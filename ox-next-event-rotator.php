<?php
/**
 * Plugin Name: OX Next Event Rotator
 * Plugin URI: https://github.com/ox-next-event-rotator
 * Description: Automatically rotates the "next-event" tag from past events to the next upcoming event. Works with The Events Calendar plugin.
 * Version: 1.0.1
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Author: Andy McLeod
 * Author URI: https://differentwines.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ox-next-event-rotator
 * Domain Path: /languages
 * Network: false
 *
 * @package OXNextEventRotator
 * @version 1.0.1
 * @author Andy McLeod
 * @license GPL v2 or later
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('OX_NER_VERSION', '1.0.1');
define('OX_NER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OX_NER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OX_NER_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('OX_NER_VERSION_OPTION', 'ox_ner_version');

// Default tag slug
define('OX_NER_DEFAULT_TAG', 'next-event');

// Cron hook name
define('OX_NER_CRON_HOOK', 'ox_next_event_rotator_daily_check');

/**
 * Check if The Events Calendar is active
 *
 * @return bool
 */
function ox_ner_check_dependencies(): bool {
    // Check if The Events Calendar class exists (available at plugins_loaded)
    if (!class_exists('Tribe__Events__Main')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('OX Next Event Rotator requires The Events Calendar plugin to be installed and activated.', 'ox-next-event-rotator');
            echo '</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Check for updates and run migrations
 */
function ox_ner_check_updates(): void {
    $current_version = get_option(OX_NER_VERSION_OPTION, '0.0.0');

    if (version_compare($current_version, OX_NER_VERSION, '<')) {
        // Run updates in sequence
        if (version_compare($current_version, '1.0.0', '<')) {
            ox_ner_update_to_1_0_0();
        }

        if (version_compare($current_version, '1.0.1', '<')) {
            ox_ner_update_to_1_0_1();
        }

        update_option(OX_NER_VERSION_OPTION, OX_NER_VERSION);
    }
}

/**
 * Update to version 1.0.1
 */
function ox_ner_update_to_1_0_1(): void {
    // No database changes needed
    // This version includes:
    // - Fixed dependency check for plugins_loaded timing
    // - Fixed event ordering with direct database queries
    // - SQLite compatibility improvements
}

/**
 * Update to version 1.0.0
 */
function ox_ner_update_to_1_0_0(): void {
    // Set default options
    if (get_option('ox_ner_tag_slug') === false) {
        add_option('ox_ner_tag_slug', OX_NER_DEFAULT_TAG);
    }

    if (get_option('ox_ner_activity_log') === false) {
        add_option('ox_ner_activity_log', []);
    }

    // Schedule the cron job if not already scheduled
    if (!wp_next_scheduled(OX_NER_CRON_HOOK)) {
        // Schedule for midnight in the site's timezone
        $timestamp = ox_ner_get_next_midnight_timestamp();
        wp_schedule_event($timestamp, 'daily', OX_NER_CRON_HOOK);
    }
}

/**
 * Get the timestamp for the next midnight in site timezone
 *
 * @return int Unix timestamp
 */
function ox_ner_get_next_midnight_timestamp(): int {
    $timezone = wp_timezone();
    $now = new DateTime('now', $timezone);
    $midnight = new DateTime('tomorrow midnight', $timezone);
    return $midnight->getTimestamp();
}

/**
 * Activation hook
 */
function ox_ner_activate(): void {
    // Check for updates/initialize
    ox_ner_check_updates();

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ox_ner_activate');

/**
 * Deactivation hook
 */
function ox_ner_deactivate(): void {
    // Clear the scheduled cron job
    $timestamp = wp_next_scheduled(OX_NER_CRON_HOOK);
    if ($timestamp) {
        wp_unschedule_event($timestamp, OX_NER_CRON_HOOK);
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'ox_ner_deactivate');

// Load plugin classes
require_once OX_NER_PLUGIN_DIR . 'includes/class-ox-ner-rotator.php';
require_once OX_NER_PLUGIN_DIR . 'includes/class-ox-ner-admin.php';

/**
 * Initialize the plugin
 */
add_action('plugins_loaded', function () {
    // Check for updates on every load
    ox_ner_check_updates();

    // Check dependencies before initializing
    if (!ox_ner_check_dependencies()) {
        return;
    }

    // Initialize the rotator (handles cron)
    new OX_NER_Rotator();

    // Initialize admin interface
    if (is_admin()) {
        new OX_NER_Admin();
    }
});

/**
 * Hook the cron event to the rotator
 */
add_action(OX_NER_CRON_HOOK, function () {
    $rotator = new OX_NER_Rotator();
    $rotator->run_rotation();
});
