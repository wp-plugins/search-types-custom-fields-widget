=== Search Types Custom Fields Widget ===
Contributors: Magenta Cuda
Tags: search, custom fields
Requires at least: 3.6
Tested up to: 4.2
Stable tag: 0.4.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Search Types custom posts for posts that have user specified values for Types custom fields.

== Description ==
This plugin is no longer being actively developed. This means no new features will be added. Since, I no longer use this plugin myself I will not know of a problem unless some user reports it. If you intend to become a new user of this plugin I would recommend you try another product. If you are an existing user please be assured that I am committed to maintaining the existing feature set of this plugin for the foreseeable future.
Although, I recently (August 2015) made a new release of this plugin, this was because I am a retired software developer who really loves developing software and was bored with nothing to do. However, I do not have any plan to further develop this plugin so the previous warning is still true.

This [search widget](http://alttypes.wordpress.com/search-types-custom-fields-widget/) can search for [Types](http://wordpress.org/plugins/types/) custom posts, WordPress posts and pages by the value of Types custom fields, WordPress taxonomies and post content. It is designed to be used with the Types plugin only and makes use of Types' proprietary database format to generate user friendly field names and field values. The widget uses user friendly substitutions for the actual values in the database when appropriate, e.g. post title is substituted for post id in parent/child fields. Please visit the [online documentation](http://alttypes.wordpress.com/search-types-custom-fields-widget/) for more details. **This plugin works with Types 1.7.7 and requires at least PHP 5.4.** [This plugin is not compatible with the WordPress Multilingual Plugin by OnTheGoSystems.](http://wordpress.org/support/topic/incompatibility-between-my-plugin-and-wpml-multilingual)

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
= 0.4.7 =
* code rewritten to fix bugs, enhance security and improve software quality
* added option to use simplified labels for select, checkboxes and radio button values
* replaced field slug with field title
* changed some css to prettify things
= 0.4.6.1.3 =
* another fix for the problem with custom field of type 'Checkboxes' with version 1.6.3 of Types
= 0.4.6.1.2 =
* fix problem with custom field of type 'Checkboxes' with version 1.6.3 of Types
= 0.4.6.1.1 =
* no new features; some small enhancements, bug fixes and code maintenance
* taxonomy items are no longer required to come before other items in the sort order
* it is now possible to choose to display post content in the table of posts which will actually display the post excerpt
* administrator's interface is now stylable using a .css file
= 0.4.6.1 =
* fixed search by tags by input box bug
* added style sheet for search widget
= 0.4.6 =
* added support for sortable tables for search results.
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
= 0.4.7 =
* code rewritten to fix bugs, enhance security and improve software quality
* added option to use simplified labels for select, checkboxes and radio button values
* replaced field slug with field title
* changed some css to prettify things
= 0.4.6.1.3 =
* another fix for the problem with custom field of type 'Checkboxes' with version 1.6.3 of Types
= 0.4.6.1.2 =
* fix problem with custom field of type 'Checkboxes' with version 1.6.3 of Types
= 0.4.6.1.1 =
* no new features; some small enhancements, bug fixes and code maintenance
* taxonomy items are no longer required to be before other items in the sort order
* it is now possible to choose to display post content in the table of posts which will actually display the post excerpt
* administrator's interface is now stylable using a .css file
= 0.4.6.1 =
* fixed search by tags by input box bug
* added style sheet for search widget
= 0.4.6 =
* added support for sortable tables for search results.
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

