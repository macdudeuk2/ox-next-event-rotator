<?php
/**
 * Core rotator logic for OX Next Event Rotator
 *
 * Handles finding the current "next-event" tagged event,
 * checking if it has passed, and rotating the tag to the next upcoming event.
 *
 * @package OXNextEventRotator
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class OX_NER_Rotator
 *
 * Handles the core rotation logic for the next-event tag.
 */
class OX_NER_Rotator {

    /**
     * The tag slug to use for identifying the "next" event
     *
     * @var string
     */
    private string $tag_slug;

    /**
     * Constructor
     */
    public function __construct() {
        $this->tag_slug = get_option('ox_ner_tag_slug', OX_NER_DEFAULT_TAG);
    }

    /**
     * Run the rotation check and perform rotation if needed
     *
     * @return array Result information about what happened
     */
    public function run_rotation(): array {
        $result = [
            'success'       => false,
            'action'        => 'none',
            'message'       => '',
            'old_event_id'  => null,
            'old_event'     => null,
            'new_event_id'  => null,
            'new_event'     => null,
            'timestamp'     => current_time('mysql'),
        ];

        // Get the current event with the next-event tag
        $current_event = $this->get_current_tagged_event();

        if (!$current_event) {
            // No event currently has the tag - find and tag the next upcoming event
            $next_event = $this->get_next_upcoming_event();

            if ($next_event) {
                $this->add_tag_to_event($next_event->ID);
                $result['success'] = true;
                $result['action'] = 'assigned';
                $result['new_event_id'] = $next_event->ID;
                $result['new_event'] = $next_event->post_title;
                $result['message'] = sprintf(
                    __('No event had the "%1$s" tag. Assigned to: %2$s', 'ox-next-event-rotator'),
                    $this->tag_slug,
                    $next_event->post_title
                );
            } else {
                $result['success'] = true;
                $result['action'] = 'no_events';
                $result['message'] = __('No upcoming events found to tag.', 'ox-next-event-rotator');
            }

            $this->log_activity($result);
            return $result;
        }

        // Check if the current tagged event has passed
        $event_start_date = get_post_meta($current_event->ID, '_EventStartDate', true);

        if (empty($event_start_date)) {
            $result['message'] = sprintf(
                __('Current tagged event "%s" has no start date.', 'ox-next-event-rotator'),
                $current_event->post_title
            );
            $this->log_activity($result);
            return $result;
        }

        $today = current_time('Y-m-d');
        $event_date = substr($event_start_date, 0, 10);

        // If the event hasn't passed yet, no rotation needed
        if ($event_date >= $today) {
            $result['success'] = true;
            $result['action'] = 'no_change';
            $result['old_event_id'] = $current_event->ID;
            $result['old_event'] = $current_event->post_title;
            $result['message'] = sprintf(
                __('Current event "%1$s" (date: %2$s) has not passed yet. No rotation needed.', 'ox-next-event-rotator'),
                $current_event->post_title,
                $event_date
            );
            $this->log_activity($result);
            return $result;
        }

        // Event has passed - find the next upcoming event
        $next_event = $this->get_next_upcoming_event();

        if (!$next_event) {
            // No next event found, but we should still remove the tag from the past event
            $this->remove_tag_from_event($current_event->ID);
            $result['success'] = true;
            $result['action'] = 'removed_only';
            $result['old_event_id'] = $current_event->ID;
            $result['old_event'] = $current_event->post_title;
            $result['message'] = sprintf(
                __('Removed tag from past event "%1$s". No upcoming events found to assign the tag to.', 'ox-next-event-rotator'),
                $current_event->post_title
            );
            $this->log_activity($result);
            return $result;
        }

        // Perform the rotation
        $this->remove_tag_from_event($current_event->ID);
        $this->add_tag_to_event($next_event->ID);

        $result['success'] = true;
        $result['action'] = 'rotated';
        $result['old_event_id'] = $current_event->ID;
        $result['old_event'] = $current_event->post_title;
        $result['new_event_id'] = $next_event->ID;
        $result['new_event'] = $next_event->post_title;
        $result['message'] = sprintf(
            __('Rotated tag from "%1$s" to "%2$s".', 'ox-next-event-rotator'),
            $current_event->post_title,
            $next_event->post_title
        );

        $this->log_activity($result);
        return $result;
    }

    /**
     * Get the current event that has the next-event tag
     *
     * @return WP_Post|null The event post or null if none found
     */
    public function get_current_tagged_event(): ?WP_Post {
        $args = [
            'post_type'      => 'tribe_events',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'tag'            => $this->tag_slug,
            'orderby'        => 'meta_value',
            'meta_key'       => '_EventStartDate',
            'order'          => 'ASC',
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            return $query->posts[0];
        }

        return null;
    }

    /**
     * Get the next upcoming event
     *
     * Uses direct database query to avoid TEC query filters and ensure
     * compatibility with SQLite.
     *
     * @return WP_Post|null The next event post or null if none found
     */
    public function get_next_upcoming_event(): ?WP_Post {
        global $wpdb;

        // Use full datetime format to match how TEC stores dates
        $now = current_time('Y-m-d H:i:s');

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT p.ID
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_EventStartDate'
                WHERE p.post_type = %s
                AND p.post_status = %s
                AND pm.meta_value >= %s
                ORDER BY pm.meta_value ASC
                LIMIT 1",
                'tribe_events',
                'publish',
                $now
            )
        );

        if ($result) {
            return get_post($result->ID);
        }

        return null;
    }

    /**
     * Get a list of upcoming events for display
     *
     * Uses direct database query to avoid TEC query filters and ensure
     * compatibility with SQLite.
     *
     * @param int $limit Number of events to retrieve
     * @return array Array of event data
     */
    public function get_upcoming_events_list(int $limit = 5): array {
        global $wpdb;

        $now = current_time('Y-m-d H:i:s');
        $events = [];

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_title, pm.meta_value as start_date
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_EventStartDate'
                WHERE p.post_type = %s
                AND p.post_status = %s
                AND pm.meta_value >= %s
                ORDER BY pm.meta_value ASC
                LIMIT %d",
                'tribe_events',
                'publish',
                $now,
                $limit
            )
        );

        if ($results) {
            foreach ($results as $row) {
                $has_tag = has_tag($this->tag_slug, $row->ID);

                $events[] = [
                    'id'         => $row->ID,
                    'title'      => $row->post_title,
                    'start_date' => $row->start_date ? substr($row->start_date, 0, 10) : 'N/A',
                    'has_tag'    => $has_tag,
                    'edit_url'   => get_edit_post_link($row->ID),
                ];
            }
        }

        return $events;
    }

    /**
     * Add the next-event tag to an event
     *
     * @param int $event_id The event post ID
     * @return bool True on success, false on failure
     */
    public function add_tag_to_event(int $event_id): bool {
        $result = wp_set_post_tags($event_id, $this->tag_slug, true);
        return !is_wp_error($result);
    }

    /**
     * Remove the next-event tag from an event
     *
     * @param int $event_id The event post ID
     * @return bool True on success, false on failure
     */
    public function remove_tag_from_event(int $event_id): bool {
        // Get current tags
        $current_tags = wp_get_post_tags($event_id, ['fields' => 'names']);

        if (empty($current_tags)) {
            return true;
        }

        // Remove the next-event tag
        $new_tags = array_filter($current_tags, function ($tag) {
            return $tag !== $this->tag_slug;
        });

        // Set the remaining tags (this replaces all tags)
        $result = wp_set_post_tags($event_id, $new_tags, false);
        return !is_wp_error($result);
    }

    /**
     * Get the current tag slug
     *
     * @return string
     */
    public function get_tag_slug(): string {
        return $this->tag_slug;
    }

    /**
     * Log activity to the options table
     *
     * @param array $result The result array from run_rotation
     */
    private function log_activity(array $result): void {
        $log = get_option('ox_ner_activity_log', []);

        // Keep only the last 20 entries
        if (count($log) >= 20) {
            array_shift($log);
        }

        $log[] = $result;
        update_option('ox_ner_activity_log', $log);
    }

    /**
     * Get the activity log
     *
     * @return array
     */
    public function get_activity_log(): array {
        return get_option('ox_ner_activity_log', []);
    }

    /**
     * Clear the activity log
     */
    public function clear_activity_log(): void {
        update_option('ox_ner_activity_log', []);
    }

    /**
     * Get status information for the admin display
     *
     * @return array Status information
     */
    public function get_status(): array {
        $status = [
            'tag_slug'         => $this->tag_slug,
            'current_event'    => null,
            'next_event'       => null,
            'next_cron_run'    => null,
            'tag_exists'       => false,
        ];

        // Check if the tag exists
        $tag = get_term_by('slug', $this->tag_slug, 'post_tag');
        $status['tag_exists'] = ($tag !== false);

        // Get current tagged event
        $current = $this->get_current_tagged_event();
        if ($current) {
            $start_date = get_post_meta($current->ID, '_EventStartDate', true);
            $status['current_event'] = [
                'id'         => $current->ID,
                'title'      => $current->post_title,
                'start_date' => $start_date ? substr($start_date, 0, 10) : 'N/A',
                'is_past'    => $start_date ? (substr($start_date, 0, 10) < current_time('Y-m-d')) : false,
                'edit_url'   => get_edit_post_link($current->ID),
            ];
        }

        // Get next upcoming event
        $next = $this->get_next_upcoming_event();
        if ($next) {
            $start_date = get_post_meta($next->ID, '_EventStartDate', true);
            $has_tag = has_tag($this->tag_slug, $next->ID);
            $status['next_event'] = [
                'id'         => $next->ID,
                'title'      => $next->post_title,
                'start_date' => $start_date ? substr($start_date, 0, 10) : 'N/A',
                'has_tag'    => $has_tag,
                'edit_url'   => get_edit_post_link($next->ID),
            ];
        }

        // Get next scheduled cron run
        $next_cron = wp_next_scheduled(OX_NER_CRON_HOOK);
        if ($next_cron) {
            $status['next_cron_run'] = wp_date('Y-m-d H:i:s', $next_cron);
        }

        return $status;
    }
}
