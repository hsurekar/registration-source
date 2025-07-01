=== Registration Source ===
Contributors: hsurekar
Tags: registration, user, source, analytics, tracking
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

**Registration Source** helps you track and analyze where your WordPress users are registering from. Whether users sign up via the default registration form, REST API, XML-RPC, or other supported sources, this plugin records the origin and displays it in your admin dashboard.

**Key Features:**
- Automatically tracks the source of every new user registration (native form, REST API, XML-RPC, and more).
- Displays the registration source in the Users list in the WordPress admin.
- Provides a dashboard widget with registration source statistics and charts.
- REST API endpoint for programmatic access to registration source data.
- Bulk actions to manage registration source data for users.
- Extensible: Developers can add custom sources via hooks and filters.
- No configuration required—works out of the box!

**Benefits:**
- Understand how users are registering on your site.
- Identify which registration methods are most popular.
- Improve your marketing and onboarding strategies with real data.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/registration-source` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. (Optional) Visit **Settings > Registration Source** to configure plugin options.

== Usage ==

- After activation, the plugin will automatically track the source of all new user registrations.
- Go to **Users > All Users** in your WordPress admin to see the "Registration Source" column.
- Visit your WordPress dashboard to view the "Registration Sources" widget with statistics and charts.
- Use the REST API endpoint `/wp-json/registration-source/v1/statistics` to access registration source data programmatically.
- Use bulk actions in the Users list to manage registration source data for multiple users.

== Frequently Asked Questions ==

= Does this plugin require any configuration? =
No. The plugin works out of the box. You can optionally visit **Settings > Registration Source** to adjust options.

= Which registration sources are tracked? =
- Native WordPress registration form
- REST API
- XML-RPC
- (Extensible) Other sources via developer hooks

= Can I export registration source data? =
Yes, you can use the REST API endpoint to export data.

= Is this plugin compatible with multisite? =
Yes, the plugin is compatible with WordPress multisite installations.

== Screenshots ==

1. Registration Source column in the Users list.
2. Registration Sources dashboard widget with statistics and charts.
3. Settings page for configuration.

== Changelog ==
= 1.1.0 =
* Initial release.

== License ==
This plugin is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this plugin; if not, see https://www.gnu.org/licenses/gpl-2.0.html 