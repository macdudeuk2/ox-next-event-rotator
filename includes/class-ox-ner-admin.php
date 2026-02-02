<?php
/**
 * Admin interface for OX Next Event Rotator
 *
 * Provides a settings page with status display, manual run button,
 * and activity log.
 *
 * @package OXNextEventRotator
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class OX_NER_Admin
 *
 * Handles the admin interface and settings page.
 */
class OX_NER_Admin {

    /**
     * The rotator instance
     *
     * @var OX_NER_Rotator
     */
    private OX_NER_Rotator $rotator;

    /**
     * Constructor
     */
    public function __construct() {
        $this->rotator = new OX_NER_Rotator();

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'handle_admin_actions']);
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'edit.php?post_type=tribe_events',
            __('Next Event Rotator', 'ox-next-event-rotator'),
            __('Next Event Rotator', 'ox-next-event-rotator'),
            'manage_options',
            'ox-next-event-rotator',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook The current admin page hook
     */
    public function enqueue_admin_assets(string $hook): void {
        if ($hook !== 'tribe_events_page_ox-next-event-rotator') {
            return;
        }

        wp_enqueue_style(
            'ox-ner-admin',
            OX_NER_PLUGIN_URL . 'assets/css/admin.css',
            [],
            OX_NER_VERSION
        );
    }

    /**
     * Handle admin form actions
     */
    public function handle_admin_actions(): void {
        // Handle manual rotation
        if (isset($_POST['ox_ner_run_now']) && check_admin_referer('ox_ner_run_now_action', 'ox_ner_run_now_nonce')) {
            $result = $this->rotator->run_rotation();
            $message_type = $result['success'] ? 'success' : 'error';
            add_settings_error(
                'ox_ner_messages',
                'ox_ner_run_result',
                $result['message'],
                $message_type
            );
        }

        // Handle settings save
        if (isset($_POST['ox_ner_save_settings']) && check_admin_referer('ox_ner_settings_action', 'ox_ner_settings_nonce')) {
            $tag_slug = isset($_POST['ox_ner_tag_slug']) ? sanitize_title($_POST['ox_ner_tag_slug']) : OX_NER_DEFAULT_TAG;

            if (empty($tag_slug)) {
                $tag_slug = OX_NER_DEFAULT_TAG;
            }

            update_option('ox_ner_tag_slug', $tag_slug);

            add_settings_error(
                'ox_ner_messages',
                'ox_ner_settings_saved',
                __('Settings saved successfully.', 'ox-next-event-rotator'),
                'success'
            );
        }

        // Handle clear log
        if (isset($_POST['ox_ner_clear_log']) && check_admin_referer('ox_ner_clear_log_action', 'ox_ner_clear_log_nonce')) {
            $this->rotator->clear_activity_log();
            add_settings_error(
                'ox_ner_messages',
                'ox_ner_log_cleared',
                __('Activity log cleared.', 'ox-next-event-rotator'),
                'success'
            );
        }
    }

    /**
     * Render the admin page
     */
    public function render_admin_page(): void {
        $status = $this->rotator->get_status();
        $activity_log = $this->rotator->get_activity_log();
        $upcoming_events = $this->rotator->get_upcoming_events_list(10);
        ?>
        <div class="wrap ox-ner-admin">
            <h1><?php echo esc_html__('Next Event Rotator', 'ox-next-event-rotator'); ?></h1>

            <?php settings_errors('ox_ner_messages'); ?>

            <div class="ox-ner-grid">
                <!-- Current Status Card -->
                <div class="ox-ner-card">
                    <h2><?php echo esc_html__('Current Status', 'ox-next-event-rotator'); ?></h2>

                    <table class="ox-ner-status-table">
                        <tr>
                            <th><?php echo esc_html__('Tag Slug:', 'ox-next-event-rotator'); ?></th>
                            <td>
                                <code><?php echo esc_html($status['tag_slug']); ?></code>
                                <?php if (!$status['tag_exists']): ?>
                                    <span class="ox-ner-warning"><?php echo esc_html__('(tag does not exist yet)', 'ox-next-event-rotator'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Currently Tagged Event:', 'ox-next-event-rotator'); ?></th>
                            <td>
                                <?php if ($status['current_event']): ?>
                                    <a href="<?php echo esc_url($status['current_event']['edit_url']); ?>">
                                        <?php echo esc_html($status['current_event']['title']); ?>
                                    </a>
                                    <br>
                                    <small>
                                        <?php echo esc_html__('Date:', 'ox-next-event-rotator'); ?>
                                        <?php echo esc_html($status['current_event']['start_date']); ?>
                                        <?php if ($status['current_event']['is_past']): ?>
                                            <span class="ox-ner-past"><?php echo esc_html__('(PAST)', 'ox-next-event-rotator'); ?></span>
                                        <?php endif; ?>
                                    </small>
                                <?php else: ?>
                                    <span class="ox-ner-none"><?php echo esc_html__('No event currently tagged', 'ox-next-event-rotator'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Next Upcoming Event:', 'ox-next-event-rotator'); ?></th>
                            <td>
                                <?php if ($status['next_event']): ?>
                                    <a href="<?php echo esc_url($status['next_event']['edit_url']); ?>">
                                        <?php echo esc_html($status['next_event']['title']); ?>
                                    </a>
                                    <br>
                                    <small>
                                        <?php echo esc_html__('Date:', 'ox-next-event-rotator'); ?>
                                        <?php echo esc_html($status['next_event']['start_date']); ?>
                                        <?php if ($status['next_event']['has_tag']): ?>
                                            <span class="ox-ner-tagged"><?php echo esc_html__('(has tag)', 'ox-next-event-rotator'); ?></span>
                                        <?php endif; ?>
                                    </small>
                                <?php else: ?>
                                    <span class="ox-ner-none"><?php echo esc_html__('No upcoming events found', 'ox-next-event-rotator'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Next Scheduled Run:', 'ox-next-event-rotator'); ?></th>
                            <td>
                                <?php if ($status['next_cron_run']): ?>
                                    <?php echo esc_html($status['next_cron_run']); ?>
                                <?php else: ?>
                                    <span class="ox-ner-warning"><?php echo esc_html__('Not scheduled', 'ox-next-event-rotator'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>

                    <form method="post" class="ox-ner-run-form">
                        <?php wp_nonce_field('ox_ner_run_now_action', 'ox_ner_run_now_nonce'); ?>
                        <button type="submit" name="ox_ner_run_now" class="button button-primary">
                            <?php echo esc_html__('Run Rotation Now', 'ox-next-event-rotator'); ?>
                        </button>
                        <p class="description">
                            <?php echo esc_html__('Manually trigger the rotation check. Useful for testing.', 'ox-next-event-rotator'); ?>
                        </p>
                    </form>
                </div>

                <!-- Settings Card -->
                <div class="ox-ner-card">
                    <h2><?php echo esc_html__('Settings', 'ox-next-event-rotator'); ?></h2>

                    <form method="post">
                        <?php wp_nonce_field('ox_ner_settings_action', 'ox_ner_settings_nonce'); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="ox_ner_tag_slug"><?php echo esc_html__('Tag Slug', 'ox-next-event-rotator'); ?></label>
                                </th>
                                <td>
                                    <input type="text"
                                           id="ox_ner_tag_slug"
                                           name="ox_ner_tag_slug"
                                           value="<?php echo esc_attr($status['tag_slug']); ?>"
                                           class="regular-text">
                                    <p class="description">
                                        <?php echo esc_html__('The tag slug used to identify the "next" event. Default: next-event', 'ox-next-event-rotator'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" name="ox_ner_save_settings" class="button button-primary">
                                <?php echo esc_html__('Save Settings', 'ox-next-event-rotator'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Upcoming Events List -->
            <div class="ox-ner-card ox-ner-full-width">
                <h2><?php echo esc_html__('Upcoming Events', 'ox-next-event-rotator'); ?></h2>

                <?php if (empty($upcoming_events)): ?>
                    <p><?php echo esc_html__('No upcoming events found.', 'ox-next-event-rotator'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Event', 'ox-next-event-rotator'); ?></th>
                                <th><?php echo esc_html__('Start Date', 'ox-next-event-rotator'); ?></th>
                                <th><?php echo esc_html__('Has Tag', 'ox-next-event-rotator'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_events as $event): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url($event['edit_url']); ?>">
                                            <?php echo esc_html($event['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html($event['start_date']); ?></td>
                                    <td>
                                        <?php if ($event['has_tag']): ?>
                                            <span class="ox-ner-tagged"><?php echo esc_html__('Yes', 'ox-next-event-rotator'); ?></span>
                                        <?php else: ?>
                                            <span class="ox-ner-no-tag"><?php echo esc_html__('No', 'ox-next-event-rotator'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Activity Log -->
            <div class="ox-ner-card ox-ner-full-width">
                <h2><?php echo esc_html__('Activity Log', 'ox-next-event-rotator'); ?></h2>

                <?php if (empty($activity_log)): ?>
                    <p><?php echo esc_html__('No activity logged yet.', 'ox-next-event-rotator'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Timestamp', 'ox-next-event-rotator'); ?></th>
                                <th><?php echo esc_html__('Action', 'ox-next-event-rotator'); ?></th>
                                <th><?php echo esc_html__('Message', 'ox-next-event-rotator'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($activity_log) as $entry): ?>
                                <tr>
                                    <td><?php echo esc_html($entry['timestamp']); ?></td>
                                    <td>
                                        <span class="ox-ner-action ox-ner-action-<?php echo esc_attr($entry['action']); ?>">
                                            <?php echo esc_html($entry['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($entry['message']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <form method="post" class="ox-ner-clear-log-form">
                        <?php wp_nonce_field('ox_ner_clear_log_action', 'ox_ner_clear_log_nonce'); ?>
                        <button type="submit" name="ox_ner_clear_log" class="button">
                            <?php echo esc_html__('Clear Log', 'ox-next-event-rotator'); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- How It Works -->
            <div class="ox-ner-card ox-ner-full-width">
                <h2><?php echo esc_html__('How It Works', 'ox-next-event-rotator'); ?></h2>
                <ol>
                    <li><?php echo esc_html__('The plugin runs automatically once per day at midnight (site timezone).', 'ox-next-event-rotator'); ?></li>
                    <li><?php echo esc_html__('It checks if the event currently tagged with "next-event" has a start date in the past.', 'ox-next-event-rotator'); ?></li>
                    <li><?php echo esc_html__('If the event has passed, the tag is removed and applied to the next upcoming event (by start date).', 'ox-next-event-rotator'); ?></li>
                    <li><?php echo esc_html__('If no event has the tag, it will be assigned to the next upcoming event.', 'ox-next-event-rotator'); ?></li>
                </ol>
                <p>
                    <strong><?php echo esc_html__('Note:', 'ox-next-event-rotator'); ?></strong>
                    <?php echo esc_html__('You can use the "Run Rotation Now" button above to test the rotation manually at any time.', 'ox-next-event-rotator'); ?>
                </p>
            </div>
        </div>
        <?php
    }
}
