=== Search Types Custom Fields Widget ===
Contributors: Magenta Cuda
Tags: search, custom fields
Requires at least: 3.6
Tested up to: 3.9
Stable tag: 0.4.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Search Types custom posts for posts that have user specified values for Types custom fields.

== Description ==
This search widget can search for [Types](http://wordpress.org/plugins/types/) custom posts, WordPress posts and pages by the value of Types custom fields, WordPress taxonomies and post content. It is designed to be used with the Types plugin only and makes use of Types' proprietary database format to generate user friendly field names and field values. The widget uses user friendly substitutions for the actual values in the database when appropriate, e.g. post title is substituted for post id in parent/child fields. Please visit the [online documentation](http://alttypes.wordpress.com/search-types-custom-fields-widget/) for more details. **This plugin works with Types 1.5.7 and requires at least PHP 5.4.** [This plugin is not compatible with the WordPress Multilingual Plugin by OnTheGoSystems.](http://wordpress.org/support/topic/incompatibility-between-my-plugin-and-wpml-multilingual)

== Installation ==
1. Upload the folder "search-types-custom-fields-widget" to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Activate this widget by dragging it to a sidebar through the "Appearance->Widgets" menu in WordPress.

== Frequently Asked Questions ==
= Does this plugin require that Types plugin to be installed? =
Yes.

= Where is the documentation? =
http://alttypes.wordpress.com/search-types-custom-fields-widget/

== Screenshots ==
1. The Adminstrator's Interface for Field Selection.
2. The Adminstrator's Interface for Settings.
3. The User's Interface for Post Type Selection.
4. The User's Interface for Searching Posts of the Selected Type.
5. The User's Interface for Settings.
6. A Sample Table of Post

== Changelog ==
= 0.4.6.1 =
fixed search by tags by input box bug
added style sheet for search widget
= 0.4.6 =
added support for sortable tables for search results.
= 0.4.5.3 =
* added search by post author
* added support for post type specific css files
* fix pagination bug
* fix several other bugs
= 0.4.5 =
* optionally display seach results in a table format
* optionally set query type to is_search so only excerpts are displayed for applicable themes
* supports drag and drop to change order of fields
= 0.4.4 =
* added range search for numeric and date custom fields
= 0.4.3 =
* separated child of/parent of categories by post type
* made items shown per custom field user settable
* fixed to handle corrupt obsolete data without crashing
* fixed incorrect counts due to double counting and counting blank and null values
* tweaked the display of checkboxes, select and radio custom fields
= 0.4.2 =
* add support for selecting and/or on search conditions
= 0.4.1.1 =
* Initial release.

== Upgrade Notice ==
= 0.4.6.1 =
fixed search by tags by input box bug
added style sheet for search widget
= 0.4.6 =
added support for sortable tables for search results.
= 0.4.5.3 =
* added search by post author
* added support for post type specific css files
* fix pagination bug
* fix several other bugs
= 0.4.5 =
* optionally display seach results in a table format
* optionally set query type to is_search so only excerpts are displayed for applicable themes
* supports drag and drop to change order of fields
= 0.4.4 =
* added range search for numeric and date custom fields
= 0.4.3 =
* some enhancements and bug fixes
= 0.4.2 =
add support for selecting and/or on search conditions
= 0.4.1.1 =
* Initial release.

