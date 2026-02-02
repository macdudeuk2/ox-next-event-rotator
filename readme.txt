=== OX Next Event Rotator ===
Contributors: andymcleod
Tags: events, the-events-calendar, automation, scheduling
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically rotates the "next-event" tag from past events to the next upcoming event. Works with The Events Calendar plugin.

== Description ==

OX Next Event Rotator automates the management of a "next-event" tag for events created with The Events Calendar plugin.

**How It Works:**

1. The plugin runs automatically once per day at midnight (site timezone).
2. It checks if the event currently tagged with "next-event" has a start date in the past.
3. If the event has passed, the tag is removed and applied to the next upcoming event (by start date).
4. If no event has the tag, it will be assigned to the next upcoming event.

**Features:**

* Automatic daily rotation at midnight
* Manual "Run Now" button for testing
* Admin dashboard showing current status
* Activity log tracking all rotation actions
* Configurable tag slug (default: "next-event")
* Full integration with The Events Calendar

**Requirements:**

* WordPress 5.0 or higher
* PHP 7.4 or higher
* The Events Calendar plugin (free or Pro version)

== Installation ==

1. Upload the `ox-next-event-rotator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure The Events Calendar plugin is installed and activated
4. Go to Events > Next Event Rotator to view status and settings

== Frequently Asked Questions ==

= Does this work with The Events Calendar Pro? =

Yes, this plugin works with both the free version and Pro version of The Events Calendar.

= Can I change the tag name? =

Yes, you can configure the tag slug in the plugin settings. The default is "next-event".

= How do I test the rotation? =

Use the "Run Rotation Now" button on the plugin's admin page. This manually triggers the rotation check without waiting for the scheduled daily run.

= What happens if no event has the tag? =

The plugin will automatically assign the tag to the next upcoming event based on start date.

= Does the plugin handle events added between the current and next event? =

No, by design the plugin simply rotates to the chronologically next event when the current tagged event expires. It does not re-evaluate if a new event is added that would fall between them.

== Screenshots ==

1. Admin dashboard showing current status and upcoming events
2. Activity log showing rotation history

== Changelog ==

= 1.0.0 =
* Initial release
* Daily automatic rotation via WP-Cron
* Manual rotation button for testing
* Admin dashboard with status display
* Activity log for tracking rotations
* Configurable tag slug setting

== Upgrade Notice ==

= 1.0.0 =
Initial release of OX Next Event Rotator.
