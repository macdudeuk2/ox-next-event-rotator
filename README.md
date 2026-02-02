# OX Next Event Rotator

Automatically rotates the "next-event" tag from past events to the next upcoming event. Works with The Events Calendar plugin.

## Description

OX Next Event Rotator automates the management of a "next-event" tag for events created with The Events Calendar plugin.

### How It Works

1. The plugin runs automatically once per day at midnight (site timezone).
2. It checks if the event currently tagged with "next-event" has a start date in the past.
3. If the event has passed, the tag is removed and applied to the next upcoming event (by start date).
4. If no event has the tag, it will be assigned to the next upcoming event.

### Features

- Automatic daily rotation at midnight
- Manual "Run Now" button for testing
- Admin dashboard showing current status
- Activity log tracking all rotation actions
- Configurable tag slug (default: "next-event")
- Full integration with The Events Calendar

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- The Events Calendar plugin (free or Pro version)

## Installation

1. Upload the `ox-next-event-rotator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure The Events Calendar plugin is installed and activated
4. Go to **Events → Next Event Rotator** to view status and settings

## Usage

### Admin Dashboard

Navigate to **Events → Next Event Rotator** in the WordPress admin to:

- View the currently tagged event and its status
- See the next upcoming event in the queue
- Check when the next scheduled rotation will run
- Manually trigger a rotation for testing
- Review the activity log

### Testing

Use the **"Run Rotation Now"** button on the admin page to manually trigger the rotation check. This is useful for:

- Testing the plugin after installation
- Verifying the rotation logic works correctly
- Forcing a rotation without waiting for midnight

## Configuration

| Setting | Description | Default |
|---------|-------------|---------|
| Tag Slug | The tag used to identify the "next" event | `next-event` |

## FAQ

### Does this work with The Events Calendar Pro?

Yes, this plugin works with both the free version and Pro version of The Events Calendar.

### Can I change the tag name?

Yes, you can configure the tag slug in the plugin settings. The default is "next-event".

### What happens if no event has the tag?

The plugin will automatically assign the tag to the next upcoming event based on start date.

### Does the plugin handle events added between the current and next event?

No, by design the plugin simply rotates to the chronologically next event when the current tagged event expires. It does not re-evaluate if a new event is added that would fall between them.

## Changelog

### 1.0.0
- Initial release
- Daily automatic rotation via WP-Cron
- Manual rotation button for testing
- Admin dashboard with status display
- Activity log for tracking rotations
- Configurable tag slug setting

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Author

Andy McLeod - [differentwines.com](https://differentwines.com)
