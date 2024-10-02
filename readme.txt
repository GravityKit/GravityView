=== GravityView ===
Tags: gravity forms, directory, gravity forms directory
Requires at least: 4.7
Tested up to: 6.6.2
Requires PHP: 7.4.0
Stable tag: trunk
Contributors: The GravityKit Team
License: GPL 3 or higher

Beautifully display and edit your Gravity Forms entries.

== Description ==

Beautifully display your Gravity Forms entries. Learn more on [gravitykit.com](https://www.gravitykit.com).

== Installation ==

1. Upload plugin files to your plugins folder, or install using WordPress' built-in Add New Plugin installer
2. Activate the plugin
3. Follow the instructions

== Changelog ==

= develop =

* Added: `{now}`, `{yesterday}` and `{tomorrow}` relative date merge tags.
* Improved: Better handling of multi-file uploads on the Edit Entry screen.

= 2.29 on October 1, 2024 =

This release introduces a much-requested [lightbox feature](https://docs.gravitykit.com/article/1020-opening-and-editing-entry-details-in-a-lightbox-modal-popup) for displaying and editing entries, settings for customizing View URLs, new options for [displaying Name field initials](https://docs.gravitykit.com/article/1021-show-name-fields-as-initials) and Custom Content fields in full width, and a merge tag modifier to show date field values in a human-readable format. Several bugs have also been fixed.

#### 🚀 Added
* Ability to edit and display entries inside a lightbox.
* Global and individual View settings to customize the URL structure for all or specific Views.
* `:human` merge tag modifier for date fields to display in human-readable format (e.g., `10 minutes ago`, `5 days from now`).
* Option to display the Name field value as initials.
* Option to display Custom Content field full width on the Single Entry screen.

#### 🐛 Fixed
* Clearing search removed all URL query parameters and, in some cases, redirected to the homepage.
* Searching the View added duplicate search parameters to the URL.
* PHP 8.2 deprecation notice related to dynamic property creation.
* Entries not displaying when a View using DataTables was embedded in a Single Entry page with the List layout.
* PHP warning when displaying a View with an Event field without an active Gravity Forms Event Fields Add-On.
* Sorting entries in random order was not working.
* Multi Select field values starting with a square bracket were not displayed as selected on the Edit Entry screen.

#### 🔧 Updated
* [Foundation](https://www.gravitykit.com/foundation/) to version 1.2.18.

#### 💻 Developer Updates
* Added `gk/gravityview/field/name/display` filter to modify the Name field display value.
* Added `gk/gravityview/permalinks/reserved-terms` filter to modify the list of reserved terms that are excluded from permalinks.

= 2.28 on August 29, 2024 =

This update adds support for plain-text URLs in entry moderation merge tags, and fixes several bugs, including critical errors in the View editor. Starting with this version, PHP 7.4 or newer is required.

**Note: GravityView now requires PHP 7.4 or newer.**

#### 🚀 Added
* Modifier for entry moderation merge tags to output plain-text URLs (e.g., `{gv_approve_entry:url}`).

#### 🐛 Fixed
* "Text domain not found" error when trying to install a layout during the View creation process.
* Fatal error in the View editor when the user does not have the necessary capabilities to install plugins.
* Merge tag support in the Source URL "Link Text" field setting.
* Deprecated filter notice when using GravityView Maps 3.1.0 or newer.
* PHP 8.2 deprecation notice due to passing an empty value to `htmlspecialchars()` and creating dynamic class properties.
* The maximum number of files allowed in the File Upload field was not respected when editing an entry.
* Sorting the View by the Name field yielded incorrect results.

#### 🔧 Updated
* [TrustedLogin](https://www.trustedlogin.com/) to version 1.9.0.

#### 💻 Developer Updates
* Added `gk/gravityview/view/entries/query/sorting-parameters` filter to modify the sorting parameters applied during the retrieval of View entries.

= 2.27.1 on August 14, 2024 =

This release fixes an issue with adding fields in the View editor's Edit Entry layout when the Multiple Forms extension is enabled.

#### 🐛 Fixed
* Fields added to the Edit Entry layout in the View editor could not be configured and would disappear after saving the View when Multiple Forms was enabled.

= 2.27 on August 13, 2024 =

This update resolves several issues related to the Multiple Forms extension, fixes the recently introduced `:format` merge tag modifier to return the Time field value in the local timezone, and adds a new filter to control which fields are added by default when creating a new View.

#### 🐛 Fixed
* Time zone selection in the Search Bar did not persist after searching a View, causing it to reset upon page refresh.
* Fields added to the View could not be configured and would disappear after saving the View when Multiple Forms was enabled.
* Fatal error occurred on the Edit Entry screen when Multiple Forms was enabled.
* The `:format` merge tag modifier on the Time field returned a UTC-adjusted time value.

#### 💻 Developer Updates
* Added `gk/gravityview/view/configuration/multiple-entries/initialize-with-all-form-fields` filter that, when set to `true`, initializes the Multiple Entries layout with all form fields when creating a new View. The default is `false`, which populates the View with only the fields configured in the Gravity Forms Entries table.

= 2.26 on August 8, 2024 =

This update resolves various issues, including compatibility with Yoast SEO, improves performance through enhanced View entries caching, and adds new functionality.

#### 🚀 Added
* Ability to modify the entry creator’s information on the Edit Entry screen.
* Merge tag modifier for formatting Date and Time fields (e.g., `{Date Field:1:format:Y-m-d}`).
* Placeholders in View Settings to inform you that additional functionality is available.

#### ✨ Improved
* The "Sort By" option in the GravityView Gutenberg block now offers a dropdown selection of fields instead of requiring manual entry of the field ID.
* Caching of View entries to prevent unnecessary database queries. Thanks, Shehroz!

#### 🐛 Fixed
* Timeout issue when rendering a page/post with GravityView Gutenberg blocks when Yoast SEO is active.
* View editor fields added to the Single or Edit Entry layouts inheriting options from the View type set in the Multiple Entries layout.
* An issue in the Search Bar widget configuration where adding a Date field caused the search mode ("any" and "all") to no longer be toggleable.
* `[gv_entry_link]` shortcode not rendering inside the Custom HTML block.

#### 🔧 Updated
* [Foundation](https://www.gravitykit.com/foundation/) and [TrustedLogin](https://www.trustedlogin.com/) to versions 1.2.17 and 1.8.0, respectively.

#### 💻 Developer Updates
* Added `gk/gravityview/feature/upgrade/disabled` filter to disable the functionality placeholders. Return `true` to disable the placeholders.
* Added `gk/gravityview/metabox/content/before` and `gk/gravityview/metabox/content/after` actions, triggered before and after the View metabox is rendered.

= 2.25 on June 5, 2024 =

This update improves how entries are automatically marked as "Read" and adds a new View setting to control this functionality.

**Note: GravityView now requires Gravity Forms 2.6 (released in March 2022) or newer.**

#### 🚀 Added
* New View setting under the Single Entry tab to mark an entry as "Read". [Read more about the feature](https://docs.gravitykit.com/article/1008-marking-entries-as-read).

#### ✨ Improved
* Marking an entry as "Read" is now handled in the backend and also supports the Multiple Forms extension.

#### 🐛 Fixed
* Appearance of the Merge Tag picker in the field settings of the View editor.

#### 💻 Developer Updates
* Removed the `gk/gravityview/field/is-read/print-script` filter in favor of the improved functionality that marks entries as "Read".

= 2.24 on May 28, 2024 =

This release introduces the ability to use different view types for Multiple Entries and Single Entry layouts, adds a new View field to display an entry's read status, and fixes issues with the File Upload field, product search, and merge tag processing in entry-based notifications. [Read the announcement](https://www.gravitykit.com/announcing-gravityview-2-24/) for more details.

#### 🚀 Added
* Ability to select different View types for Multiple Entries and Single Entry layouts. [Learn all about the new View type switcher!](https://www.gravitykit.com/announcing-gravityview-2-24/)
* "Read Status" field to display whether an entry has been read or not.
  - Customize the labels for "Read" and "Unread" statuses.
  - Sort a View by "Read Status".
* Entries are now marked as "Read" when users who have the ability to edit entries visit an entry in the front-end.

#### 🐛 Fixed
* File Upload field values not rendering in the View if filenames have non-Latin characters.
* Product search now returns correct results when using all search input types in the search bar.
* View's Export Link widget would not respect date range search filters.
* Removed the unsupported "date" input type for the Date Entry field under the Search Bar widget settings.
* Merge tags in GravityView notifications are now properly processed for fields dynamically populated by Gravity Wiz's Populate Anything add-on.

#### 💻 Developer Updates
* Added `gk/gravityview/field/is-read/print-script` filter to modify whether to print the script in the frontend that marks an entry as "Read".
* Added `gk/gravityview/field/is-read/label` filter to change the "Is Read" field's "Read" and "Unread" labels.
* Added `gk/gravityview/entry-approval/choices` filter to modify strings used for entry approval ("Approved", "Unapproved", "Disapproved", etc.).

= 2.23 on May 17, 2024 =

This update adds support for Nested Forms' entry meta, addresses several bugs, including critical ones, and improves GravityKit's Settings and Manage Your Kit screens.

#### 🚀 Added
* Support for Gravity Wiz's Gravity Forms Nested Forms entry meta (parent form and entry IDs, child form field ID) in the View editor and merge tags.

#### 🐛 Fixed
* Export link View widget would cause a fatal error during multi-word searches.
* Fatal error when the search bar is configured with a Gravity Flow field and the Gravity Flow plugin is not active.
* Duplicating entries no longer fails to refresh the entry list when View-based caching is enabled.
* View cache not being invalidated when updating entries on a form joined using the Multiple Forms extension.
* Number field output now respects the form field's format settings, such as decimals and currency.

#### 🔧 Updated
* [Foundation](https://www.gravitykit.com/foundation/) to version 1.2.14.
  - Added an option to subscribe to GravityKit's newsletter from the Manage Your Kit screen.
  - Added a setting in GravityKit > Settings > GravityKit to specify the GravityKit menu position in the Dashboard.
  - Improved internal check for product updates that could still interfere with third-party plugin updates. Thanks, Aaron!
  - Fixed a bug that prevented WordPress from loading third-party plugin translations after their updates. Thanks, Jérôme!
  - Success message now shows correct product name after activation/deactivation.

#### 💻 Developer Updates
* Added `gk/gravityview/entry/approval-link/params` filter to modify entry approval link parameters.

= 2.22 on April 16, 2024 =

This release introduces [support for search modifiers](https://docs.gravitykit.com/article/995-gravityview-search-modifiers) and [range-based searching for numeric fields](https://docs.gravitykit.com/article/996-number-range-search), enables easy duplication and precise insertion of View fields and widgets, and resolves critical issues with Yoast SEO and LifterLMS. [Read the announcement](https://www.gravitykit.com/gravityview-2-22/) for more details.

#### 🚀 Added
* Support for negative, positive, and exact-match search modifiers in the Search Bar.
* Range-based search for Number, Product (user-defined price), Quantity and Total fields in the Search Bar.
* Ability to duplicate View fields and widgets, and to insert them at a desired position.

#### 🐛 Fixed
* Editing an entry with Yoast SEO active resulted in changes being saved twice.
* Views secured with a secret code did not display inside LifterLMS dashboards.
* View editor display issues when LifterLMS is active.
* Fatal error when editing posts/pages containing GravityView blocks.

#### 🔧 Updated
* [Foundation](https://www.gravitykit.com/foundation/) to version 1.2.12.
  - Fixed a bug that hid third-party plugin updates on the Plugins and Updates pages.
  - Resolved a dependency management issue that incorrectly prompted for a Gravity Forms update before activating, installing, or updating GravityKit products.

__Developer Updates:__
* `gk/gravityview/common/quotation-marks` filter to modify the quotation marks used for exact-match searches.
* `gk/gravityview/search/number-range/step` filter to adjust the interval between numbers in input fields for range-based searches.

= 2.21.2 on March 28, 2024 =

This update fixes an issue with previewing GravityView blocks for Views with enhanced security and resolves a problem where blocks were previously rendered only for logged-in users.

#### 🐛 Fixed
* Previewing a GravityView block for a View that has enhanced security enabled no longer results in a notice about a missing `secret` shortcode attribute.
* GravityView blocks now render for all users, not just those who are logged in.

= 2.21.1 on March 22, 2024 =

This hotfix release addresses a critical error that occurred when activating the plugin without Gravity Forms installed.

#### 🐛 Fixed
* Critical error when activating the plugin without Gravity Forms installed.

= 2.21 on March 18, 2024 =

This release enhances security, introduces support for LifterLMS, adds a new CSV/TSV export widget to the View editor along with the option to add Gravity Flow fields to the Search Bar, addresses PHP 8.2 deprecation notices, fixes a conflict with BuddyBoss Platform, and improves performance with updates to essential components.

#### 🚀 Added
* A View editor widget to export entries in CSV or TSV formats.
* Support for SVG images.
* Support for Gravity Flow's "Workflow User" and "Workflow Multi-User" fields inside the Search Bar.
* Integration with LifterLMS that allows embedding Views inside Student Dashboards.
* Notice to inform administrators that an embedded View was moved to "trash" and an option to restore it.
* Click-to-copy shortcode functionality in the View editor and when listing existing Views.

#### 🐛 Fixed
* PHP 8.2 deprecation notices.
* Fields linked to single entry layouts are now exported as plain text values, not hyperlinks, in CSV/TSV files.
* Issue preventing the saving of pages/posts with GravityView Gutenberg blocks when BuddyBoss Platform is active.

#### 🔐 Security
* Enhanced security by adding a `secret` attribute to shortcodes and blocks connected to Views.

#### 🔧 Updated
* [Foundation](https://www.gravitykit.com/foundation/) to version 1.2.11.
  - GravityKit product updates are now showing on the Plugins page.
  - Database options that are no longer used are now automatically removed.

* Added: You can now search exact-match phrases by wrapping a search term in quotes (e.g., `"blue motorcycle"`). This will search for text exactly matching `"blue motorcycle"`)

__Developer Updates:__

* Added: `gk/gravityview/widget/search/clear-button/params` filter to modify the parameters of the Clear button in the search widget.

= 2.20.2 on March 4, 2024 =

This release enhances performance by optimizing caching and managing transients more effectively.

#### ✨ Improved
* Enhanced detection of duplicate queries, resulting in fewer cache records stored in the database.

#### 🔧 Updated
* Updated [Foundation](https://www.gravitykit.com/foundation/) to version 1.2.10.
  - Transients are no longer autoloaded.

= 2.20.1 on February 29, 2024 =

This release fixes an issue with View caching and improves compatibility with the Advanced Custom Fields plugin.

#### 🐛 Fixed
* Disappearing pagination and incorrect entry count when View caching is enabled.
* Potential timeout issue when embedding GravityView shortcodes with Advanced Custom Fields plugin.
* PHP 8.1+ deprecation notice.

= 2.20 on February 22, 2024 =

This release introduces new settings for better control over View caching, adds support for the Advanced Post Creation Add-On when editing entries, fixes a fatal error when exporting entries to CSV, and updates internal components for better performance and compatibility.

#### 🚀 Added
* Global and View-specific settings to control caching of View entries. [Learn more about GravityView caching](https://docs.gravitykit.com/article/58-about-gravityview-caching).
* Support for the [Advanced Post Creation Add-On](https://www.gravityforms.com/add-ons/advanced-post-creation/) when editing entries in GravityView's Edit Entry mode.

#### ✨ Improved
* If Gravity Forms is not installed and/or activated, a notice is displayed to alert users when creating new or listing existing Views.

#### 🐛 Fixed
* Deprecation notice in PHP 8.1+ when displaying a View with file upload fields.
* Fatal error when exporting entries to CSV.

#### 🔧 Updated
* [Foundation](https://www.gravitykit.com/foundation/) to version 1.2.9.
  - GravityKit products that are already installed can now be activated without a valid license.
  - Fixed PHP warning messages that appeared when deactivating the last active product with Foundation installed.

#### 🐛 Fixed
* The GravityView capabilities for a specific role were overwritten on every admin request.

= 2.19.6 on February 7, 2024 =

This update introduces the ability to send notifications using Gravity Forms when an entry is deleted, improves sorting and survey field ratings, and updates key components for better performance and compatibility.

#### 🚀 Added
* Ability to send notifications using Gravity Forms when an entry is deleted by selecting the "GravityView - Entry is deleted" event from the event dropdown in Gravity Forms notifications settings.

#### 🐛 Fixed
* Sorting the View by entry ID in ascending and descending order would yield the same result.
* Survey fields without a rating would show a 1-star rating.
* Editing Gravity Forms [Custom Post Fields](https://docs.gravityforms.com/post-custom/#h-general-settings) with a Field Type set to "File Uploads" inside in Edit Entry.

#### 🔧 Updated
* [Foundation](https://www.gravitykit.com/foundation/) and [TrustedLogin](https://www.trustedlogin.com/) to versions 1.2.8 and 1.7.0, respectively.
  - Transients are now set and retrieved correctly when using object cache plugins.
  - Fixed a JavaScript warning that occurred when deactivating license keys and when viewing products without the necessary permissions.
  - Resolved PHP warning messages on the Plugins page.

__Developer Updates:__

* Added: `GravityView_Notifications` class as a wrapper for Gravity Forms notifications.
* Modified: Added the current `\GV\View` object as a second parameter for the `gravityview/search-all-split-words` and `gravityview/search-trim-input` filters.
* Modified: Attach listeners in the View editor to `$( document.body )` instead of `$('body')` for speed improvements.

= 2.19.5 on December 7, 2023 =

* Fixed: PHP 8.1+ deprecation notice when editing an entry with the Gravity Forms User Registration add-on enabled
* Updated: [Foundation](https://www.gravitykit.com/foundation/) to version 1.2.6

= 2.19.4 on November 2, 2023 =

* Improved: View editor performance, especially with Views with a large number of fields
* Improved: "Link to Edit Entry," "Link to Single Entry," and "Delete Entry" fields are now more easily accessible at the top of the field picker in the View editor
* Fixed: PHP 8.1+ deprecation notice

= 2.19.3 on October 25, 2023 =

* Fixed: Using merge tags as values for search and start/end date override settings was not working in Views embedded as a field
* Fixed: Deprecation notice in PHP 8.2+

= 2.19.2 on October 19, 2023 =

* Fixed: Merge tags were still not working in the Custom Content field after the fix in 2.19.1

= 2.19.1 on October 17, 2023 =

* Fixed: PHP 8+ deprecation notice appearing on 404 pages
* Fixed: Merge tags not working in the Custom Content field
* Improved: PHP 8.1 compatibility

= 2.19 on October 12, 2023 =

* Added: Embed a Gravity Forms form using a field in the View editor
* Added: Embed a GravityView View using a field in the View editor
* Added: New Custom Code tab in the View Setting metabox to add custom CSS and JavaScript to the View
* Fixed: Appearance of HTML tables nested within View fields, including Gravity Forms Survey Add-On fields
* Fixed: Clicking the "?" tooltip icon would not go to the article if the Support Port is disabled
* Tweak: Improved Chained Select field output when the Chained Select Add-On is disabled
* Updated: [Foundation](https://www.gravitykit.com/foundation/) to version 1.2.5

__Developer Updates:__

* Added: Entries submitted using the new Gravity Forms Field will have `gk_parent_entry_id` and `gk_parent_form_id` entry meta added to them to better support connecting Views

= 2.18.7 on September 21, 2023 =

* Added: Support for embedding Views inside [WooCommerce Account Pages](https://iconicwp.com/products/woocommerce-account-pages/)
* Improved: `[gvlogic]` shortcode now works with the [Dashboard Views](https://github.com/GravityKit/Dashboard-Views) add-on in the WordPress admin area
* Fixed: The Recent Entries widget results would be affected when browsing a View: the search query, page number, and sorting would affect the displayed entries
* Fixed: Activation of View types (e.g., Maps, DataTables) would fail in the View editor
* Fixed: Image preview (file upload field) not working if the file is uploaded to Dropbox using the Gravity Forms Dropbox add-on
* Updated: [Foundation](https://www.gravitykit.com/foundation/) to version 1.2.4

__Developer Updates:__

* Added: `gk/gravityview/approve-link/return-url` filter to modify the return URL after entry approval
* Added: Second parameter to the `GravityView_Fields::get_all()` method to allow for filtering by context
* Improved: Added third argument to `gravityview_get_connected_views()` to prevent including joined forms in the search
* Implemented: The `GravityView_Field::$contexts` property is now respected; if defined, fields that are not in a supported context will not render

= 2.18.6 on September 7, 2023 =

* Improved: Introduced a gear icon to the editor tabs that brings you directly to the Settings metabox
* Improved: Support for RTL languages
* Updated: [Foundation](https://www.gravitykit.com/foundation/) to version 1.2.2

= 2.18.5 on September 1, 2023 =

* Fixed: Fatal error caused by GravityView version 2.18.4

= 2.18.4 on August 31, 2023 =

* Added: A "Direct Access" summary in the Publish box in the View editor that makes it easy to see and modify whether a View is accessible directly
* Improved: Views will now remember the Settings tab you are on after you save a View
* Fixed: Resolved a fatal error that occurred under certain circumstances due to passing the wrong parameter type to a WordPress function
* Updated: The video on the Getting Started page
* Updated: [Foundation](https://www.gravitykit.com/foundation/) to version 1.2

= 2.18.3 on July 20, 2023 =

* Fixed: Incorrect total entry count and hidden pagination when View contains an Entry Edit field

= 2.18.2 on July 12, 2023 =

* Fixed: Performance issue
* Fixed: [WP-CLI](https://wp-cli.org/) not displaying available GravityKit product updates
* Updated: [Foundation](https://www.gravitykit.com/foundation/) to version 1.1.1

__Developer Notes:__

* Added: `gk/gravityview/view/entries/cache` filter to provide control over the caching of View entries (default: `true`)

= 2.18.1 on June 20, 2023 =

* Fixed: PHP warning message that appeared when attempting to edit a View

= 2.18 on June 20, 2023 =

* Fixed: Issue where "Edit Entry" link was not appearing under the Single Entry layout when the View was filtered using the "Created By" criterion with the "{user:ID}" merge tag
* Fixed: REST API response breaking the functionality of Maps Layout 2.0
* Updated: [Foundation](https://www.gravitykit.com/foundation/) to version 1.1

__Developer Notes:__

* Deprecated: `get_gravityview()` and the `the_gravityview()` global functions
* Added: `GravityView_Field_Delete_Link` class to render the Delete Entry link instead of relying on filtering
	- `delete_link` will now be properly returned in the `GravityView_Fields::get_all('gravityview');` response

= 2.17.8 on May 16, 2023 =

* Improved: Performance when using Gravity Forms 2.6.9 or older
* Improved: Form ID now appears beside the form title for easier data source selection in the View editor
* Fixed: Fatal error when adding a GravityView block in Gutenberg editor
* Fixed: Error when activating an installed but deactivated View type (e.g., Maps) from within the View editor
* Fixed: File Upload fields may incorrectly show empty values

__Developer Notes:__

* Added: `gk/gravityview/metaboxes/data-source/order-by` filter to modify the default sorting order of forms in the View editor's data source dropdown menu (default: `title`)
* Added: `gk/gravityview/renderer/should-display-configuration-notice` filter to control the display of View configuration notices (default: `true`)

= 2.17.7 on May 4, 2023 =

* Fixed: Fatal error when using the Radio input types in the Search Bar (introduced in 2.17.6)

= 2.17.6 on May 3, 2023 =

* Added: Filter entries by payment status using a drop-down, radio, multi-select, or checkbox inputs in the Search Bar (previously, only searchable using a text input)
* Modified: Added "(Inactive)" suffix to inactive forms in the Data Source dropdown
* Fixed: Incompatibility with some plugins/themes that use Laravel components
* Fixed: Appearance of Likert survey fields when using Gravity Forms Survey Add-On Version 3.8 or newer
* Fixed: Appearance of the Poll widget when using Gravity Forms Poll Add-On Version 4.0 or newer
* Fixed: `[gvlogic]` not working when embedded in a Post or Page
* Fixed: `[gvlogic if="context" is="multiple"]` not working when a View is embedded
* Fixed: Consent field always showing checked status when there are two or more Consent fields in the form
* Fixed: Selecting all entries on the Entries page would not properly apply all the search filters

__Developer Notes:__

* Added: `gk/gravityview/common/get_forms` filter to modify the forms returned by `GVCommon::get_forms()`
* Modified: Removed `.hidden` from compiled CSS files to prevent potential conflicts with other plugins/themes (use `.gv-hidden` instead)
* Modified: Added `gvlogic`-related shortcodes to the `no_texturize_shortcodes` array to prevent shortcode attributes from being encoding
* Modified: Updated Gravity Forms CSS file locations for the Survey, Poll, and Quiz Add-Ons
* Modified: Likert survey responses are now wrapped in `div.gform-settings__content.gform-settings-panel__content` to match the Gravity Forms Survey Add-On 3.8 appearance
* Fixed: Properly suppress PHP warnings when calling `GFCommon::gv_vars()` in the Edit View screen
* Updated: [Foundation](https://www.gravitykit.com/foundation/) to version 1.0.12
* Updated: TrustedLogin to version 1.5.1

= 2.17.5 on April 12, 2023 =

* Fixed: Do not modify the Single Entry title when the "Prevent Direct Access" setting is enabled for a View
* Fixed: Fatal error when performing a translations scan with the WPML plugin

= 2.17.4 on April 7, 2023 =

* Fixed: Fatal error rendering some Maps Layout Views introduced in 2.17.2
* Fixed: When a View is embedded multiple times on the same page, Edit Entry, Delete Entry, and Duplicate Entry links could be hidden after the first View
* Fixed: "The Single Entry layout has not been configured" notice shows when embedding a View into another View's Single Entry page using a Custom Content field

= 2.17.3 on April 6, 2023 =

* Fixed: Fatal error rendering multiple Views on the same page/post introduced in 2.17.2

__Developer Updates:__

* Added: A `$context` argument of `\GV\Template_Context` is now passed to `\GV\Widget\pre_render_frontend()`

= 2.17.2 on April 5, 2023 =

**Note: GravityView now requires Gravity Forms 2.5.1 or newer**

* Added: "No Entries Behavior" option to hide the View when there are no entries visible to the current user (not applied to search results)
* Fixed: Performance issue introduced in 2.17 that resulted in a large number of queries
* Fixed: PHP 8+ fatal error when displaying connected Views in the Gravity Forms form editor or forms list
* Fixed: PHP 8+ warning messages when creating a new View
* Fixed: PHP warning when a View checks for the ability to edit an entry that has just been deleted using code
* Fixed: On sites running the GiveWP plugin, the View Editor would look bad
* Updated: [Foundation](https://www.gravitykit.com/foundation/) to version 1.0.11

__Developer Updates:__

* Added: View blocks are also parsed when running `\GV\View_Collection::from_content()`
* Added: New filter, to be used by Multiple Forms extension: `gravityview/view/get_entries/should_apply_legacy_join_is_approved_query_conditions`
* Modified: `gravityview()->views->get()` now parses the content of the global `$post` object and will detect View shortcodes or blocks stored in the `$post->post_content`
* Modified: `gravityview()->views->get()` now may return a `GV\View_Collection` object when it detects multiple Views in the content
* Updated: HTML tags that had used `.hidden` now use the `.gv-hidden` CSS class to prevent potential conflicts with other plugins/themes

= 2.17.1 on February 20, 2023 =

* Updated: [Foundation](https://www.gravitykit.com/foundation/) to version 1.0.9

= 2.17 on February 13, 2023 =

**Note: GravityView now requires PHP 7.2 or newer**

* It's faster than ever to create a new View! (Table and DataTables View types only)
	- Fields configured in the [Gravity Forms Entry Columns](https://docs.gravityforms.com/entries/#h-entry-columns) are added to the Multiple Entries layout
	- The first field in the Multiple Entries layout is linked to the Single Entry layout
	- All form fields are added to the Single Entry layout
	- An Edit Entry Link field is added to the bottom of the Single Entry layout
* Added: New "No Entries Behavior" setting: when a View has no entries visible to the current user, you can now choose to display a message, show a Gravity Forms form, or redirect to a URL
* Modified: The field picker now uses Gravity Forms field icons
* Fixed: ["Pre-filter choices"](https://docs.gravitykit.com/article/701-show-choices-that-exist) Search Bar setting not working for Address fields
* Fixed: `[gventry]` shortcode not working the Entry ID is set to "first" or "last"
* Fixed: Fatal error when using the Gravity Forms Survey Add-On
* Tweak: The field picker in the View editor now uses Gravity Forms field icons

__Developer Updates:__

* Modified: If you use the `gravityview/template/text/no_entries` or `gravitview_no_entries_text` filters, the output is now passed through the `wpautop()` function prior to applying the filters, not after
	* Added `$unformatted_output` parameter to the `gravityview/template/text/no_entries` filter to return the original value before being passed through `wpautop()`
* Modified: Container classes for no results output change based on the "No Entries Behavior" setting:
	- `.gv-no-results.gv-no-results-text` when set to "Show a Message"
	- `.gv-no-results.gv-no-results-form` when set to "Display a Form"
	- Updated `templates/views/list/list-body.php`, `templates/views/table/table-body.php`
* Added: `$form_id` parameter to `gravityview_get_directory_fields()` function and `GVCommon::get_directory_fields()` method

= 2.16.6 on January 12, 2023 =

* Fixed: Fatal error due to an uncaught PHP exception
* Fixed: It was not possible to select any content inside the field settings window in the View editor

= 2.16.5 on January 5, 2023 =

* Updated: [Foundation](https://www.gravitykit.com/foundation/) to version 1.0.8
* Improved: Internal changes to allow using Custom Content fields on the Edit Screen with the [DIY Layout](https://www.gravitykit.com/extensions/diy-layout/)

= 2.16.4 on December 23, 2022 =

* Fixed: Prevent possible conflict in the View editor with themes/plugins that use Bootstrap's tooltip library

= 2.16.3 on December 21, 2022 =

* Fixed: Caching wouldn't always clear when an entry was added or modified
* Fixed: Fatal error on some hosts due to a conflict with one of the plugin dependencies (psr/log)
* Fixed: PHP 8.1 notices
* Fixed: View scripts and styles not loading for some logged-in users

= 2.16.2 on December 14, 2022 =

* Fixed: Views would take an abnormally long time to load
* Fixed: Fatal error on some hosts that use weak security keys and salts

= 2.16.1 on December 7, 2022 =

* Fixed: Date picker and other JavaScript not working on the Edit Entry screen
* Fixed: JavaScript error preventing the Search Bar widget properties from opening when creating a new View
* Fixed: CodeMirror editor initializing multiple times when opening the custom content field properties in the View
* Fixed: Secure download link for the file upload field was not showing the file name as the link text
* Fixed: The saved View would not recognize fields added from a joined form when using the [Multiple Forms](https://www.gravitykit.com/extensions/multiple-forms/) extension

= 2.16.0.4 on December 2, 2022 =

* Fixed: Incompatibility with some plugins/themes that could result in a blank WordPress Dashboard

= 2.16.0.3 on December 2, 2022 =

* Fixed: Fatal error when downloading plugin translations

= 2.16.0.2 on December 1, 2022 =

* Fixed: Fatal error when Maps isn't installed

= 2.16.0.1 on December 1, 2022 =

* Fixed: Admin menu not expanded when on a GravityView page

= 2.16 on December 1, 2022 =

* Added: New WordPress admin menu where you can now centrally manage all your GravityKit product licenses and settings ([learn more about the new GravityKit menu](https://www.gravitykit.com/foundation/))
    - Go to the WordPress sidebar and check out the GravityKit menu!
    - We have automatically migrated your existing licenses and settings, which were previously entered in the Views→Settings page
    - Request support using the "Grant Support Access" menu item
* Added: Support for defining `alt` text in File Upload fields
* Added: "Pre-Filter Choices" Search Bar setting will only display choices that exist in submitted entries ([learn more about Pre-Filter Choices](https://docs.gravitykit.com/article/701-s))
* Improved: When creating a new View, it is now possible to install a View type (if included in the license) straight from the View editor
* Improved: Reduce the number of queries when displaying a View
* Improved: The Edit View screen loads faster
* Fixed: Merge Tags were not processed inside Custom Content fields when using the [`[gventry]` edit mode](https://docs.gravitykit.com/article/463-gventry-shortcode)
* Fixed: Gravity Forms poll results was not being refreshed after editing a Poll field in GravityView Edit Entry
* Fixed: Survey field "Rating" stars were not displaying properly in the frontend
* Fixed: JavaScript error when creating a new View
* Fixed: JavaScript error when opening field settings in a new View
* Fixed: Merge Tag picker not initializing when changing View type for an existing View
* Fixed: "Field connected to XYZ field was deleted from the form" notice when adding a new field to a View created from a form preset
* Fixed: Edit Entry may partially save changes if form fields have conditional logic; thanks, Jurriaan!
* Fixed: View presets not working
* Fixed: "This View is configured using the View type, which is disabled" notice when creating a new View after activating or installing a View type (e.g., Maps, DIY, DataTables)
* Fixed: Incorrect search mode is set when one of the View search widget fields uses a "date range" input type
* Fixed: Multiple files upload error (e.g., when editing an entry using GravityEdit)

__Developer Updates:__

* Added: `gravityview/template/field/survey/rating/before` filter that fires before the Survey field rating stars markup
* Added: `$return_view` parameter to `\GV\Request::is_view()` method, reducing the need to build a \GV\View object when simply checking if a request is a View
* Added: `$expiration` parameter to `GravityView_Cache::set()` method to allow for different cache lifetimes
* Fixed: `GravityView_Cache` was not used when the `WP_DEBUG` constant was set to `true`. This resulted in the cache being effectively disabled on many sites.
	- Improved: Only run `GravityView_Cache::use_cache()` once per request
	- Added: `GRAVITYVIEW_DISABLE_CACHE` constant to disable the cache. Note: `gravityview_use_cache` filter will still be run.

= 2.15 on September 21, 2022 =

* Added: Entire View contents are wrapped in a container, allowing for better styling ([learn about, and how to modify, the container](https://docs.gravitykit.com/article/867-modifying-the-view-container-div))
* Added: When submitting a search form, the page will scroll to the search form
* Modified: Select and Multiselect search inputs will now use the connected field's "Placeholder" values, if defined in Gravity Forms ([read about Search Bar placeholders](https://docs.gravitykit.com/article/866-search-bar-placeholder))
* Improved: Date comparisons when using `[gvlogic]` with `greater_than` or `less_than` comparisons
* Fixed: Reduced the number of database queries to render a View, especially when using Custom Content, Entry Link, Edit Link, and Delete Link fields
* Fixed: Removed the Gravity Forms Partial Entries Add-On privacy notice when using Edit Entry because auto-saving in Edit Entry is not supported
* Fixed: The "entry approval is changed" notification, if configured, was being sent for new form submissions
* Fixed: Views would not render in PHP 8.1
* Fixed: Multiple PHP 8 and PHP 8.1 warnings

__Developer Updates:__

* Added: `gravityview/widget/search/append_view_id_anchor` filter to control appending the unique View anchor ID to the search URL (enabled by default)
* Added: `gravityview/view/wrapper_container` filter to wrap to optionally wrap the View in a container (enabled by default) — [see examples of modifying the container](https://docs.gravitykit.com/article/867-modifying-the-view-container-div)
* Added: `gravityview/view/anchor_id` filter to control the unique View anchor ID
* Modified the following template files:
	- `includes/widgets/search-widget/templates/search-field-multiselect.php`
	- `includes/widgets/search-widget/templates/search-field-select.php`
	- `templates/views/list.php`
	- `templates/views/table.php`
	- `templates/fields/field-custom.php`
	- `templates/fields/field-duplicate_link-html.php`
	- `templates/fields/field-delete_link-html.php`
	- `templates/fields/field-edit_link-html.php`
	- `templates/fields/field-entry_link-html.php`
	- `templates/fields/field-website-html.php`
	- `templates/deprecated/fields/custom.php`
	- `templates/deprecated/fields/website.php`

= 2.14.7 on July 31, 2022 =

* Fixed: GravityView plugin updates were not shown in the plugin update screen since version 2.14.4 (April 27, 2022)

= 2.14.6 on May 27, 2022 =

* [GravityView (the company) is now GravityKit!](https://www.gravitykit.com/rebrand/)
* Fixed: Embedding Edit Entry context directly in a page/post using the `[gventry edit="1"]` shortcode ([learn more](https://docs.gravitykit.com/article/463-gventry-shortcode))
* Fixed: Edit Entry link wasn't working in the Single Entry context of an embedded View
* Fixed: Search Bar GravityView widget was not saving the chosen fields
* Fixed: Gravity PDF shortcodes would not be processed when bulk-approving entries using GravityView. Thanks, Jake!
* Fixed: Sometimes embedding a GravityView shortcode in the block editor could cause a fatal error
* Fixed: Multiple PHP 8 warnings

__Developer Updates:__

* Added: `redirect_url` parameter to the `gravityview/edit_entry/success` filter
* Added `redirect_url` and `back_link` parameters to the `gravityview/shortcodes/gventry/edit/success` filter

= 2.14.5 on May 4, 2022 =

* Added: A link that allows administrators to disable the "Show only approved entries" View setting from the front-end
* Fixed: Configuring new Search Bar WordPress widgets wasn't working in WordPress 5.8+
* Fixed: Styling of form settings dropdowns on the Gravity Forms "Forms" page

= 2.14.4 on April 27, 2022 =

* Added: Search Bar support for the [Chained Selects](https://www.gravityforms.com/add-ons/chained-selects/) field type
* Improved: Plugin updater script now supports auto-updates and better supports multisite installations
* Improved: If a View does not support joined forms, log as a notice, not an error
* Fixed: Merge Tag picker behavior when using Gravity Forms 2.6
* Fixed: Deleting a file when editing an entry as a non-administrator user on Gravity Forms 2.6.1 results in a server error
* Fixed: When The Events Calendar Pro plugin is active, Views became un-editable
* Tweak: Additional translation strings related to View editing

Note: We will be requiring Gravity Forms 2.5 and WordPress 5.3 in the near future; please upgrade!

__Developer Updates:__

* Added: Search URLs now support `input_{field ID}` formats as well as `filter_{field ID}`; the following will both be treated the same:
	- `/view/example/?filter_3=SEARCH`
	- `/view/example/?input_3=SEARCH`
* Added: In the admin, CSS classes are now added to the `body` tag based on Gravity Forms version. See `GravityView_Admin_Views::add_gf_version_css_class()`
* Modified: Allow non-admin users with "edit entry" permissions to delete uploaded files
* Updated: EDD_SL_Plugin_Updater script to version 1.9.1

= 2.14.3 on March 24, 2022 =

* Added: Support for displaying WebP images
* Improved: Internal logging of notices and errors
* Fixed: Images hosted on Dropbox sometimes would not display properly on the Safari browser. Thanks, Kevin M. Dean!

__Developer Updates:__

* Added: `GravityView_Image::get_image_extensions()` static method to fetch full list of extension types interpreted as images by GravityView.
* Added: `webp` as a valid image extension

= 2.14.2.1 on March 11, 2022 =

* Fixed: Empty values in search widget fields may return incorrect results

__Developer Updates:__

* Added: `gravityview/search/ignore-empty-values` filter to control strict matching of empty field values

= 2.14.2 on March 10, 2022 =

* Fixed: Potential fatal error on PHP 8 when exporting View entries in CSV and TSV formats
* Fixed: Search widget would cause a fatal error when the Number field is used with the "is" operator
* Fixed: Search widget returning incorrect results when a field value is blank and the operator is set to "is"
* Fixed: Gravity Forms widget icon not showing
* Fixed: Gravity Forms widget not displaying available forms when the View is saved

= 2.14.1 on January 25, 2022 =

* Tested with WordPress 5.9
* Improved: The [Members plugin](https://wordpress.org/plugins/members/) now works with No-Conflict Mode enabled
* Improved: Performance when saving Views with many fields
* Improved: Performance when loading the Edit View screen when a View has many fields
* Fixed: Gravity Forms widget used in the View editor would initialize on all admin pages
* Fixed: PHP notice when editing an entry in Gravity Forms that was created by user that no longer exists
* Fixed: Error activating on sites that use the Danish language
* Fixed: Entry approval scripts not loading properly when using Full Site Editing themes in WordPress 5.9
* Updated: TrustedLogin client to Version 1.2, which now supports logins for WordPress Multisite installations
* Updated: Polish translation. Thanks, Dariusz!

__Developer Updates:__

* Modified: Refactored drag & drop in the View editor to improve performance: we only initialize drag & drop on the active tab instead of globally.
	* Added: `gravityview/tab-ready` jQuery trigger to `body` when each GravityView tab is ready (drag & drop initialized). [See example of binding to this event](https://gist.github.com/zackkatz/a2844e9f6b68879e79ba7d6f66ba0850).

= 2.14.0.1 on December 30, 2021 =

Fixed: Deprecated filter message when adding fields to the View

= 2.14 on December 21, 2021 =

This would be a minor version update (2.13.5), except that we renamed many functions. See "Developer Updates" for this release below.

* Added: `{is_starred}` Merge Tag. [Learn more about using `{is_starred}`](https://docs.gravitykit.com/article/820-the-isstarred-merge-tag)
* Fixed: Media files uploaded to Dropbox were not properly embedded
* Fixed: JavaScript error when trying to edit entry's creator
* Fixed: Recent Entries widget would cause a fatal error on WP 5.8 or newer
* Fixed: When using Multiple Forms, editing an entry in a joined form now works properly if the "Edit Entry" tab has not been configured
* Fixed: View settings not hiding automatically on page load

__Developer Updates:__

We renamed all instances of `blacklist` to `blocklist` and `whitelist` to `allowlist`. All methods and filters have been deprecated using `apply_filters_deprecated()` and `_deprecated_function()`. [See a complete list of modified methods and filters](https://docs.gravitykit.com/article/816-renamed-filters-methods-in-2-14).

= 2.13.4 on November 4, 2021 =

* Fixed: View scripts and styles would not load when manually outputting the contents of the `[gravityview]` shortcode

__Developer Updates:__

* Added: `gravityview/shortcode/before-processing` action that runs before the GravityView shortcode is processed
* Added: `gravityview/edit_entry/cancel_onclick` filter to modify the "Back" link `onclick` HTML attribute
	- Modified: `/includes/extensions/edit-entry/partials/form-buttons.php` file to add the filter

= 2.13.3 on October 14, 2021 =

* Fixed: Edit Entry would not accept zero as a value for a Number field marked as required
* Modified: Refined the capabilities assigned to GravityView support when access is granted using TrustedLogin. Now our support will be able to debug theme-related issues and use the [Code Snippets](https://wordpress.org/plugins/code-snippets/) plugin.

= 2.13.2 on October 7, 2021 =

* Fixed: Entry Approval not working when using DataTables in responsive mode (requires DataTables 2.4.9 or newer).

__Developer Updates:__

* Updated: Upgraded to [Fancybox 4](https://fancyapps.com/docs/ui/fancybox).
* Updated: [TrustedLogin Client](https://github.com/trustedlogin/client) to Version 1.0.2.
* Modified: Added Code Snippets CSS file to No Conflict allow list.
* Modified: Moved internal (but public) method `GravityView_Admin_ApproveEntries::process_bulk_action` to new `GravityView_Bulk_Actions` class.

= 2.13.1 on September 27, 2021 =

* Improved: Views now load faster due to improved template caching.
* Added: Ability to configure an "Admin Label" for Custom Content widgets. This makes it easier to see your widget configuration a glance.
* Fixed: Issue where non-support users may see a "Revoke TrustedLogin" admin bar link.

= 2.13 on September 23, 2021 =

* Added: Integrated with TrustedLogin, the easiest & most secure way to grant access to your website. [Learn more about TrustedLogin](https://www.trustedlogin.com/about/easy-and-safe/).
	- Need to share access with support? Click the new "Grant Support Access" link in the "Views" menu.

= 2.12.1 on September 1, 2021 =

* Fixed: The Gravity Forms widget in the View editor would always use the source form of the View
* Fixed: The field picker didn't use available translations
* Fixed: Importing [exported Views](https://docs.gravitykit.com/article/119-importing-and-exporting-configured-views) failed when Custom Content or [DIY Layout](https://www.gravitykit.com/extensions/diy-layout/) fields included line breaks.
* Fixed: When first installing GravityView, the message was for an invalid license instead of inactive.
* Fixed: The "Affiliate ID" setting would not toggle properly when loading GravityView settings. [P.S. — Become an affiliate and earn money referring GravityView!](https://www.gravitykit.com/account/affiliates/#about-the-program)
* Tweak: Changed the icon of the Presets preview

= 2.12 on July 29, 2021 =

* Fixed: Add latest Yoast SEO scripts to the No-Conflict approved list
* Fixed: Updating an entry with a multi-file upload field may erase existing contents when using Gravity Forms 2.5.8

= 2.11 on July 15, 2021 =

* Added: Settings to customize "Update", "Cancel", and "Delete" button text in Edit Entry
* Improved: Much better Gravity Forms Survey Add-On integration! [Learn more in the release announcement](https://www.gravitykit.com/gravityview-2-11/).
	- Ratings can be displayed as text or stars
	- Multi-row Likert fields can be shown as Text or Score
	- Improved display of a single row from a multi-row Likert field
	- Single checkbox inputs are now supported
* Improved: Search widget clear/reset button behavior
* Improved: Allow unassigning an entry's Entry Creator when editing an entry
* Improved: When editing an entry, clicking the "Cancel" button will take you to the prior browser page rather than a specific URL
* Improved: Conditionally update "Clear Search" button text in the Search Bar
* Fixed: When Time fields were submitted with a single `0` for hour and minute inputs, instead of displaying midnight (`0:0`), it would display the current time
* Fixed: Delete Entry links did not work when custom entry slugs were enabled
* Fixed: Editing an entry in Gravity Forms that was created by a logged-out user forced an entry to be assigned to a user
* Fixed: Missing download/delete icons for file upload field in Edit Entry when running Gravity Forms ≥ 2.5.6.4
* Fixed: A broken German translation file caused a fatal error (only for the `de_DE` localization)
* Updated: Dutch translation (thanks René S.!) and German translation (thanks Aleksander K-W.!)

__Developer Updates:__

* Added: `gravityview/template/field/survey/glue` filter to modify how the multi-row Likert field values are combined. Default: `; `
* Modified: `templates/deprecated/fields/time.php` and `templates/fields/field-time-html.php` to include the commented `strtotime()` check
* Modified: `includes/extensions/edit-entry/partials/form-buttons.php` to add Cancel button enhancements
* Fixed: `gravityview/search/sieve_choices` didn't filter by Created By
* Fixed: `\GV\Utils::get()` didn't properly support properties available using PHP magic methods. Now supports overriding using the `__isset()` magic method.
* Updated: EDD auto-updates library to version 1.8

= 2.10.3.2 on June 2, 2021 =

* Improved: Loading of plugin dependencies
* Fixed: Field's required attribute was ignored in certain scenarios when using Edit Entry

= 2.10.3.1 on May 27, 2021 =

* Fixed: The "delete file" button was transparent in Edit Entry when running Gravity Forms 2.5 or newer
* Security enhancements

= 2.10.3 on May 20, 2021 =

* Added: Support for the [All in One SEO](https://wordpress.org/plugins/all-in-one-seo-pack/) plugin
* Fixed: GravityView styles and scripts not loading when embedding View as a block shortcode in GeneratePress
* Fixed: PHP notice appearing when a translation file is not available for the chosen locale
* Fixed: Search clear button disappearing when using GravityView Maps layout

__Developer Updates:__

* Added: `gravityview/fields/custom/form` filter to modify form used as the source for View entries
* Added: `gravityview/fields/custom/entry` filter to modify entry being displayed

= 2.10.2.2 on April 19, 2021 =

* Improved: Previous fix for an issue that affected HTML rendering of some posts and pages

= 2.10.2.1 on April 13, 2021 =

* Fixed: Issue introduced in Version 2.10.2 that affected HTML rendering of some posts and pages
* Fixed: Undefined function error for sites running WordPress 4.x introduced in Version 2.10.2

= 2.10.2 on April 12, 2021 =

* Fixed: Using the GravityView shortcode inside a [reusable block](https://wordpress.org/news/2021/02/gutenberg-tutorial-reusable-blocks/) in the WordPress Editor would prevent CSS and JavaScript from loading
* Fixed: "Open in new tab/window" checkbox is missing from Link to Single Entry and Link to Edit Entry links
* Fixed: Searching while on a paginated search result fails; it shows no entries because the page number isn't removed
* Fixed: Sorting by Entry ID resulted in a MySQL error

= 2.10.1 on March 31, 2021 =

* Added: Allow comparing multiple values when using `[gvlogic]` shortcode
	- Use `&&` to match all values `[gvlogic if="abc" contains="a&&b"]`
	- Use `||` to match any values `[gvlogic if="abc" equals="abc||efg"]`
* Added: `{site_url}` Merge Tag that returns the current site URL. This can be helpful when migrating sites or deploying from staging to live.
* Fixed: Paragraph fields have a "Link to single entry" field setting, even though it doesn't make sense
* Fixed: PDF and Text files were not opened in a lightbox
* Fixed: Show File Upload files as links if they aren't an image, audio, or video file (like a .zip, .txt, or .pdf file)
* Fixed: Lightbox script was being loaded for Views even if it was not being used
* Fixed: Don't show the icon for the "Source URL" field in the View editor
* Fixed: Change Entry Creator not working properly on non-English sites
* Updated _so many translations_! Thank you to all the translators!
	- Arabic translation (thanks Salman!)
	- Dutch translation (thanks Desiree!)
	- Russian translation (thanks Victor S.!)
	- Romanian (thanks Cazare!)
	- Chinese (thanks Edi Weigh!)
	- Turkish (thanks Süha!)
	- Swedish (thanks Adam!)
	- Portuguese (thanks Luis and Rafael!)
	- Dutch (thanks Erik!)
	- Norwegian (thanks Aleksander!)
	- Italian (thanks Clara!)
	- Hungarian (thanks dbalage!)
	- Hebrew
	- French
	- Canadian French (thanks Nicolas!)
	- Finnish (thanks Jari!)
	- Iranian (thanks amir!)
	- Mexican Spanish (thanks Luis!)
	- Spanish (thanks Joaquin!)
	- German (thanks Hubert!)
	- Danish (thanks Lisbeth!)
	- Bosnian (thanks Damir!)
	- Bengali (thanks Akter!)

= 2.10 on March 9, 2021 =

* A beautiful visual refresh for the View editor!
	- Brand new field picker for more easily creating your View
	- Visually see when Single Entry and Edit Entry layouts haven't been configured
	- See at a glance which fields link to Single Entry and Edit Entry
	- Manage and activate layouts from the View editor
	- Added: Show a notice when "Show only approve entries" setting is enabled for a View and no entries are displayed because of the setting
	- Added: Custom Content now supports syntax highlighting, making it much easier to write HTML (to disable, click on the Users sidebar menu, select Profile. Check the box labeled "Disable syntax highlighting when editing code" and save your profile)
	- Added: Warning when leaving Edit View screen if there are unsaved changes
	- Added: See the details of the current field while configuring field settings
	- Added: "Clear all" link to remove all fields from the View editor at once
	- Fixed: It was possible to drag and drop a field while the field settings screen was showing. Now it's not!
	- Fixed: See when fields have been deleted from a form
* New: Brand-new lightbox script, now using [Fancybox](http://fancyapps.com/fancybox/3/). It's fast, it's beautiful, and mobile-optimized.
	- Fixes issue with Gravity Forms images not loading in lightboxes due to secure URLs
* Ready for Gravity Forms 2.5!
* Added: Better support for the Consent field
* Improved layout of the Manage Add-Ons screen
	- Added a "Refresh" link to the Manage Add-Ons screen. This is helpful if you've upgraded your license and are ready to get started!
	- Allow enabling/disabling installed add-ons regardless of license status
* Added: A dropdown in the "All Views" screen to filter Views by the layout (Table, List, DataTables, DIY, Map, etc.)
* Added: Export entries in TSV format by adding `/tsv/` to the View URL
* Fixed: Approval Status field contains HTML in CSV and TSV exports
* Fixed: Updating an entry associated with an unactivated user (Gravity Forms User Registration) would also change entry creator's information
* Fixed: PHP warning `The magic method must have public visibility` appearing in PHP 8.0
* Fixed: PHP notice `Undefined property: stdClass::$icons` appearing on Plugins page
* Fixed: "At least one field must be filled out" validation errors (thanks <a href="https://gravitypdf.com">Gravity PDF</a>!)

__Developer Updates:__

* New: FancyBox is now being used for the lightbox
	- Thickbox is no longer used
	- Modify settings using `gravityview/lightbox/provider/fancybox/settings`
	- [See options available here](https://fancyapps.com/fancybox/3/docs/#options)
	- If you prefer, a [Featherlight lightbox option is available](https://github.com/gravityview/gv-snippets/tree/addon/featherlight-lightbox)
	- Easily add support for your own lightbox script by extending the new `GravityView_Lightbox_Provider` abstract class (the [Featherbox lightbox script](https://github.com/gravityview/gv-snippets/tree/addon/featherlight-lightbox) is a good example).
	- Modified: Formally deprecated the mis-spelled `gravity_view_lightbox_script` and `gravity_view_lightbox_style` filters in favor of  `gravityview_lightbox_script` and `gravityview_lightbox_style` (finally!)
	- Fixed: `gravityview_lightbox_script` filter wasn't being applied
	- Removed `gravityview/fields/fileupload/allow_insecure_lightbox` filter, since it's no longer needed
* Modified: `$_GET` args are now passed to links by default.
	- Added: Prevent entry links (single, edit, duplicate) from including $_GET query args by returning false to the filter `gravityview/entry_link/add_query_args`
	- Added: Prevent entry links being added to *delete* links by returning false to the filter `gravityview/delete-entry/add_query_args`
* Added: `gv_get_query_args()` function to return $_GET query args, with reserved args removed
	- Added: `gravityview/api/reserved_query_args` filter to modify internal reserved URL query args
* Added: `field-is_approved-html.php` and `field-is_approved-csv.php` template files for the Is Approved field
* Modified: Removed
* Modified: `templates/fields/field-entry_link-html.php` template to add `gv_get_query_args()` functionality
* Breaking CSS change: Removed `.gv-list-view` CSS class from the List layout container `<div>`. The CSS class was also used in the looped entry containers, making it hard to style. This issue was introduced in GravityView 2.0. For background, see [the GitHub issue](https://github.com/gravityview/GravityView/issues/1026).

= 2.9.4 on January 25, 2021 =

* Added: Apply `{get}` merge tag replacements in `[gvlogic]` attributes and content
* Modified: Made View Settings changes preparing for a big [Math by GravityView](https://www.gravitykit.com/extensions/math/) update!
* Fixed: "Change Entry Creator" would not work with Gravity Forms no-conflict mode enabled

__Developer Updates:__

* Added: `gravityview/metaboxes/multiple_entries/after` action to `includes/admin/metabox/views/multiple-entries.php` to allow extending Multiple Entries View settings

= 2.9.3 on December 15, 2020 =

* Improved: Add search field to the Entry Creator drop-down menu
* Tweak: Hide field icons (for now) when editing a View...until our refreshed design is released 😉
* Fixed: Some JavaScript warnings on WordPress 5.6
* Fixed: Uncaught error when one of GravityView's methods is used before WordPress finishes loading
* Fixed: Duplicate Entry link would only be displayed to users with an administrator role
* Fixed: Search entries by Payment Date would not yield results
* Fixed: Lightbox didn't work with secure images
* New: New lightbox gallery mode for File Upload fields with Multi-File Upload enabled

__Developer Updates:__

* Added: `gravityview/search-trim-input` filter to strip or preserve leading/trailing whitespaces in Search Bar values
* Added: Future WordPress version compatibility check
* Tweak: Improved logging output
* Modified: `gravityview_date_created_adjust_timezone` default is now set to false (use UTC value)

= 2.9.2.1 on October 26, 2020 =

* Improved: Plugin license information layout when running Gravity Forms 2.5
* Fixed: View Settings overflow their container (introduced in 2.9.2)

= 2.9.2 on October 21, 2020 =

* Added: GravityView is now 100% compatible with upcoming [Gravity Forms 2.5](https://www.gravityforms.com/gravity-forms-2-5-beta-2/)!
* Added: New View setting to redirect users to a custom URL after deleting an entry
* Added: An option to display "Powered by GravityView" link under your Views. If you're a [GravityView affiliate](https://www.gravitykit.com/account/affiliate/), you can earn 20% of sales generated from your link!
* Improved: Duplicate Entry field is only visible for logged-in users with edit or duplicate entry permissions
* Modified: Remove HTML from Website and Email fields in CSV output
* Fixed: Possible fatal error when Gravity Forms is inactive
* Fixed: Export of View entries as a CSV would result in a 404 error on some hosts
* Fixed: Entries filtered by creation date using relative dates (e.g., "today", "-1 day") did not respect WordPress's timezone offset
* Fixed: Partial entries edited in GravityView were being duplicated
* Fixed: Trying to activate a license disabled due to a refund showed an empty error message
* Tweak: Improvements to tooltip behavior in View editor
* Tweak: When "Make Phone Number Clickable" is checked, disable the "Link to single entry" setting in Phone field settings
* Tweak: Don't show "Open links in new window" for Custom Content field
* Tweak: Removed "Open link in the same window?" setting from Website field
	- Note: For existing Views, if both "Open link in the same window?" and "Open link in a new tab or window?" settings were checked, the link will now _not open in a new tab_. We hope no one had them both checked; this would have caused a rift in space-time and a room full of dark-matter rainbows.

__Developer Updates:__

* Added brand-new unit testing and acceptance testing...stay tuned for a write-up on how to easily run the GravityView test suite
* Changed: `/templates/fields/field-website-html.php` and `/templates/deprecated/fields/website.php` to use new `target=_blank` logic
* Fixed: License key activation when `GRAVITYVIEW_LICENSE_KEY` was defined
* Deprecated: Never used method `GravityView_Delete_Entry::set_entry()`

= 2.9.1 on September 1, 2020 =

* Improved: Changed the Support Port icon & text to make it clearer
* Updated: Updater script now handles WordPress 5.5 auto-updates
* Fixed: Add Yoast SEO 14.7 scripts to the No-Conflict approved list
* Fixed: Available Gravity Forms forms weren't appearing in the Gravity Forms widget when configuring a View

__Developer Updates:__

* Improved: Gravity Forms 2.5 beta support
* Fixed: Issue when server doesn't support `GLOB_BRACE`
* Fixed: Removed references to non-existent source map files

= 2.9.0.1 on July 23, 2020 =

* Fixed: Loading all Gravity Forms forms on the frontend
	* Fixes Map Icons field not working
	* Fixes conflict with gAppointments and Gravity Perks
* Fixed: Fatal error when Gravity Forms is inactive

= 2.9 on July 16, 2020 =

* Added: A "Gravity Forms" widget to easily embed a form above and below a View
* Added: Settings for changing the "No Results" text and "No Search Results" text
* Added: "Date Updated" field to field picker and sorting options
* Modified: When clicking the "GravityView" link in the Admin Toolbar, go to GravityView settings
* Improved: Add new Yoast SEO plugin scripts to the No-Conflict approved list
* Improved: Add Wicked Folders plugin scripts to the No-Conflict approved list
* Fixed: Don't allow sorting by the Duplicate field
* Fixed: Multi-site licenses not being properly shared with single sites when GravityView is not Network Activated
* Fixed: Potential fatal error for Enfold theme

__Developer Updates:__

* Fixed: Settings not able to be saved when using the `GRAVITYVIEW_LICENSE_KEY` constant
* Fixed: License not able to be activated when using the `GRAVITYVIEW_LICENSE_KEY` constant
* Fixed: Potential PHP warning when using the `{created_by}` Merge Tag
* Modified: Added index of the current file in the loop to the `gravityview/fields/fileupload/file_path` filter

= 2.8.1 on April 22, 2020 =

* Added: Better inline documentation for View Settings
* Improved: When clicking "Add All Form Fields" in the "+ Add Field" picker
* Modified: Changed default settings for new Views to "Show only approved entries"
* Modified: When adding a field to a table-based layout, "+ Add Field" now says "+ Add Column"
* Fixed: Single Entry "Hide empty fields" not working in Table and DataTables layouts

= 2.8 on April 16, 2020 =

* Added: User Fields now has many more options, including avatars, first and last name combinations, and more
* Added: A new [Gravatar (Globally Recognized Avatar)](https://en.gravatar.com) field
* Added: "Display as HTML" option for Paragraph fields - By default, safe HTML will be shown. If disabled, only text will be shown.
* Added: Support for Gravity Forms Partial Entries Add-On. When editing an entry, the entry's "Progress" will now be updated.
* Modified: Sort forms by title in Edit View, rather than Date Created (thanks, Rochelle!)
* Modified: The [`{created_by}` Merge Tag](https://docs.gravitykit.com/article/281-the-createdby-merge-tag)
	* When an entry was created by a logged-out user, `{created_by}` will now show details for a logged-out user (ID `0`), instead of returning an unmodified Merge Tag
	* When `{created_by}` is passed without any modifiers, it now will return the ID of the user who created the entry
	* Fixed PHP warning when `{created_by}` Merge Tag was passed without any modifiers
* Fixed: The "Single Entry Title" setting was not working properly
* Fixed: Recent Entries widget filters not being applied
* Updated translations: Added Formal German translation (thanks, Felix K!) and updated Polish translation (thanks, Dariusz!)

__Developer Updates:__

* Added: `gravityview/fields/textarea/allow_html` filter to toggle whether Paragraph field output should allow HTML or should be sanitized with `esc_html()`
* Added: `gravityview/field/created_by/name_display` filter for custom User Field output.
* Added: `gravityview/field/created_by/name_display/raw` allow raw (unescaped) output for `gravityview/field/created_by/name_display`.
* Added: `gravityview/fields/gravatar/settings` filter to modify the new Gravatar field's settings
* Added: `gravityview/search/sieve_choices` filter in Version 2.5 that enables only showing choices in the Search Bar that exist in entries ([learn more about this filter](https://docs.gravitykit.com/article/701-show-choices-that-exist))
* Modified: `gravityview_get_forms()` and `GVCommon::get_forms()` have new `$order_by` and `$order` parameters (Thanks, Rochelle!)
* Fixed: `gravityview/edit_entry/user_can_edit_entry` and `gravityview/capabilities/allow_logged_out` were not reachable in Edit Entry and Delete Entry since Version 2.5

= 2.7.1 on February 24, 2020 =

* Fixed: Fatal error when viewing entries using WPML or Social Sharing & SEO extensions

= 2.7 on February 20, 2020 =

* Added: "Enable Edit Locking" View setting to toggle on and off entry locking (in the "Edit Entry" tab of the View Settings)
* Fixed: Broken Toolbar link to Gravity Forms' entry editing while editing an entry in GravityView
* Fixed: PHP undefined index when editing an entry with empty File Upload field
* Fixed: When adding a field in the View Configuration, the browser window would resize

__Developer Updates:__

* Modified: The way Hidden Fields are rendered in Edit Entry no fields are configured. [Read what has changed around Hidden Fields](https://docs.gravitykit.com/article/678-edit-entry-hidden-fields-field-visibility#timeline)
	* Fixed: Rendering Hidden Fields as `input=hidden` when no fields are configured in Edit Entry (fixing a regression in 2.5)
	* Modified: The default value for the `gravityview/edit_entry/reveal_hidden_field` filter is now `false`
	* Added: `gravityview/edit_entry/render_hidden_field` filter to modify whether to render Hidden Field HTML in Edit Entry (default: `true`)
* Modified: Changed `GravityView_Edit_Entry_Locking::enqueue_scripts()` visibility to protected

= 2.6 on February 12, 2020 =

* Added: Implement Gravity Forms Entry Locking - see when others are editing an entry at the same time ([learn more](https://docs.gravitykit.com/article/676-entry-locking))
* Added: Easily duplicate entries in Gravity Forms using the new "Duplicate" link in Gravity Forms Entries screen ([read how](https://docs.gravitykit.com/article/675-duplicate-gravity-forms-entry))
* Improved: Speed up loading of Edit View screen
* Improved: Speed of adding fields in the View Configuration screen
* Modified: Reorganized some settings to be clearer
* Fixed: Potential fatal error when activating extensions with GravityView not active
* Updated: Russian translation (thank you, Victor S!)

__Developer Updates:__

* Added: `gravityview/duplicate/backend/enable` filter to disable adding a "Duplicate" link for entries
* Added: `gravityview/request/is_renderable` filter to modify what request classes represent valid GravityView requests
* Added: `gravityview/widget/search/form/action` filter to change search submission URL as needed
* Added: `gravityview/entry-list/link` filter to modify Other Entries links as needed
* Added: `gravityview/edit/link` filter to modify Edit Entry link as needed
* Fixed: A rare issue where a single entry is prevented from displaying with Post Category filters
* Modified: Important! `gravityview_get_entry()` and `GVCommon::get_entry()` require a View object as the fourth parameter. While the View will be retrieved from the context if the parameter is missing, it's important to supply it.
* Modified: `GVCommon::check_entry_display` now requires a View object as the second parameter. Not passing it will return an error.
* Modified: `gravityview/common/get_entry/check_entry_display` filter has a third View parameter passed from `GVCommon::get_entry`
* Modified: Bumped future minimum Gravity Forms version to 2.4

= 2.5.1 on December 14, 2019 =

* Modified: "Show Label" is now off by default for non-table layouts
* Improved: The View Configuration screen has been visually simplified. Fewer borders, larger items, and rounder corners.
* Accessibility improvements. Thanks to [Rian Rietveld](https://rianrietveld.com) and Gravity Forms for their support.
	- Color contrast ratios now meet [Web Content Accessibility Guidelines (WCAG) 2.0](https://www.w3.org/TR/WCAG20/) recommendations
	- Converted links that act as buttons to actual buttons
	- Added keyboard navigation support for "Add Field" and "Add Widget" pickers
	- Auto-focus the field search field when Add Field is opened
	- Improved Search Bar HTML structure for a better screen reader experience
	- Added ARIA labels for Search Bar configuration buttons
	- Improved touch target size and spacing for Search Bar add/remove field buttons
* Fixed: "Search All" with Multiple Forms plugin now works as expected in both "any" and "all" search modes.

__Developer Updates:__

* Added: `gravityview_lightbox_script` and `gravityview_lightbox_style` filters.
* Deprecated: `gravity_view_lightbox_script` and `gravity_view_lightbox_style` filters. Use `gravityview_lightbox_script` and `gravityview_lightbox_style` instead.

= 2.5 on December 5, 2019 =

This is a **big update**! Lots of improvements and fixes.

#### All changes:

* **GravityView now requires WordPress 4.7 or newer.**
* Added: A new "Duplicate Entry" allows you to duplicate entries from the front-end
* View Configuration
    * Added: You can now add labels for Custom Content in the View editor (this helps keep track of many Custom Content fields at once!)
    * Modified: New Views will be created with a number of default widgets preset
    * Fixed: View configuration could be lost when the "Update" button was clicked early in the page load or multiple times rapidly
    * Fixed: Some users were unable to edit a View, although having the correct permissions
* Improved CSV output
    * Modified: Multiple items in exported CSVs are now separated by a semicolon instead of new line. This is more consistent with formatting from other services.
    * Fixed: Checkbox output in CSVs will no longer contain HTML by default
    * Fixed: Textarea (Paragraph) output in CSVs will no longer contain `<br />` tags by default
* Edit Entry
    * Added: Directly embed the Edit Entry screen using the shortcode `[gventry edit="1"]`
    * Fixed: Editing an entry with Approve/Disapprove field hidden would disapprove an unapproved entry
    * Fixed: Field visibility when editing entries. Hidden fields remain hidden unless explicitly allowed via field configuration.
    * Fixed: Hidden calculation fields were being recalculated on Edit Entry
* Sorting and Search
    * Fixed: User sorting does not work when the `[gravityview]` shortcode defines a sorting order
    * Fixed: Proper sorting capabilities for Time and Date fields
    * Fixed: Page Size widget breaks when multiple search filters are set
    * Fixed: Page Size widget resets itself when a search is performed
* [Multiple Forms](https://www.gravitykit.com/extensions/multiple-forms/) fixes
    * Fixed: Global search not working with joined forms
    * Fixed: Custom Content fields now work properly with Multiple Forms
    * Fixed: [Gravity PDF](https://gravitypdf.com) support with Multiple Forms plugin and Custom Content fields
    * Fixed: Entry Link, Edit Link and Delete Link URLs may be incorrect with some Multiple Forms setups
* Integrations
    * Added: "Show as score" setting for Gravity Forms Survey fields
    * Added: Support for [Gravity Forms Pipe Add-On](https://www.gravityforms.com/add-ons/pipe-video-recording/)
    * Added: Track the number of pageviews entries get by using the new `[gv_pageviews]` shortcode integration with the lightweight [Pageviews](https://pageviews.io/) plugin
    * Fixed: [GP Nested Forms](https://gravitywiz.com/documentation/gravity-forms-nested-forms/) compatibility issues
    * Fixed: PHP warnings appeared when searching Views for sites running GP Populate Anything with "Default" permalinks enabled
* Improved: When a View is embedded on a post or page with an incompatible URL Slug, show a warning ([read more](https://docs.gravitykit.com/article/659-reserved-urls))
* Fixed: Number field decimal precision formatting not being respected
* Fixed: Lifetime licenses showed "0" instead of "Unlimited" sites available
* Updated: Polish translation (Thanks, Dariusz!)

__Developer Updates:__

* Added: `[gventry edit="1"]` mode where edit entry shortcodes can be used now (experimental)
* Added: `gravityview/template/field/csv/glue` filter to modify the glue used to separate multiple values in the CSV export (previously "\n", now default is ';')
* Added: `gravityview/shortcodes/gventry/edit/success` filter to modify [gventry] edit success message
* Added: `gravityview/search/sieve_choices` filter that sieves Search Widget field filter choices to only ones that have been used in entries (a UI is coming soon)
* Added: `gravityview/search/filter_details` filter for developers to modify search filter configurations
* Added: `gravityview/admin/available_fields` filter for developers to add their own assignable fields to View configurations
* Added: `gravityview/features/paged-edit` A super-secret early-bird filter to enable multiple page forms in Edit Entry
* Added: `$form_id` parameter for the `gravityview_template_$field_type_options` filter
* Added: `gravityview/security/require_unfiltered_html` filter now has 3 additional parameters: `user_id`, `cap` and `args`.
* Added: `gravityview/gvlogic/atts` filter for `[gvlogic]`
* Added: `gravityview/edit_entry/page/success` filter to alter the message between edit entry pages.
* Added: `gravityview/approve_entries/update_unapproved_meta` filter to modify entry update approval status.
* Added: `gravityview/search/searchable_fields/whitelist` filter to modify allowed URL-based searches.
* Fixed: Some issues with `unfiltered_html` user capabilities being not enough to edit a View
* Fixed: Partial form was being passed to `gform_after_update_entry` filter after editing an entry. Full form will now be passed.
* Fixed: Widget form IDs would not change when form ID is changed in the View Configuration screen
* Fixed: Intermittent `[gvlogic2]` and nested `else` issues
    * The `[gvlogic]` shortcode has been rewritten for more stable, stateless behavior
* Fixed: `GravityView_Entry_Notes::get_notes()` can return null; cast `$notes` as an array in `templates/fields/field-notes-html.php` and `includes/extensions/entry-notes/fields/notes.php` template files
* Fixed: Prevent error logs from filling with "union features not supported"
* Modified: Cookies will no longer be set for Single Entry back links
* Modified: Default 250px `image_width` setting for File Upload images is now easily overrideable
* Removed: The `gravityview/gvlogic/parse_atts/after` action is no longer available. See `gravityview/gvlogic/atts` filter instead
* Removed: The `GVLogic_Shortcode` class is now a lifeless stub. See `\GV\Shortcodes\gvlogic`.
* Deprecated: `gravityview_get_current_view_data` — use the `\GV\View` API instead

= 2.4.1.1 on August 27, 2019 =

* Fixed: Inconsistent sorting behavior for Views using Table layouts
* Fixed: Searching all fields not searching Multi Select fields
* Fixed: Error activating GravityView when Gravity Forms is disabled
* Fixed: "Getting Started" and "List of Changes" page layouts in WordPress 5.3
* Fixed: Don't show error messages twice when editing a View with a missing form
* Tweak: Don't show "Create a View" on trashed forms action menus

= 2.4 on July 17, 2019 =

**We tightened security by limiting who can edit Views. [Read how to grant Authors and Editors access](https://docs.gravitykit.com/article/598-non-administrator-edit-view).**

* Added: A new Result Number field and `{sequence}` Merge Tag [learn all about it!](https://docs.gravitykit.com/article/597-the-sequence-merge-tag)
* Added: `{date_updated}` Merge Tag ([see all GravityView Merge Tags](https://docs.gravitykit.com/article/76-merge-tags))
* Added: Option to output all CSV entries, instead of a single page of results
* Fixed: Settings compatibility issues on Multisite
* Fixed: CSV output for address fields contained Google Maps link
* Fixed: When editing an entry in Gravity Forms, clicking the "Cancel" button would not exit edit mode
* Fixed: Some fatal errors when Gravity Forms is deactivated while GravityView is active
* Fixed: Search All Fields functionality with latest Gravity Forms

__Developer Updates:__

* **Breaking Change:** Users without the `unfiltered_html` capability can no longer edit Views.
* Added: `gravityview/security/allow_unfiltered_html` to not require `unfiltered_html`. Dangerous!
* Added: `gravityview/template/field/address/csv/delimiter` filter for CSV output of addresses

= 2.3.2 on May 3, 2019 =

* Re-fixed: Conditional Logic breaks in Edit Entry if the condition field is not present

__Developer Updates:__

* Fixed: `strtolower()` warnings in `class-frontend-views.php`
* Fixed: `gravityview/fields/fileupload/link_atts` filter didn't work on link-wrapped images
* Fixed: PHP notice triggered when using the Poll widget
* Updated: Updater script, which should improve license check load time

= 2.3.1 on April 18, 2019 =

* Added: Entry Approval now features a popover that allows you to select from all approval statuses
* Fixed: Issues accessing Edit Entry for Views using [Multiple Forms](https://www.gravitykit.com/extensions/multiple-forms/)
* Fixed: Issues with Edit Entry where fields were duplicated. This temporarily reverts the conditional logic fix added in 2.3.
* Fixed: Maps will now properly use global API key settings on Multisite installations

__Developer Updates:__

* Fixed: Issues searching Address fields that contain custom states
* Added: `gravityview/approve_entries/popover_placement` filter to modify the placement of the approval popover (default: right)

= 2.3 on April 2, 2019 =

**Gravity Forms 2.3 is required**. Some functionality will not work if you are using Gravity Forms 2.2. If this affects you, please [let us know](mailto:support@gravitykit.com?subject=Gravity%20Forms%202.3%20Requirement)

* Added: Multi-Sorting! Example: Sort first by Last Name, then sort those results by First Name [Read more about multi-sorting](https://docs.gravitykit.com/article/570-sorting-by-multiple-columns)
    - Works great with our [DataTables extension](https://www.gravitykit.com/extensions/datatables/), too!
* Added: `[gvlogic logged_in="true"]` support to easily check user login status - [read how it works](https://docs.gravitykit.com/article/252-gvlogic-shortcode#logged-in-parameter)
* Added: Dropdown, Radio and Link input support for searching product fields
* Fixed: Conditional Logic breaks in Edit Entry if the condition field is not present
* Fixed: Sorting numbers with decimals
* Fixed: CSV output of List and File Upload fields
* Fixed: "Hide empty fields" setting not working Product and Quantity fields
* Fixed: Month and day reversed in multi-input date search fields
* Fixed: Join issues with embedded Views when using [Multiple Forms](https://www.gravitykit.com/extensions/multiple-forms/)
* Fixed: Other Entries empty text override was not working
* Updated: 100% translated for Dutch, German, and French

__Developer Updates:__

* Added: `gravityview/search/created_by/text` filter to override dropdown and radio text in "created by" search UI
* Added: `gravityview/approve_entries/after_submission` filter to prevent `is_approved` meta from being added automatically after entry creation
* Modified: List and File Upload fields are now output as objects/arrays in REST API JSON
* Modified: [Business Hours](https://wordpress.org/plugins/gravity-forms-business-hours/) field support in CSV and JSON output
* Fixed: Fatal error when custom templates are loaded without `\GV\Template_Context`
* Fixed: Potential PHP warning with PHP 7.2
* Added notice for users to upgrade to PHP 5.6, since WordPress will be bumping the minimum version soon


= 2.2.5 on February 4, 2019 =

* Added: Support for nested dropdown selection in Search Bar
* Fixed: State search dropdown type for custom address types
* Fixed: Don't show Credit Card fields on the Edit Entry screen (#1219)
* REST API and CSV fixes
    * Fixed: Email field being output as links in CSV
    * Fixed: CSVs could not contain more than one special field (Entry ID, Custom Content, etc.)
    * Fixed: CSV and JSON REST API did not output duplicate headers (Entry ID, Custom Content, etc.)
    * Fixed: JSON REST API endpoint did not render Custom Content fields
    * Modified: In the REST API duplicate keys are now suffixed with (n), for example: id(1), id(2), instead of not showing them at all
* Updated: Script used to provide built-in Support Port
* Updated: Russian translation by [@awsswa59](https://www.transifex.com/user/profile/awsswa59/)

__Developer Updates:__

* Added: `gravityview/edit_entry/before_update` hook
* Added: `gravityview/api/field/key` filter to customize the generated REST API entry JSON keys
* Added: `gravityview/template/csv/field/raw` filter to allow raw output of specific fields
* Modified: CSV REST API endpoint returns binary data instead of JSON-encoded data

= 2.2.4 on January 14, 2019 =

* Fixed: Other Entries field would display all entries without filtering
* Fixed: Entry Date searches not working (broken in 2.2)
* Fixed: CSV outputting wrong date formats for Date and Date Created fields
* Fixed: CSV outputting empty content for Custom Content fields
* Fixed: Changelog formatting so that the 2.2.1, 2.2.2, and 2.2.3 updates are shown
* Fixed: The picture of Floaty was _really big_ in the Getting Started screen
* Updated Translations for Italian and Iranian. Thanks, Farhad!

= 2.2.3 on December 20, 2018 =

* Fixed: Issue loading translation files on Windows IIS servers

__Developer Updates:__

* Added: Third argument to `gravityview_search_operator` filter (the current `\GV\View` object)
* Added: `GravityView_Image::is_valid_extension()` to determine whether an extension is valid for an image
* Fixed: Search operator overrides that broke in 2.2
* Modified: SVG files are now processed as images in GravityView
* Modified: Changed translation file loading order to remove paths that didn't work! [See this article for the updated paths](https://docs.gravitykit.com/article/530-translation-string-loading-order).

= 2.2.2 on December 11, 2018 =

* Added: Support for the new [Multiple Forms beta](https://www.gravitykit.com/extensions/multiple-forms/)!
* **Minor CSS Change**: Reduced Search Bar negative margins to fix the Search Bar not aligning properly
* Fixed: Calculation fields that were not added to the Edit Entry fields were being emptied (except the price)
* Updated translations - thank you, translators!
    - Turkish translated by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)
    - Russian translated by [@awsswa59](https://www.transifex.com/user/profile/awsswa59/)
    - Polish translated by [@dariusz.zielonka](https://www.transifex.com/user/profile/dariusz.zielonka/)

__Developer Updates:__

* Template Change: Updated `widget-poll.php` template to display poll results for all Multiple Forms fields
* Added: `gravityview/query/class` filter to allow query class overrides, needed for Multiple Forms extension
* Added: `gravityview/approve_entries/autounapprove/status` filter to change the approval status set when an entry is modified in Edit Entry
* Added: `$unions` property to `\GV\View`, for future use with [Multiple Forms plugin](https://www.gravitykit.com/extensions/multiple-forms/)

= 2.2.1 on December 4, 2018 =

* Confirmed compatibility with WordPress 5.0 and the new Gutenberg editor ([use the shortcode block to embed](https://docs.gravitykit.com/article/526-does-gravityview-support-gutenberg))
* Added: Support for upcoming [Multiple Forms plugin](https://www.gravitykit.com/extensions/multiple-forms/)
* Fixed: Edit Entry writes incorrectly-formatted empty values in some cases.
* Fixed: "Hide View data until search is performed" not working for [Maps layout](https://www.gravitykit.com/extensions/maps/)
* Fixed: Entries are not accessible when linked to from second page of results
* Fixed: Search redirects to home page when previewing an unpublished View

__Developer Updates:__

* Fixed: Error loading GravityView when server has not defined `GLOB_BRACE` value for the `glob()` function
* Added: `gravityview/entry/slug` filter to modify entry slug. It runs after the slug has been generated by `GravityView_API::get_entry_slug()`
* Added: `\GV\Entry::is_multi()` method to check whether the request's entry is a `Multi_Entry` (contains data from multiple entries because of joins)

= 2.2 on November 28, 2018 =

* Yes, GravityView is fully compatible with Gravity Forms 2.4!
* Added: Choose where users go after editing an entry
* Added: Search entries by approval status with new "Approval Status" field in the Search Bar
* Added: More search input types added for "Created By" searches
* Added: When searching "Created By", set the input type to "text" to search by user email, login and name fields
* Fixed: Issue installing plugins from the Extensions page on a Multisite network
* Fixed: When a View is embedded on the homepage of a site, Single Entry and Edit Entry did not work (404 not found error)
* Fixed: Stray "Advanced Custom Fields" editor at the bottom of Edit View pages
* Fixed: Labels and quantities removed when editing an entry that had product calculations
* Fixed: When multiple Views are embedded on a page, Single Entry could sometimes show "You are not allowed to view this content"
* Fixed: Major search and filtering any/all mode combination issues, especially with "Show only approved entries" mode, A-Z Filters, Featured Entries, Advanced Filtering plugins
* Fixed: Support all [documented date formats](https://docs.gravitykit.com/article/115-changing-the-format-of-the-search-widgets-date-picker) in Search Bar date fields
* Fixed: Issues with [Advanced Filtering](https://www.gravitykit.com/extensions/advanced-filter/) date fields (including human strings, less than, greater than)
* Fixed: Security issue when Advanced Filter was configured with an "Any form field" filter (single entries were not properly secured)
* Fixed: The Quiz Letter Grade is lost if Edit Entry does not contain all Gravity Forms Quiz Add-On fields

__Developer Updates:__

* Updated: `search-field-select.php` template to gracefully handle array values
* Added: Filters for new "Created By" search. [Learn how to modify what fields are searched](https://docs.gravitykit.com/article/523-created-by-text-search).

= 2.1.1 on October 26, 2018 =

* Added: A "Connected Views" menu on the Gravity Forms Forms page - hover over a form to see the new Connected Views menu!
* Fixed: Additional slashes being added to the custom date format for Date fields
* Fixed: Quiz Letter Grade not updated after editing an entry that has Gravity Forms Quiz fields
* Fixed: Single Entry screen is inaccessible when the category is part of a URL path (using the `%category%` tag in the site's Permalinks settings)
* Fixed: Issue where GravityView CSS isn't loading in the Dashboard for some customers
* Fixed: Display uploaded files using Gravity Forms' secure link URL format, if enabled
* Updated Polish translation. Dziękuję Ci, [@dariusz.zielonka](https://www.transifex.com/user/profile/dariusz.zielonka/)!

__Developer Updates:__

* Added: `gravityview/template/table/use-legacy-style` filter to  use the legacy Table layout stylesheet without any responsive layout styles (added in GravityView 2.1) - [Here's code you can use](https://gist.github.com/zackkatz/45d869e096cd5114a87952d292116d3f)
* Added: `gravityview/view/can_render` filter to allow you to override whether a View can be rendered or not
* Added: `gravityview/widgets/search/datepicker/format` filter to allow you to modify only the format used, rather than using the `gravityview_search_datepicker_class` filter
* Fixed: Fixed an issue when using [custom entry slugs](https://docs.gravitykit.com/article/57-customizing-urls) where non-unique values across forms cause the entries to not be accessible
* Fixed: Undefined index PHP warning in the GravityView Extensions screen
* Fixed: Removed internal usage of deprecated GravityView functions
* Limitation: "Enable lightbox for images" will not work on images when using Gravity Forms secure URL format. [Contact support](mailto:support@gravitykit.com) for a work-around, or use a [different lightbox script](https://docs.gravitykit.com/article/277-using-the-foobox-lightbox-plugin-instead-of-the-default).

= 2.1.0.2 and 2.1.0.3 on September 28, 2018 =

* Fixed: Slashes being added to field quotes
* Fixed: Images showing as links for File Upload fields

= 2.1.0.1 on September 27, 2018 =

* Fixed: Responsive table layout labels showing sorting icon HTML
* Fixed: Responsive table layout showing table footer

= 2.1 on September 27, 2018 =

* Added: You can now send email notifications when an entry is approved, disapproved, or the approval status has changed. [Learn how](https://docs.gravitykit.com/article/488-notification-when-entry-approved)
* Added: Automatically un-approve an entry when it has been updated by an user without the ability to moderate entries
* Added: Easy way to install GravityView Extensions and our stand-alone plugins [Learn how](https://docs.gravitykit.com/article/489-managing-extensions)
* Added: Enable CSV output for Views [Learn how](https://docs.gravitykit.com/article/491-csv-export)
* Added: A "Page Size" widget allows users to change the number of entries per page
* Added: Support for displaying a single input value of a Chained Select field
* Added: The Table layout is now mobile-responsive!
* Improved: Added a shortcut to reset entry approval on the front-end of a View: "Option + Click" on the Entry Approval field
* Fixed: Custom date format not working with the `{date_created}` Merge Tag
* Fixed: Embedding a View inside an embedded entry didn't work
* Fixed: "Link to entry" setting not working for File Upload fields
* Fixed: Approval Status field not showing anything
* Updated translations - thank you, translators!
    - Polish translated by [@dariusz.zielonka](https://www.transifex.com/user/profile/dariusz.zielonka/)
    - Russian translated by [@awsswa59](https://www.transifex.com/user/profile/awsswa59/)
    - Turkish translated by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)
    - Chinese translated by [@michaeledi](https://www.transifex.com/user/profile/michaeledi/)

__Developer Notes:__

* Added: Process shortcodes inside [gv_entry_link] shortcodes
* Added: `gravityview/shortcodes/gv_entry_link/output` filter to modify output of the `[gv_entry_link]` shortcode
* Added `gravityview/widget/page_size/settings` and `gravityview/widget/page_size/page_sizes` filters to modify new Page Size widget
* Modified: Added `data-label` attributes to all Table layout cells to make responsive layout CSS-only
* Modified: Added responsive CSS to the Table layout CSS ("table-view.css")
* Improved: Reduced database lookups when using custom entry slugs
* Introduced `\GV\View->can_render()` method to reduce code duplication
* Fixed: Don't add `gvid` unless multiple Views embedded in a post
* Fixed: PHP 5.3 warning in when using `array_combine()` on empty arrays
* Fixed: Apply `addslashes` to View Configuration when saving, fixing `{date_created}` format
* REST API: Allow setting parent post or page with the REST API request using `post_id={id}` ([learn more](https://docs.gravitykit.com/article/468-rest-api))
* REST API: Added `X-Item-Total` header and meta to REST API response

= 2.0.14.1 on July 19, 2018 =

* Fixed: Potential XSS ("Cross Site Scripting") security issue. **Please update.**
* Fixed: GravityView styles weren't being loaded for some users

= 2.0.14 on July 9, 2018 =

* Added: Allow filtering entries by Unapproved status in Gravity Forms
* Added: Reset entry approval status by holding down Option/Alt when clicking entry approval icon
* Fixed: Merge Tags not working in field Custom Labels
* Fixed: Enable sorting by approval status all the time, not just when a form has an Approval field
* Fixed: When a View is saved without a connected form, don't show "no longer exists" message
* Fixed: Inline Edit plugin not updating properly when GravityView is active

__Developer Notes:__

* Added: `gravityview/approve_entries/after_submission/default_status` filter to modify the default status of an entry as it is created.
* Modified: No longer delete `is_approved` entry meta when updating entry status - leave the value to be `GravityView_Entry_Approval_Status::UNAPPROVED` (3)
* Fixed: Allow for "in" and "not_in" comparisons when using `GravityView_GFFormsModel::is_value_match`
* Tweak: If "Search Mode" key is set, but there is no value, use "all"
* Tweak: Reduced number of database queries when rendering a View

= 2.0.13.1 on June 26, 2018 =

* Fixed: Custom Content fields not working with DIY Layout
* Fixed: Error when displaying plugin updates on a single site of a Multisite installation

= 2.0.13 on June 25, 2018 =

* Fixed: When View is embedded in a page, the "Delete Entry" link redirects the user to the View URL instead of embedded page URL
* Fixed: Custom Content fields not working with DIY Layout since 2.0.11
* Fixed: Fatal error when migrating settings from (very) old versions of GravityView
* Fixed: oEmbed not working when using "plain" URLs with numeric View ID slugs

__Developer Notes__

* Added: Code to expose Entry Notes globally, to fix conflict with DataTables (future DataTables update required)
* Added: `data-viewid` attribute to the Search Bar form with the current View ID
* Added: Current Post ID parameter to the `gravityview/edit-entry/publishing-action/after` action

= 2.0.12 on June 12, 2018 =

* Fixed: On the Plugins page, "Update now" not working for GravityView Premium Plugins, Views & Extensions
* Fixed: Always show that plugin updates are available, even if a license is expired

= 2.0.11 on June 12, 2018 =

* Added: Search for fields by name when adding fields to your View configuration (it's really great!)
* Fixed: GravityView license details not saving when the license was activated (only when the Update Settings button was clicked)
* Fixed: Entry filtering for single entries
* Fixed: Per-user language setting not being used in WordPress 4.7 or newer

__Developer Notes__

* Added: `\GV\View::get_joins()` method to fetch array of `\GV\Joins` connected with a View
* Added: `\GV\View::get_joined_forms()` method to get array of `\GV\GF_Forms` connected with a View

= 2.0.10 on June 6, 2018 =

* Fixed: Password-protected Views were showing "You are not allowed to view this content" instead of the password form
* Fixed: When Map View is embedded, Search Bar pointed to View URL, not page URL

= 2.0.9 on June 1, 2018 =

* Added: Allow passing `{get}` Merge Tags to [gventry] and [gvfield] shortcodes
* Fixed: Searching by entry creator using the Search Bar wasn't working
* Fixed: Edit Entry showing "Invalid link" warnings when multiple Views are embedded on a page
* Fixed: Issues with legacy template back-compatiblity (A-Z Filters) and newer API widgets (Maps)
* Fixed: Translations for entry "meta", like "Created By" or "Date Created"
* Fixed: When searching State/Province with the Search Bar, use "exact match" search

__Developer Notes__

* Added: Auto-prefixing for all CSS rules, set to cover 99.7% of browsers. We were already prefixing, so it doesn't change much, but it will update automatically from now on, based on browser support.

= 2.0.8.1 on May 31, 2018 =

* Fixed: Standalone map fields not displaying on the [Maps layout](https://www.gravitykit.com/extensions/maps/)
* Fixed: `[gv_entry_link]` when embedded in a post or page, not a View
* Fixed: `[gv_entry_link]` returning a broken link when the entry isn't defined
* Fixed: Conflict with Testimonials Widget plugin (and other plugins) loading outdated code
* Fixed: PHP notice when displaying Gravity Flow "Workflow" field

= 2.0.8 on May 25, 2018 =

* Fixed: Table layout not using field Column Width settings
* Fixed: With "Show Label" disabled, "Custom Label" setting is being displayed (if set)
* Fixed: List Field columns were being shown as searchable in Search Bar
* Fixed: Conflict with Gravity Forms Import Entries file upload process
* Fixed: Empty searches could show results when "Hide View data until search is performed" is enabled
* Fixed: When "Start Date" and "End Date" are the same day, results may not be accurate

__Developer Updates__

* Fixed: `gv_value()` didn't have necessary View global data set for backward compatibility (`gv_value()` is now deprecated! Use `Use \GV\Field_Template::render()` instead.)

= 2.0.7.1 on May 24, 2018 =

* Fixed: Merge Tags not being shown in Custom Content fields in Edit Entry
* Fixed: "gvGlobals not defined" JavaScript error on Edit Entry screen affecting some themes
* Fixed: Don't clear Search Bar configuration when switching View layouts

= 2.0.7 on May 23, 2018 =

* Fixed: Entry visibility when View is embedded
* Fixed: Don't show widgets if we're oEmbedding an entry
* Fixed: Don't apply "Hide Until Search" on entry pages
* Fixed: "Hide View data until search is performed" not working for Views on embedded pages
* Fixed: Restore Advanced Custom Fields plugin compatibility
* Tweak: When activating a license, remove the notice immediately
* Fixed: Maps API key settings resetting after 24 hours

__Developer Updates__

* Changed: gravityview_get_context() now returns empty string if not GravityView post type

= 2.0.6.1 on May 21, 2018 =

* Fixed: "Hide View data until search is performed" not working
* Added: Support for SiteOrigin Page Builder and LiveMesh SiteOrigin Widgets
* Fixed: Enfold Theme layout builder no longer rendering Views

= 2.0.6 on May 17, 2018 =

* Fixed: Conflicts with Yoast SEO & Jetpack plugins that prevent widgets from displaying
* Fixed: Some fields display as HTML (fixes Gravity Flow Discussion field, for example)
* Fixed: Some Merge Tag modifiers not working, such as `:url` for List fields
* Fixed: Give Floaty a place to hang out on the GravityView Settings screen with new Gravity Forms CSS

__Developer Updates__

* Fixed: Backward-compatibility for using global `$gravityview_view->_current_field` (don't use in new code!)

= 2.0.5 on May 16, 2018 =

* Fixed: Entry Link fields and `[gv_entry_link]` shortcode not working properly with DataTables when embedded
* Fixed: Do not output other shortcodes in single entry mode
* Fixed: Error when deleting an entry
* Fixed: When multiple Views are embedded on a page, and one or more has Advanced Filters enabled, no entries will be displayed
* Fixed: PHP warning with `[gravitypdf]` shortcode
* Fixed: When multiple table layout Views are embedded on a page, there are multiple column sorting links displayed
* Fixed: Error displaying message that a license is expired

= 2.0.4 on May 12, 2018 =

* Fixed: Slow front-end performance, affecting all layout types
* Fixed: Search not performing properly
* Fixed: "Enable sorting by column" option for Table layouts
* GravityView will require Gravity Forms 2.3 in the future; please make sure you're using the latest version of Gravity Forms!

__Developer Updates__

* Fixed: `GravityView_frontend::get_view_entries()` search generation
* Fixed: `gravityview_get_template_settings()` not returning settings
* Tweak: Cache View and Field magic getters into variables for less overhead.

= 2.0.3 on May 10, 2018 =

* Fixed: Compatibility with `[gravitypdf]` shortcode
* Fixed: When using `[gravityview]` shortcode, the `page_size` setting wasn't being respected
* Fixed: `[gravityview detail="last_entry" /]` not returning the correct entry
* Fixed: Widgets not being properly rendered when using oEmbed
* Fixed: Note fields not rendering properly

__Developer Notes__

* Fixed: `GravityView_View::getInstance()` not returning information about a single entry
* Added: `gravityview/shortcode/detail/$key` filter

= 2.0.1 & 2.0.2 on May 9, 2018 =

* Fixed: Widgets not displayed when a View is embedded
* Fixed: Saving new settings can cause fatal error
* Fixed: Prevent commonly-used front end function from creating an error in the Dashboard
* Fixed: Hide labels if "Show Label" is not checked
* Fixed: CSS borders on List layout
* Fixed: Error when fetching GravityView Widget with DataTables Extension 2.2
* Fixed: Fail gracefully when GravityView Maps is installed on a server running PHP 5.2.4

= Version 2.0 on May 8, 2018 =

We are proud to share this release with you: we have been working on this release since 2016, and although most of the changes won’t be seen, GravityView has a brand-new engine that will power the plugin into the future! ��
\- Zack with GravityView

---

**Note: GravityView now requires PHP 5.3 or newer**

_This is a major release. Please back up your site before updating._ We have tested the plugin thoroughly, but we suggest backing up your site before updating all plugins.

**New functionality**

* `[gventry]`: embed entries in a post, page or a View ([learn more](https://docs.gravitykit.com/article/462-gvfield-embed-gravity-forms-field-values))
* `[gvfield]`: embed single field values ([learn more](https://docs.gravitykit.com/article/462-gvfield-embed-gravity-forms-field-values))
* [Many new Merge Tag modifiers](https://docs.gravitykit.com/article/350-merge-tag-modifiers) - These enable powerful new abilities when using the Custom Content field!
* Use oEmbed with Custom Content fields - easily embed YouTube videos, Tweets (and much more) on your Custom Content field
* "Is Starred" field - display whether an entry is "Starred" in Gravity Forms or not, and star/unstar it from the front end of your site
* Added Bosnian, Iranian, and Canadian French translations, updated many others (thank you all!)

**Smaller changes**

* Added `{gv_entry_link}` Merge Tag, alias of `[gv_entry_link]` shortcode in `{gv_entry_link:[post id]:[action]}` format. This allows you to use `{gv_entry_link}` inside HTML tags, where you are not able to use the `[gv_entry_link]` shortcode.
* Default `[gvlogic]` comparison is now set to `isnot=""`; this way, you can just use `[gvlogic if="{example:1}"]` instead of `[gvlogic if="{example:1}" isnot=""]` to check if a field has a value.

**Developer Updates**

This release is the biggest ever for developers! Even so, we have taken great care to provide backward compatibility with GravityView 1.x. Other than increasing the minimum version of PHP to 5.3, **no breaking changes were made.**

* We have rewritten the plugin from the ground up. [Learn all about it here](https://github.com/gravityview/GravityView/wiki/The-Future-of-GravityView).
* New REST API! Fetch GravityView details and entries using the WordPress REST API endpoint. It's disabled by default, but can be enabled or disabled globally on GravityView Settings screen, or per-View in View Settings. [Learn about the endpoints](https://github.com/gravityview/GravityView/wiki/REST-API).
* New `gravityview()` API wrapper function, now used for easy access to everything you could want
* New template structure ([learn how to migrate your custom template files](https://github.com/gravityview/GravityView/wiki/Template-Migration))
* We have gotten rid of global state; actions and filters are now passed a `$context` argument, a [`\GV\Template_Context` object](https://github.com/gravityview/GravityView/blob/v2.0/future/includes/class-gv-context-template.php)
* When HTML 5 is enabled in Gravity Forms, now the Search All field will use `type="search"`
* _Countless_ new filters and actions! Additional documentation will be coming, both on [docs.gravitykit.com](https://docs.gravitykit.com) as well as [codex.gravitykit.com](https://codex.gravitykit.com).

A special thanks to [Gennady](https://codeseekah.com) for your tireless pursuit of better code, insistence on backward compatibility, and your positive attitude. ��

= 1.22.6 on April 4, 2018 =

* Fixed: Line breaks being added to `[gvlogic]` shortcode output
* Fixed: Gravity Forms 2.3 compatibility notice
* Fixed: "The ID is required." message when configuring the GravityView Search WordPress widget
* Fixed: Slashes were being added to Post Image details

__Developer Updates:__

* Added `gravityview/edit_entry/reveal_hidden_field` filter, which allows you to prevent Hidden fields from becoming Text fields in Edit Entry context
* Added `gravityview/edit_entry/field_visibility` filter to set field visibility on Edit Entry (default is always "visible")

= 1.22.5 on January 25, 2018 =

* Improves support for [DIY Layout](https://www.gravitykit.com/extensions/diy-layout/), a layout for designers & developers to take full advantage of GravityView
* Tweak: Show "Embed Shortcode" helper if a View has widgets configured but not Fields
* Fixed: Add Note support for Gravity Forms 2.3 (it's coming soon)
* Fixed: `tabindex` not properly set for Update/Cancel/Delete buttons in Edit Entry
* Fixed: Hide Yoast SEO Content & SEO Analysis functionality when editing a View
* Fixed: Line breaks were being added to Custom Content fields and widgets, even when "Automatically add paragraphs to content" wasn't checked

__Developer Updates:__

* Add `$nl2br`, `$format`, `$aux_data` parameters to `GravityView_API::replace_variables()` to be consistent with `GFCommon::replace_variables()`

= 1.22.4? =

Yes, we skipped a minor release (1.22.4 exists only in our hearts). Thanks for noticing!

= 1.22.3 on December 21, 2017 =

* Added: Support for displaying files uploaded using the Gravity Forms Dropbox Addon (thanks, @mgratch and @ViewFromTheBox!)
* Added: Merge Tags now are replaced when in `[gvlogic]` shortcodes not in a View
* Fixed: Filtering by date in Advanced Filters prevented single entries from being visible
* Fixed: `gravityview/capabilities/allow_logged_out` filter wasn't living up to its name (allowing logged-out visitors to edit entries)

__Developer Updates:__

* Modified: We're reverting changes made to Advanced Custom Field plugin compatibility
* Added: `gravityview/fields/fileupload/file_path` filter in `class-gravityview-field-fileupload.php`
* Modified: Removed `!important` from the CSS height rule for the `.gv-notes .gv-note-add textarea` rule

= 1.22.2 on December 7, 2017 =

* Fixed: Fatal error when running Ultimate Member 2.0 beta
* Fixed: Issue deleting entries when Advanced Filter rules don't match
* Fixed: Delete Entry messages not displaying when entry is deleted
* Fixed: ACF shortcodes in WYSIWYG fields no longer processed since 1.22.1
* Fixed: Fatal error when using old installations of Gravity Forms

__Developer Updates:__

* Added: `gravityview/edit_entry/unset_hidden_field_values` filter to prevent deleting values for fields hidden by Conditional Logic

= 1.22.1.1 on November 30, 2017 =

* Fixed: When displaying Email fields, PHP warning about `StandalonePHPEnkoder.php`

= 1.22.1 on November 29, 2017 =

* Moved "Custom Content" field to top of field picker, in what Rafael calls the "Best idea of 2017 �""
* Added: When Gravity Forms 2.3 is released, support for "Random" entry order will be enabled
* Fixed: Entry oEmbeds not working when using "Plain" URL formats to embed
* Fixed: Only published Views showing in Gravity Forms "Connected Views" menu
* Fixed: Deleting entries can cause entries to be displayed from a different View when Advanced Filters is activated and multiple Views are embedded on a page
* Fixed: Infinite loop when using `[gravityview]` shortcode inside ACF fields

__Developer Updates:__

* Added: `GravityView_HTML_Elements` class for generating commonly-used HTML elements
* Added: Way to disable front-end cookies for our friends in Europe ([see code here](https://gist.github.com/zackkatz/354a71dc47ffef072ed725706cf455ed))
* Added: `gravityview/metaboxes/data-source/before` and `gravityview/metaboxes/data-source/after` hooks
* Added: Second `$args` param added to `gravityview_get_connected_views()` function
* Modified: Pass fifth parameter `$input_type` to `GravityView_Template::assign_field_options` method

= 1.22 on September 4, 2017=

* Added: Support for Gravity Forms 2.3
* Fixed: Fatal error when Divi (and other Elegant Themes) try to load GravityView widgets while editing a post with a sidebar block in it—now the sidebar block will not be rendered
* Fixed: Inline Edit plugin not working when displaying a single entry
* Fixed: Featured Entries plugin not adding correct CSS selector to the single entry container

__Developer Updates:__

* Modified: Template files `list-header.php`, `list-single.php`, `table-header.php`, `table-single.php`
* Fixed: When `GRAVITYVIEW_LICENSE_KEY` constant is defined, it will always be used, and the license field will be disabled
* Fixed: List View and Table View templates have more standardized CSS selectors for single & multiple contexts ([Learn more](https://docs.gravitykit.com/article/63-css-guide))
* Fixed: Permalink issue when embedding a View on a page, then making it the site's Front Page
* Fixed: Transient cache issues when invalidating cache
* Fixed: `gv_empty()` now returns false for an array with all empty values
* Fixed: Delay plugin compatibility checks until `plugins_loaded`

= 1.21.5.3 on July 24, 2017 =

* Fixed: For some field types, the value "No" would be interpreted as `false`
* Fixed: In Edit Entry, when editing a form that has a Post Custom Field field type—configured as checkboxes—file upload fields would not be saved
* Fixed: If a form connected to a View is in the trash, there will be an error when editing the View
* Fixed: Embedding single entries with WordPress 4.8
* Fixed: Fatal error when using older version of WPML

= 1.21.5.2 on June 26, 2017 =

* Tweak: Improved plugin speed by reducing amount of information logged
* Fixed: Duplicate descriptions on the settings screen
* Fixed: Our "No-Conflict Mode" made the settings screen look bad. Yes, we recognize the irony.
* Updated: Translations - thank you, translators!
    - Turkish translation by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)
    - Dutch translations by Thom

= 1.21.5.1 on June 13, 2017 =

* Modified: We stopped allowing any HTML in Paragraph Text fields in 1.21.5, but this functionality was used by lots of people. We now use a different function to allow safe HTML by default.
* Added: `gravityview/fields/textarea/allowed_kses` filter to modify the allowed HTML to be displayed.

= 1.21.5 on June 8, 2017 =

* Added: The `{current_post}` Merge Tag adds information about the current post. [Read more about it](https://docs.gravitykit.com/article/412-currentpost-merge-tag).
* Added: `gravityview/gvlogic/parse_atts/after` action to modify `[gvlogic]` shortcode attributes after it's been parsed
* Added: A new setting to opt-in for access to the latest pre-release versions of GravityView (in Views > Settings)
* Added: Support for Restrict Content Pro when in "No-Conflict Mode"
* Fixed: Saving an entry could strip the entry creator information. Now, when the entry creator is not in the "Change Entry Creator" users list, we add them back in to the list.
* Fixed: Potential security issue
* Fixed: Multiple notifications could sometimes be sent when editing an entry in GravityView.
* Fixed: Gravity Forms tooltip scripts being loaded admin-wide.
* Updated: Dutch translations (thanks, Thom!)

= 1.21.4 on April 13, 2017 =

* Fixed: "Enable sorting by column" not visible when using table-based View Presets
* Fixed: Error activating the plugin when Gravity Forms is not active
* Fixed: Numeric sorting
* Fixed: Compatibility issue with WPML 3.6.1 and lower
* Tweak: When using `?cache` to disable entries caching, cached data is removed

= 1.21.3 on April 4, 2017 =

* Fixed: Post Images stopped working in Edit Entry
* Fixed: Conflict with our Social Sharing & SEO Extension
* Fixed: Unable to search for a value of `0`
* Fixed: Inaccurate search results when using the `search_field` and `search_value` settings in the `[gravityview]` shortcode
    - The search mode will now always be set to `all` when using these settings

__Developer Updates:__

* We decided to not throw exceptions in the new `gravityview()` wrapper function. Instead, we will log errors via Gravity Forms logging.

= 1.21.2 on March 31, 2017 =

* Added: Support for embedding `[gravityview]` shortcodes in Advanced Custom Fields (ACF) fields
* Fixed: PHP warnings and notices

= 1.21.1 on March 30, 2017 =

* Fixed: Advanced Filters no longer filtered ��
* Fixed: Fatal error when viewing Single Entry with a Single Entry Title setting that included Merge Tags
* Fixed: Cache wasn't cleared when an entry was created using Gravity Forms API (thanks Steve with Gravity Flow!)

= 1.21 on March 29, 2017 =

* Fixed: Edit Entry compatibility with Gravity Forms 2.2
* Fixed: Single Entry not accessible when filtering a View by Gravity Flow's "Final Status" field
* Fixed: Needed to re-save permalink settings for Single Entry and Edit Entry to work
* Fixed: Incorrect pagination calculations when passing `offset` via the `[gravityview]` shortcode

__Developer Updates:__

* Modified: `GVCommon::check_entry_display()` now returns WP_Error instead of `false` when an error occurs. This allows for additional information to be passed.
* Added: `gravityview/search-all-split-words` filter to change search behavior for the "Search All" search input. Default (`true`) converts words separated by spaces into separate search terms. `false` will search whole word.
* Much progress has been made on the `gravityview()` wrapper function behind the scenes. Getting closer to parity all the time.

= 1.20.1 on March 1, 2017 =

* Added: Support for comma-separated email addresses when adding a note and using "Other email address"
* Fixed: Edit Entry issue with File Uploads not saving properly
* Fixed: Support for `offset` attribute in the `[gravityview]` shortcode
* Updated: Auto-upgrade script

= 1.20 on February 24, 2017 =

* Added: Product Fields are now editable
    - Quantity,
    - Product fields are hidden if the entry contains external transaction data
    - Support for Coupon Addon
* Fixed: Single Entry not accessible when filtering by a Checkbox field in the Advanced Filters Extension
* Fixed: WPML links to Single Entry not working if using directory or sub-domain URL formats
* Fixed: Product field prices not always formatted as a currency
* Fixed: Product fields sometimes appeared twice in the Add Field field picker
* Fixed: PHP warning when updating entries. Thanks for reporting, Werner!
* Modified: Don't show CAPTCHA fields in Edit Entry
* Fixed: "Trying to get property of non-object" bug when updating an entry connected to Gravity Forms User Registration
* Fixed: Yoast SEO scripts and styles not loading properly on Edit View screen
* Updated: Minimum version of Gravity Forms User Registration updated to 3.2

__Developer Notes:__


* Added: `GVCommon::entry_has_transaction_data()` to check whether entry array contains payment gateway transaction information
* Added: `gravityview/edit_entry/hide-coupon-fields` to modify whether to hide Coupon fields in Edit Entry (default: `false`)
* Added: `GravityView_frontend::get_view_entries_parameters()` method to get the final entry search parameters for a View without fetching the entries as well
* Added: `GVCommon::get_product_field_types()` to fetch Gravity Forms product field types array
* Added: `gravityview/edit_entry/field_blacklist` filter to modify what field types should not be shown in Edit Entry
* Added: `GravityView_Plugin_Hooks_Gravity_Forms_Coupon` class
* Added: Third `GravityView_Edit_Entry_Render` parameter to `gravityview/edit_entry/field_value`, `gravityview/edit_entry/field_value_{field_type}` filters and `gravityview/edit_entry/after_update` action
* Updated: `list-body.php` and `list-single.php` template files to prevent empty `<div>` from rendering (and looking bad) when there are no fields configured for the zones
* Updated: `fields/product.php` template file
* Updated: Flexibility library for IE CSS flexbox support
* Modified: `gravityview/edit_entry/hide-product-fields` default will now be determined by whether entry has gateway transaction information
* Modified: Only print errors when running the unit tests if the `--debug` setting is defined, like `phpunit --debug --verbose`
* Modified: If overriding `get_field_input()` using `GravityView_Field`, returning empty value will now result in the default `GF_Field` input being used
* Modified: GravityView_Edit_Entry_User_Registration::restore_display_name() now returns a value instead of void
* Tweak: Edit Entry links no longer require `page=gf_entries&view=entry` at the end of the URL (in case you noticed)

= 1.19.4 on January 19, 2017 =

* **GravityView requirements will soon be updated**: Gravity Forms Version 2.0+, PHP 5.3+
* Updated: GravityView now requires WordPress 4.0 or newer
* Fixed: Search Bar search not working for states in the United States
* Fixed: WPML conflict where Single Entry or Edit Entry screens are inaccessible
* Fixed: Prevent PHP error when displaying GravityView using `get_gravityview()`
* Updated translations:
    - �� Danish *100% translated*d*
    - �� Norwegian *100% translated*d*
    - �� Swedish translation updateded

__Developer Notes: __

* New: We're starting the migration to a new wrapper API that will awesome. We will be rolling out new functionality and documentation over time. For now, we are just using it to load the plugin. [Very exciting time](https://i.imgur.com/xmkONOD.gif)!
* Fixed: Issue fetching image sizes when using `GravityView_Image` class and fetching from a site with invalid SSL cert.
* Added: `gravityview_directory_link` to modify the URL to the View directory context (in `GravityView_API::directory_link()`)

= 1.19.3 on January 9, 2017 =

First update of 2017! We've got great things planned for GravityView and our Extensions. As always, [contact us](mailto:support@gravitykit.com) with any questions or feedback. We don't bite!

* Fixed: List field inputs not loading in Edit Entry when values were empty or the field was hidden initially because of Conditional Logic
* Fixed: Prevent Approve Entry and Delete Entry fields from being added to Edit Entry field configuration
* Fixed: Don't render Views outside "the loop", prevents conflicts with other plugins that run `the_content` filter outside normal places
* Fixed: Only display "You have attempted to view an entry that is not visible or may not exist." warning once when multiple Views are embedded on a page
* Fixed: The `[gravityview]` shortcode would not be parsed properly due to HTML encoding when using certain page builders, including OptimizePress
* Fixed: Potential errors when non-standard form fields are added to Edit Entry configurations ("Creating default object from empty value" and "Cannot use object of type stdClass as array")
* Updated translations:
    - �� Chinese *100% translated* (thank you, Michael Edi!)!)
    - �� French *100% translated*d*
    - �� Brazilian Portuguese *100% translated* (thanks, Rafael!)!)
    - �� Dutch translation updated (thank you, Erik van Beek!)!)
    - �� Swedish translation updateded
    - Updated Spanish (Spain + Mexican) and German (`de` + `de_DE`) with each other

__Developer Notes:__

* `GVCommon::get_form_from_entry_id()` now correctly fetches forms with any status
* Moved `GravityView_Support_Port::get_related_plugins_and_extensions()` to `GV_License_Handler` class
* Updated the `install.sh` bash script
    - The 6th parameter now prevents database creation, and the 7th is the Gravity Forms source file
    - Script no longer breaks if there is a space in a directory name
    - `/tmp/` is no longer created in the GravityView directory; it's installed in the server's `/tmp/` directory
* Fixed Travis CI integration

= 1.19.2 on December 21, 2016 =

* Added: Search Bar now supports displaying State and Country fields as Select, List, or Radio input types (before, only text fields)
* Fixed: Single entries not accessible when a View has filters based on Gravity Forms "Advanced" fields like Address and Name
* Added: There is now a warning when a View tab has not been configured. The question "Why aren't my entries showing up?" is often due to a lack of configuration.
* Added: Notice for future PHP requirements.
    * Reminder: GravityView will soon require PHP 5.3. 97.6% of sites are already compatible.
* Fixed: Conflict with another plugin that prevented the Field Settings from being reachable in the Edit View screen
* Fixed: GravityView widgets repeating twice for some customers

__Developer Notes:__

* Added: `GravityView_View::getContextFields()` method allows fetching the fields configured for each View context (`directory`, `single`, `edit`)
    * Modified: `templates/list-body.php` and `templates/list-single.php` to add a check for context fields before rendering
* Added: `$field_id` as fourth argument passed to `gravityview/extension/search/input_type` filter
* Added: Added `$cap` and `$object_id` parameters to `GVCommon::generate_notice()` to be able to check caps before displaying a notice

= 1.19.1 on November 15, 2016 =

* Fixed: When creating a new View, the "form doesn't exist" warning would display

= 1.19 on November 14, 2016 =

* New: __Front-end entry moderation__! You can now approve and disapprove entries from the front of a View - [learn how to use front-end entry approval](https://docs.gravitykit.com/article/390-entry-approval)
    - Add entry moderation to your View with the new "Approve Entries" field
    - Displaying the current approval status by using the new "Approval Status" field
    - Views have a new "Show all entries to administrators" setting. This allows administrators to see entries with any approval status. [Learn how to use this new setting](https://docs.gravitykit.com/article/390-entry-approval#clarify-step-16)
* Fixed: Approval values not updating properly when using the "Approve/Reject" and "User Opt-In" fields
* Tweak: Show inactive forms in the Data Source form dropdown
* Tweak: If a View is connected to a form that is in the trash or does not exist, an error message is now shown
* Tweak: Don't show "Lost in space?" message when searching existing Views
* Added: New Russian translation - thank you, [George Kovalev](https://www.transifex.com/user/profile/gkovaleff/)!
    - Updated: Spanish translation (thanks [@matrixmercury](https://www.transifex.com/user/profile/matrixmercury/))

__Developer Notes:__

* Added: `field-approval.css` CSS file. [Learn how to override the design here](https://docs.gravitykit.com/article/388-front-end-approval-css).
* Modified: Removed the bottom border on the "No Results" text (`.gv-no-results` CSS selector)
* Fixed: Deprecated `get_bloginfo()` usage

= 1.18.1 on November 3, 2016 =

* Updated: 100% Chinese translation—thank you [Michael Edi](https://www.transifex.com/user/profile/michaeledi/)!
* Fixed: Entry approval not working when using [custom entry slugs](https://docs.gravitykit.com/article/57-customizing-urls)
* Fixed: `Undefined index: is_active` warning is shown when editing entries with User Registration Addon active
* Fixed: Strip extra whitespace in Entry Note field templates

= 1.18 on October 11, 2016 =

* Updated minimum requirements: WordPress 3.5, Gravity Forms 1.9.14
* Modified: Entries that are unapproved (not approved or disapproved) are shown as yellow circles
* Added: Shortcut to create a View for an existing form
* Added: Entry Note emails now have a message "This note was sent from {url}" to provide context for the note recipient
* Fixed: Edit Entry did not save other field values when Post fields were in the Edit Entry form
* Fixed: When using "Start Fresh" View presets, form fields were not being added to the "Add Field" field picker
* Fixed: Hidden visible inputs were showing in the "Add Field" picker (for example, the "Middle Name" input was hidden in the Name field, but showing as an option)
* Fixed: Fatal error when editing Post Content and Post Image fields
* Fixed: Lightbox images not loading
* Fixed: Lightbox loading indicator displaying below the overlay
* Fixed: "New form created" message was not shown when saving a draft using a "Start Fresh" View preset
* Gravity Forms User Registration Addon changes:
    * Gravity Forms User Registration 2.0 is no longer supported
    * Fixed Processing "Update User" feeds
    * Fixed: Inactive User Registration feeds were being processed
    * Fixed: User Registration "Update User" feeds were being processed, even if the Update Conditions weren't met
    * Fixed: Unable to use `gravityview/edit_entry/user_registration/trigger_update` filter
* Fixed: Prevent negative entry counts when approving and disapproving entries
* Fixed: PHP notice when WooCommerce Memberships is active
* Tweak: Entry Note emails now have paragraphs automatically added to them
* Tweak: When the global "Show Support Port" setting is "Hide", always hide; if set to "Show", respect each user's Support Port display preference
* Updated: Complete German translation—thank you [hubert123456](https://www.transifex.com/user/profile/hubert123456/)!

__Developer Notes__

* Migrated `is_approved` entry meta values; statuses are now managed by the `GravityView_Entry_Approval_Status` class
    - "Approved" => `1`, use `GravityView_Entry_Approval_Status::APPROVED` constant
    - "0" => `2`, use `GravityView_Entry_Approval_Status::DISAPPROVED` constant
    - Use `$new_value = GravityView_Entry_Approval_Status::maybe_convert_status( $old_value )` to reliably translate meta values
* Added: `GVCommon::get_entry_id()` method to get the entry ID from a slug or ID
* Added: `gravityview_go_back_url` filter to modify the link URL used for the single entry back-link in `gravityview_back_link()` function
* Added: `gravityview/field/notes/wpautop_email` filter to disable `wpautop()` on Entry Note emails
* Added: `$email_footer` to the `gravityview/field/notes/email_content` filter content
* Modified: `note-add-note.php` template: added `current-url` hidden field
* Modified: `list-single.php` template file: added `.gv-grid-col-1-3` CSS class to the `.gv-list-view-content-image` container
* Fixed: Mask the Entry ID in the link to lightbox files

= 1.17.4 on September 7, 2016 =

* Added: Support for editing [Gravity Perks Unique ID](https://gravitywiz.com/documentation/gp-unique-id/) fields
* Fixed: Issue searching and sorting fields with multiple inputs (like names)
* Fixed: Restore Gravity Forms Quiz Addon details in the field picker

__Developer Notes__

* Added: `gravityview_get_directory_widgets()`, `gravityview_set_directory_widgets()` wrapper functions to get and set View widget configurations
* Added: Second `$apply_filter` parameter to `GVCommon::get_directory_fields()` function to set whether or not to apply the `gravityview/configuration/fields` filter

= 1.17.3 on August 31, 2016 =

* Added: Search Bar support for Gravity Forms Survey fields: filter by survey responses
* Added: Search Bar support for Gravity Flow: search entries by the current Step, Step Status, or Workflow Status
* Added: `[gvlogic]` and other shortcodes now can be used inside Email field settings content
* Added: Support for embedding Views in the front page of a site; the [GravityView - Allow Front Page Views plugin](https://github.com/gravityview/gravityview-front-page-views) is no longer required
* Tweak: In Edit View, holding down the option (or alt) key while switching forms allows you to change forms without resetting field configurations - this is useful if you want to switch between duplicate forms
* Fixed: Restored correct Gravity Flow status and workflow values
* Fixed: Conflict when editing an entry in Gravity Flow
* Fixed: Tooltip title text of the field and widget "gear" icon
* Changed the plugin author from "Katz Web Services, Inc." to "GravityView" - it seemed like it was time!

__Developer Notes__

* Modified: `gravityview_get_forms()` function and `GVCommon::get_forms()` method to be compatible with `GFAPI::get_forms()`. Now accepts `$active` and `$trash` arguments, as well as returning all form data (not just `id` and `title` keys)
* Modified: `template/fields/post_image.php` file to use `gravityview_get_link()` to generate the anchor link
* Modified: `rel="noopener noreferrer"` now added to all links generated using `gravityview_get_link()` with `target="_blank"`. This fixes a generic security issue (not specific to GravityView) when displaying links to submitted websites and "Open link in new window" is checked - [read more about it here](https://dev.to/ben/the-targetblank-vulnerability-by-example)
* Modified: Don't convert underscores to periods if not numeric in `GravityView_Widget_Search::prepare_field_filter()` - this fixes searching entry meta
* Modified: Added third `gravityview_search_field_label` parameter: `$field` - it's the field configuration array passed by the Search Bar
* Modified: HTML tags are now stripped from Email field body and subject content
* Modified: Moved `GravityView_Admin_View_Item`, `GravityView_Admin_View_Field`, and `GravityView_Admin_View_Widget` to their own files
* Added: Deprecation notices for methods that haven't been used since Version 1.2!

= 1.17.2 on August 9, 2016 =

* Fixed: "Start Fresh" fails when there are no pre-existing forms in Gravity Forms
* Fixed: Edit Entry not saving values for fields that were initially hidden
* Added: Support for embedding Views in Ultimate Member profile tabs
* Fixed: File Upload fields potentially displaying PHP warnings
* Fixed: Check plugin and theme existence before loading hooks
* Fixed: "Hide empty fields" not working when "Make Phone Number Clickable" is checked for Phone fields
* Fixed: Potential PHP warning when adding Password fields in Edit View
* Fixed: Dutch (Netherlands) `nl_NL` translation file fixed
* Fixed: Divi theme shortcode buttons and modal form added to Edit View screen
* Fixed: Possible for Approve Entries checkbox to use the wrong Form ID
* Fixed: Search issues with special characters
    - Searches that contained ampersands `&` were not working
    - Searches containing plus signs `+` were not working
    - The "Select" Search Bar input type would not show the active search if search term contained an `&`
* Fixed: Multisite issue: when Users are logged-in but not added to any sites, they aren't able to see View content
* Fixed: Never show GravityView Toolbar menu to users who aren't able to edit Views, Forms, or Entries
* Fixed: Allow passing `post_id` in `[gravityview]` shortcode
* Tweak: Use system fonts instead of Open Sans in the admin
* Modified: The default setting for "No-Conflict Mode" is now "On". GravityView _should look good_ on your site!
* Updated translations (thank you!)
    - Turkish translation by Süha Karalar
    - Chinese translation by Michael Edi

__Developer Notes:__

* Added: `gravityview_view_saved` action, triggered after a View has been saved in the admin
* Modified: Changed the Phone field template to use `gravityview_get_link()` to generate the anchor tag
* Added: `gravityview/common/get_entry_id_from_slug/form_id` filter to modify the form ID used to generate entry slugs, in order to avoid hash collisions with data from other forms

= 1.17.1 on June 27 =
* Fixed: Entry approval with Gravity Forms 2.0
    * Added: Approved/Disapproved filters to Gravity Forms "Entries" page
    * Fixed: Bulk Approve/Disapprove
    * Fixed: Approve column and Bulk Actions not visible on Gravity Forms Entries page
    * Tweak: Improved speed of approving/disapproving entries
* Fixed: "Reply To" reference fixed in `GVCommon::send_email()` function
* Added: Improved logging for creation of Custom Slug hash ids
* Translations updated:
    - Updated Chinese translation by [@michaeledi](https://www.transifex.com/user/profile/michaeledi/)
    - Updated Persian translation by [@azadmojtaba](https://www.transifex.com/user/profile/azadmojtaba/)

= 1.17 on June 14 =

* Fully compatible with Gravity Forms 2.0
* Added: Entry Notes field
    - Add and delete Entry Notes from the frontend
    - Allows users to email Notes when they are added
    - Display notes to logged-out users
    - New [user capabilities](https://docs.gravitykit.com/article/311-gravityview-capabilities) to limit access (`gravityview_add_entry_notes`, `gravityview_view_entry_notes`, `gravityview_delete_entry_notes`, `gravityview_email_entry_notes`)
* Added: Merge Tag modifiers - now set a maximum length of content, and automatically add paragraphs to Merge Tags. [Read how to use the new Merge Tag modifiers](https://docs.gravitykit.com/article/350-merge-tag-modifiers).
    - `:maxwords:{number}` - Limit output to a set number of words
    - `:wpautop` - Automatically add line breaks and paragraphs to content
    - `:timestamp` - Convert dates into timestamp values
* Modified: Major changes to the Search Bar design
* Added: Field setting to display the input value, label, or check mark, depending on field type. Currently supported: Checkbox, Radio, Drop Down fields.
* Added: RTL ("right to left") language support in default and List template styles (Added: `gv-default-styles-rtl.css` and `list-view-rtl.css` stylesheets)
* Added: Option to make Phone numbers click-to-call
* Added: GravityView parent menu to Toolbar; now you can edit the form connected to a View directly from the View
    * Changed: Don't show Edit View in the Admin Bar; it's now under the GravityView parent menu
    * Fixed: Don't remove Edit Post/Page admin bar menu item
* Added: Support for [Gravity Flow](https://gravityflow.io) "Workflow Step" and Workflow "Final Status" fields
* Added: Support for Password fields. You probably shouldn't display them (in most cases!) but now you *can*
* Modified: When deleting/trashing entries with GravityView, the connected posts created by Gravity Forms will now also be deleted/trashed
* Edit Entry improvements
    * Added: Edit Entry now fully supports [Gravity Forms Content Templates](https://www.gravityhelp.com/documentation/article/create-content-template/)
    * Fixed: Edit Entry didn't pre-populate List inputs if they were part of a Post Custom Field field type
    * Fixed: Updating Post Image fields in Edit Entry when the field is not set to "Featured Image" in Gravity Forms
    * Fixed: "Rank" and "Ratings" Survey Field types not being displayed properly in Edit Entry
    * Fixed: Signature field not displaying existing signatures in Edit Entry
    * Fixed: Post Category fields will now update to show the Post's current categories
    * Fixed: Allow multiple Post Category fields in Edit Entry
    * Fixed: PHP warning caused when a form had "Anti-spam honeypot" enabled
* Fixed: When inserting a GravityView shortcode using the "Add View" button, the form would flow over the window
* Fixed: [Church Themes](https://churchthemes.com) theme compatibility
* Fixed: Inactive and expired licenses were being shown the wrong error message
* Fixed: Moving domains would prevent GravityView from updating
* Fixed: When using the User Opt-in field together with the View setting "Show Only Approved Entries", entries weren't showing
* Fixed: If a label is set for Search Bar "Link" fields, use the label. Otherwise, "Show only:" will be used
* Fixed: Showing the first column of a List field was displaying all the field's columns
* Translations: New Persian translation by [@azadmojtaba](https://www.transifex.com/user/profile/azadmojtaba/) (thank you!)

__Developer Notes__

* Templates changed:
    * `list-single.php` and `list-body.php`: changed `#gv_list_{entry_id}` to `#gv_list_{entry slug}`. If using custom entry slugs, the ID attribute will change. Otherwise, no change.
    * `list-body.php`: Removed `id` attribute from entry title `<h3>`
* Added: Override GravityView CSS files by copying them to a template's `/gravityview/css/` sub-directory
* Added: `gravityview_css_url()` function to check for overriding CSS files in templates
* Added: `gravityview_use_legacy_search_style` filter; return `true` to use previous Search Bar stylesheet
* Major CSS changes for the Search Bar.
    - Search inputs `<div>`s now have additional CSS classes based on the input type: `.gv-search-field-{input_type}` where `{input_type}` is:
    `search_all` (search everything text box), `link`, `date`, `checkbox` (list of checkboxes), `single_checkbox`, `text`, `radio`, `select`,
    `multiselect`, `date_range`, `entry_id`, `entry_date`
    - Added `gv-search-date-range` CSS class to containers that have date ranges
    - Moved `gv-search-box-links` CSS class from the `<p>` to the `<div>` container
    - Fixed: `<label>` `for` attribute was missing quotes
* Added:
    - `gravityview/edit_entry/form_fields` filter to modify the fields displayed in Edit Entry form
    - `gravityview/edit_entry/field_value_{field_type}` filter to change the value of an Edit Entry field for a specific field type
    - `gravityview/edit-entry/render/before` action, triggered before the Edit Entry form is rendered
    - `gravityview/edit-entry/render/after` action, triggered after the Edit Entry form is rendered
* Fixed: PHP Warning for certain hosting `open_basedir` configurations
* Added: `gravityview/delete-entry/delete-connected-post` Filter to modify behavior when entry is deleted. Return false to prevent posts from being deleted or trashed when connected entries are deleted or trashed. See `gravityview/delete-entry/mode` filter to modify the default behavior, which is "delete".
* Added: `gravityview/edit_entry/post_content/append_categories` filter to modify whether post categories should be added to or replaced?
* Added: `gravityview/common/get_form_fields` filter to modify fields used in the "Add Field" selector, View "Filters" dropdowns, and Search Bar
* Added: `gravityview/search/searchable_fields` filter to modify fields used in the Search Bar field dropdown
* Added: `GVCommon::send_email()`, a public alias of `GFCommon::send_email()`
* Added: `GravityView_Field_Notes` class, with lots of filters to modify output
* Added: `$field_value` parameter to `gravityview_get_field_label()` function and `GVCommon::get_field_label()` method
* Added: `$force` parameter to `GravityView_Plugin::frontend_actions()` to force including files
* Modified: Added second parameter `$entry` to `gravityview/delete-entry/trashed` and `gravityview/delete-entry/deleted` actions
* Fixed: An image with no `src` output a broken HTML `<img>` tag

= 1.16.5.1 on April 7 =

* Fixed: Edit Entry links didn't work

= 1.16.5 on April 6 =

* Fixed: Search Bar inputs not displaying for Number fields
* Fixed: Compatibility issue with [ACF](https://wordpress.org/plugins/advanced-custom-fields/) plugin when saving a View
* Fixed (for real this time): Survey field values weren't displaying in Edit Entry
* Tweak: Made it clearer when editing a View that GravityView is processing in the background
* Added: Chinese translation (thanks, Edi Weigh!)
* Updated: German translation (thanks, [@akwdigital](https://www.transifex.com/user/profile/akwdigital/)!)

__Developer Notes__

* Added: `gravityview/fields/custom/decode_shortcodes` filter to determine whether to process shortcodes inside Merge Tags in Custom Content fields. Off by default, for security reasons.
* Fixed: Potential fatal errors when activating GravityView if Gravity Forms isn't active
* Updated: Gamajo Template Loader to Version 1.2
* Verified compatibility with WordPress 4.5

= 1.16.4.1 on March 23 =
* Fixed: Major display issue caused by output buffering introduced in 1.16.4. Sorry!

= 1.16.4 on March 21 =
* Fixed: `[gravityview]` shortcodes sometimes not rendering inside page builder shortcodes
* Fixed: Individual date inputs (Day, Month, Year) always would show full date.
* Fixed: Quiz and Poll fields weren't displaying properly
* Fixed: Survey field CSS styles weren't enqueued properly when viewing survey results
* Fixed: Survey field values weren't displaying in Edit Entry. We hope you "likert" this update a lot ;-)
* Added: Option to set the search mode ("any" or "all") on the GravityView Search WordPress widget.
* Added: Option to show/hide "Show Answer Explanation" for Gravity Forms Quiz Addon fields
* Tweak: Don't show GravityView Approve Entry column in Gravity Forms Entries table if there are no entries
* Updated: Turkish translation. Thanks, [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)!
* Tested and works with [Gravity Forms 2.0 Beta 1](https://www.gravityforms.com/gravity-forms-v2-0-beta-1-released/)

__Developer Notes:__

* Tweak: Updated `templates/fields/date.php` template to use new `GravityView_Field_Date::date_display()` method.
* Added `gv-widgets-no-results` and `gv-container-no-results` classes to the widget and View container `<div>`s. This will make it easier to hide empty View content and/or Widgets.
* Added: New action hooks when entry is deleted (`gravityview/delete-entry/deleted`) or trashed (`gravityview/delete-entry/trashed`).
* Added: Use the hook `gravityview/search/method` to change the default search method from `GET` to `POST` (hiding the search filters from the View url)
* Added: `gravityview/extension/search/select_default` filter to modify default value for Drop Down and Multiselect Search Bar fields.
* Added: `gravityview_get_input_id_from_id()` helper function to get the Input ID from a Field ID.

= 1.16.3 on February 28 =

* Fixed: Date range search not working
* Fixed: Display fields with calculation enabled on the Edit Entry view
* Fixed: Large images in a gallery not resizing (when using [.gv-gallery](https://docs.gravitykit.com/article/247-create-a-gallery))
* Tweak: Start and end date in search are included in the results

__Developer Notes:__

* Added: `gravityview/approve_entries/bulk_actions` filter to modify items displayed in the Gravity Forms Entries "Bulk action" dropdown, in the "GravityView" `<optgroup>`
* Added: `gravityview/edit_entry/button_labels` filter to modify the Edit Entry view buttons labels (defaults: `Cancel` and `Update`)
* Added: `gravityview/approve_entries/add-note` filter to modify whether to add a note when the entry has been approved or disapproved (default: `true`)
* Fixed: Removed deprecated `get_currentuserinfo()` function usage

= 1.16.2.2 on February 17 =

* This fixes Edit Entry issues introduced by 1.16.2.1. If you are running 1.16.2.1, please update. Sorry for the inconvenience!

= 1.16.2.1 on February 16 =

* Fixed: Edit Entry calculation fields not being able to calculate values when the required fields weren't included in Edit Entry layout
* Fixed: Prevent Section fields from being searchable
* Fixed: Setting User Registration 3.0 "create" vs "update" feed type

= 1.16.2 on February 15 =

* Added: Support for Post Image field on the Edit Entry screen
* Added: Now use any Merge Tags as `[gravityview]` parameters
* Fixed: Support for User Registration Addon Version 3
* Fixed: Support for rich text editor for Post Body fields
* Fixed: Admin-only fields may get overwritten when fields aren't visible during entry edit by user (non-admin)
* Fixed: Address fields displayed hidden inputs
* Fixed: Merge Tag dropdown list can be too wide when field names are long
* Fixed: When sorting, recent entries disappeared from results
* Fixed: Searches that included apostrophes  or ampersands returned no results
* Fixed: Zero values not set in fields while in Edit Entry
* Fixed: Re-calculate fields where calculation is enabled after entry is updated
* Fixed: Warning message when Number fields not included in custom Edit Entry configurations
* Translation updates:
    - Bengali - thank you [@tareqhi](https://www.transifex.com/accounts/profile/tareqhi/) for 100% translation!
    - Turkish by [@dbalage](https://www.transifex.com/accounts/profile/dbalage/)


__Developer Notes:__

* Reminder: <strong>GravityView will soon require PHP 5.3</strong>
* Added: `gravityview/widgets/container_css_class` filter to modify widget container `<div>` CSS class
    - Added `gv-widgets-{zone}` class to wrapper (`{zone}` will be either `header` or `footer`)
* Fixed: Conflict with some plugins when `?action=delete` is processed in the Admin ([#624](https://github.com/gravityview/GravityView/issues/624), reported by [dcavins](https://github.com/dcavins))
* Fixed: Removed `icon` CSS class name from the table sorting icon links. Now just `gv-icon` instead of `icon gv-icon`.
* Fixed: "Clear" search link now set to `display: inline-block` instead of `display: block`
* Added: `gravityview/common/get_entry/check_entry_display` filter to disable validating whether to show entries or not against View filters
* Fixed: `GravityView_API::replace_variables` no longer requires `$form` and `$entry` arguments

= 1.16.1 on January 21 =

* Fixed: GravityView prevented Gravity Forms translations from loading
* Fixed: Field Width setting was visible in Edit Entry
* Fixed: Don't display embedded Gravity Forms forms when editing an entry in GravityView

__Developer Notes:__

* Added: `gravityview_excerpt_more` filter. Modify the "Read more" link used when "Maximum Words" setting is enabled and the output is truncated.
    * Removed: `excerpt_more` filter on `textarea.php` - many themes use permalink values to generate links.

= 1.16 on January 14 =
* Happy New Year! We have big things planned for GravityView in 2016, including a new View Builder. Stay tuned :-)
* Added: Merge Tags. [See all GravityView Merge Tags](https://docs.gravitykit.com/article/76-merge-tags)
    * `{date_created}` The date an entry was created. [Read how to use it here](https://docs.gravitykit.com/article/331-date-created-merge-tag).
    * `{payment_date}` The date the payment was received. Formatted using [the same modifiers](https://docs.gravitykit.com/article/331-date-created-merge-tag) as `{date_created}`
    * `{payment_status}` The current payment status of the entry (ie "Processing", "Pending", "Active", "Expired", "Failed", "Cancelled", "Approved", "Reversed", "Refunded", "Voided")
    * `{payment_method}` The way the entry was paid for (ie "Credit Card", "PayPal", etc.)
    * `{payment_amount}` The payment amount, formatted as the currency (ie `$75.25`). Use `{payment_amount:raw}` for the un-formatted number (ie `75.25`)
    * `{currency}` The currency with which the entry was submitted (ie "USD", "EUR")
    * `{is_fulfilled}` Whether the order has been fulfilled. Displays "Not Fulfilled" or "Fulfilled"
    * `{transaction_id}` the ID of the transaction returned by the payment gateway
    * `{transaction_type}` Indicates the transaction type of the entry/order. "Single Payment" or "Subscription".
* Fixed: Custom merge tags not being replaced properly by GravityView
* Fixed: Connected form links were not visible in the Data Source metabox
* Fixed: Inaccurate "Key missing" error shown when license key is invalid
* Fixed: Search Bar could show "undefined" search fields when security key has expired. Now, a helpful message will appear.
* Tweak: Only show Add View button to users who are able to publish Views
* Tweak: Reduce the number of database calls by fetching forms differently
* Tweak: Only show license key notices to users who have capability to edit settings, and only on GravityView pages
* Tweak: Improved load time of Views screen in the admin
* Tweak: Make sure entry belongs to correct form before displaying
* Tweak: Removed need for one database call per displayed entry
* Translations, thanks to:
    - Brazilian Portuguese by [@marlosvinicius](https://www.transifex.com/accounts/profile/marlosvinicius.info/)
    - Mexican Spanish by [@janolima](https://www.transifex.com/accounts/profile/janolima/)

__Developer Notes:__

* New: Added `get_content()` method to some `GravityView_Fields` subclasses. We plan on moving this to the parent class soon. This allows us to not use `/templates/fields/` files for every field type.
* New: `GVCommon::format_date()` function formats entry and payment dates in more ways than `GFCommon::format_date`
* New: `gravityview_get_terms_choices()` function generates array of categories ready to be added to Gravity Forms $choices array
* New: `GVCommon::has_product_field()` method to check whether a form has product fields
* New: Added `add_filter( 'gform_is_encrypted_field', '__return_false' );` before fetching entries
* Added: `gv-container-{view id}` CSS class to `gv_container_class()` function output. This will be added to View container `<div>`s
* Added: `$group` parameter to `GravityView_Fields::get_all()` to get all fields in a specified group
* Added: `gravityview_field_entry_value_{field_type}_pre_link` filter to modify field values before "Show As Link" setting is applied
* Added: Second parameter `$echo` (boolean) to `gv_container_class()`
* Added: Use the `$is_sortable` `GravityView_Field` variable to define whether a field is sortable. Overrides using the  `gravityview/sortable/field_blacklist` filter.
* Fixed: `gv_container_class()` didn't return value
* Fixed: Don't add link to empty field value
* Fixed: Strip extra whitespace in `gravityview_sanitize_html_class()`
* Fixed: Don't output widget structural HTML if there are no configured widgets
* Fixed: Empty HTML `<h4>` label container output in List layout, even when "Show Label" was unchecked
* Fixed: Fetching the current entry can improperly return an empty array when using `GravityView_View->getCurrentEntry()` in DataTables extension
* Fixed: `gravityview/sortable/formfield_{form}_{field_id}` filter [detailed here](https://docs.gravitykit.com/article/231-how-to-disable-the-sorting-control-on-one-table-column)
* Fixed: `gravityview/sortable/field_blacklist` filter docBlock fixed
* Tweak: Set `max-width: 50%` for `div.gv-list-view-content-image`
* Tweak: Moved `gv_selected()` to `helper-functions.php` from `class-api.php`

= 1.15.2 on December 3 =

* Fixed: Approval column not being added properly on the Form Entries screen for Gravity Forms 1.9.14.18+
* Fixed: Select, multi-select, radio, checkbox, and post category field types should use exact match search
* Fixed: Cannot delete entry notes from Gravity Forms Entry screen
* Fixed: Date Range search field label not working
* Fixed: Date Range searches did not include the "End Date" day
* Fixed: Support Port docs not working on HTTPS sites
* Fixed: When deleting an entry, only show "Entry Deleted" message for the deleted entry's View
* Fixed: "Open link in a new tab or window?" setting for Paragraph Text fields
* Fixed: Custom Labels not being used as field label in the View Configuration screen
    * Tweak: Custom Labels will be used as the field label, even when the "Show Label" checkbox isn't checked
* Tweak: Show available plugin updates, even when license is expired
* Tweak: Improve spacing of the Approval column on the Entries screen
* Tweak: Added support for new accessibility labels added in WordPress 4.4

__Developer Notes:__

* Fixed: Make `gravityview/fields/fileupload/link_atts` filter available when not using lightbox with File Uploads field
* Renamed files:
    - `includes/fields/class.field.php` => `includes/fields/class-gravityview-field.php`
    - `includes/class-logging.php` => `includes/class-gravityview-logging.php`
    - `includes/class-image.php` => `includes/class-gravityview-image.php`
    - `includes/class-migrate.php` => `includes/class-gravityview-migrate.php`
    - `includes/class-change-entry-creator.php` => `includes/class-gravityview-change-entry-creator.php`
* New: `gravityview/delete-entry/verify_nonce` Override Delete Entry nonce validation. Return true to declare nonce valid.
* New: `gravityview/entry_notes/add_note` filter to modify GravityView note properties before being added
* New: `gravityview_post_type_supports` filter to modify `gravityview` post type support values
* New: `gravityview_publicly_queryable` filter to modify whether Views be accessible using `example.com/?post_type=gravityview`. Default: Whether the current user has `read_private_gravityviews` capability (Editor or Administrator by default)

= 1.15.1 on October 27 =
* New: Use `{get}` Merge Tags as `[gravityview]` attributes
* Fixed: Edit Entry and Delete Entry links weren't working in DataTables
* Fixed: Some Gravity Forms Merge Tags weren't working, like `{embed_post:post_title}`
* Fixed: Display Checkbox and Radio field labels in the Search Bar
	* New: If you prefer how the searches looked before the labels were visible, you can set the "Label" for the search field to a blank space. That will hide the label.
	* Removed extra whitespace from search field `<label>`s
* Fixed: Update the required Gravity Forms version to 1.9.9.10
* Fixed: Section fields should not be affected by "Hide empty fields" View setting
* Fixed: Add ability to check post custom fields for `[gravityview]` shortcode. This fixes issues with some themes and page builder plugins.
* Fixed: Return type wasn't boolean for `has_gravityview_shortcode()` function
* Tweak: Improve notifications logic
	* Only show notices to users with appropriate capabilities
	* Allow dismissing all notices
	* Clear dismissed notices when activating the plugin
	* Fixed showing notice to enter license key
* Tweak: Added previously-supported `{created_by:roles}` Merge Tag to available tags dropdown
* Tweak: Allow overriding `gravityview_sanitize_html_class()` function
* Tweak: Make `GravityView_Merge_Tags::replace_get_variables()` method public
* Tweak: Rename `GravityView_Merge_Tags::_gform_replace_merge_tags()` method `GravityView_Merge_Tags::replace_gv_merge_tags()` for clarity

= 1.15 on October 15 =
* Added: `{get}` Merge Tag that allows passing data via URL to be safely displayed in Merge Tags. [Learn how this works](https://docs.gravitykit.com/article/314-the-get-merge-tag).
	- Example: When adding `?first-name=Floaty` to a URL, the Custom Content `My name is {get:first-name}` would be replaced with `My name is Floaty`
* Added: GravityView Capabilities: restrict access to GravityView functionality to certain users and roles. [Learn more](https://docs.gravitykit.com/article/311-gravityview-capabilities).
	- Fixed: Users without the ability to create Gravity Forms forms are able to create a new form via "Start Fresh"
	- Only add the Approve Entries column if user has the `gravityview_moderate_entries` capability (defaults to Editor role or higher)
	- Fixed: Contributors now have access to the GravityView "Getting Started" screen
* Added: `[gv_entry_link]` shortcode to link directly to an entry. [Learn more](https://docs.gravitykit.com/article/287-edit-entry-and-delete-entry-shortcodes).
	- Existing `[gv_delete_entry_link]` and `[gv_edit_entry_link]` shortcodes will continue to work
* Added: Ability to filter View by form in the Admin. [Learn more](https://docs.gravitykit.com/article/313-the-views-list-on-the-dashboard).
* Added: Option to delete GravityView data when the plugin is uninstalled, then deleted. [Learn more](https://docs.gravitykit.com/article/312-how-to-delete-the-gravityview-data-when-the-plugin-is-uninstalled).
* Added: New support "Beacon" to easily search documentation and ask support questions
* Added: Clear search button to the Search Widget (WP widget)
* Fixed: `number_format()` PHP warning on blank Number fields
* Fixed: `{created_by}` merge tags weren't being escaped using `esc_html()`
* Fixed: Checkmark icons weren't always available when displaying checkbox input field
* Fixed: When "Shorten Link Display" was enabled for Website fields, "Link Text" wasn't respected
* Fixed: Only process "Create" Gravity Forms User Registration Addon feeds, by default the user role and the user display name format persist
* Fixed: Error with List field  `Call to undefined method GF_Field::get_input_type()`
* Fixed: BuddyPress/bbPress `bbp_setup_current_user()` warning
* Fixed: `gravityview_is_admin_page()` wasn't recognizing the Settings page as a GravityView admin page
* Fixed: Custom Content Widgets didn't replace Merge Tags
* Fixed: PHP Warnings
* Fixed: WordPress Multisite fatal error when Gravity Forms not Network Activated
* Tweak: Don't show Data Source column in Views screen to users who don't have permissions to see any of the data anyway
* Tweak: Entry notes are now created using `GravityView_Entry_Notes` class
* Tweak: Improved automated code testing
* Tweak: Added `gravityview/support_port/display` filter to enable/disable displaying Support Port
* Tweak: Added `gravityview/support_port/show_profile_setting` filter to disable adding the Support Port setting on User Profile pages
* Tweak: Removed `gravityview/admin/display_live_chat` filter
* Tweak: Removed `gravityview_settings_capability` filter
* Tweak: Escape form name in dropdowns

= 1.14.2 & 1.14.3 on September 17 =
* Fixed: Issue affecting Gravity Forms User Registration Addon. Passwords were being reset when an user edited their own entry.

= 1.14.1 on September 16 =
* Fixed: Error with older versions of Maps Premium View

= 1.14 on September 16 =
* Added: Search Bar now supports custom label text
* Added: Show the value of a single column of a "Multiple Columns" List field
* Added: Sorting by time now works. Why is this "Added" and not "Fixed"? Because Gravity Forms doesn't natively support sorting by time!
* Added: Display the roles of the entry creator by using `{created_by:roles}` Merge Tag
* Fixed: Field containers were being rendered even when empty
* Fixed: Widgets were not being displayed when using page builders and themes that pre-process shortcodes
* Fixed: Don't show "Width %" setting when in Single Entry configuration
* Fixed: Error in extension class that assumes GravityView is active
* Fixed: Add check for `{all_fields_display_empty}` Gravity Forms merge tag
* Fixed: Hide metabox until View Data Source is configured
* Fixed: Search Bar "Link" input type wasn't highlighting properly based on the value of the filter
* Fixed: Improved speed of getting users for Search Bar and GravityView Search Widgets with "Submitted by" fields, and in the Edit Entry screen (the Change Entry Creator dropdown)
* Fixed: Conflict with other icon fonts in the Dashboard
* Fixed: Allow HTML in Source URL "Link Text" field setting
* Fixed: Gravity Forms User Registration Addon conflicts
	- When editing an entry, an user's roles and display name were reset to the Addon's feed configuration settings
	- Users receive "Password Updated" emails in WordPress 4.3+, even if the password wasn't changed
* Fixed: Prevent sorting by List fields, which aren't sortable due to their data storage method
* Tweak: Support for plugin banner images in the plugin changelog screen
* Tweak: Updated default Search Bar configuration to be a single input with "Search Everything"
* Tweak: Sort user dropdown by display name instead of username
* Tweak: Reduce size of AJAX responses
* Tweak: Add "Template" column to the All Views list table - now you can better see what template is being used
* Tweak: Remove redundant close icon for field and widget settings
* Tweak: When adding notes via GravityView, set the note type to `gravityview` to allow for better searchability
* Added: Automated code testing
* Updated: Bengali translation by [@tareqhi](https://www.transifex.com/accounts/profile/tareqhi/). Thank you!

= 1.13.1 on August 26 =
* Fixed: Potential XSS security issue. **Please update.**
* Fixed: The cache was not being reset properly for entry changes, including:
	- Starring/unstarring
	- Moving to/from the trash
	- Changing entry owner
	- Being marked as spam
* Fixed: Delete entry URL not properly passing some parameters (only affecting pages with multiple `[gravityview]` shortcodes)
* Added: `gravityview/delete-entry/mode` filter. When returning "trash", "Delete Entry" moves entries to the trash instead of permanently deleting them.
* Added: `gravityview/admin/display_live_chat` filter to disable live chat widget
* Added: `gravityview/delete-entry/message` filter to modify the "Entry Deleted" message content
* Tweak: Improved license activation error handling by linking to relevant account functions
* Tweak: Added settings link to plugin page actions
* Tweak: Improved code documentation
* Updated Translations:
	- Bengali translation by [@tareqhi](https://www.transifex.com/accounts/profile/tareqhi/)
	- Turkish translation by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)
* New: Released a new [GravityView Codex](http://codex.gravitykit.com) for developers

= 1.13 on August 20 =
* Fixed: Wildcard search broken for Gravity Forms 1.9.12+
* Fixed: Edit Entry validation messages not displaying for Gravity Forms 1.9.12+
* Added: Number field settings
	- Format number: Display numbers with thousands separators
	- Decimals: Precision of the number of decimal places. Leave blank to use existing precision.
* Added: `detail` parameter to the `[gravityview]` shortcode. [Learn more](https://docs.gravitykit.com/article/73-using-the-shortcode#detail-parameter)
* Added: `context` parameter to the `[gvlogic]` shortcode to show/hide content based on current mode (Multiple Entries, Single Entry, Edit Entry). [Learn more](https://docs.gravitykit.com/article/252-gvlogic-shortcode#context)
* Added: Allow to override the entry saved value by the dynamic populated value on the Edit Entry view using the `gravityview/edit_entry/pre_populate/override` filter
* Added: "Edit View" link in the Toolbar when on an embedded View screen
* Added: `gravityview_is_hierarchical` filter to enable defining a Parent View
* Added: `gravityview/merge_tags/do_replace_variables` filter to enable/disable replace_variables behavior
* Added: `gravityview/edit_entry/verify_nonce` filter to override nonce validation in Edit Entry
* Added: `gravityview_strip_whitespace()` function to strip new lines, tabs, and multiple spaces and replace with single spaces
* Added: `gravityview_ob_include()` function to get the contents of a file using combination of `include()` and `ob_start()`
* Fixed: Edit Entry link not showing for non-admins when using the DataTables template
* Fixed: Cache wasn't being used for `get_entries()`
* Fixed: Extension class wasn't properly checking requirements
* Fixed: Issue with some themes adding paragraphs to Javascript tags in the Edit Entry screen
* Fixed: Duplicated information in the debugging logs
* Updated: "Single Entry Title" and "Back Link Label" settings now support shortcodes, allowing for you to use [`[gvlogic]`](https://docs.gravitykit.com/article/252-gvlogic-shortcode)
* Updated: German and Portuguese translations

= 1.12 on August 5 =
* Fixed: Conflicts with Advanced Filter extension when using the Recent Entries widget
* Fixed: Sorting icons were being added to List template fields when embedded on the same page as Table templates
* Fixed: Empty Product fields would show a string (", Qty: , Price:") instead of being empty. This prevented "Hide empty fields" from working
* Fixed: When searching on the Entry Created date, the date used GMT, not blog timezone
* Fixed: Issue accessing settings page on Multisite
* Fixed: Don't show View post types if GravityView isn't valid
* Fixed: Don't redirect to the List of Changes screen if you've already seen the screen for the current version
* Fixed: When checking license status, the plugin can now fix PHP warnings caused by other plugins that messed up the requests
* Fixed: In Multisite, only show notices when it makes sense to
* Added: `gravityview/common/sortable_fields` filter to override which fields are sortable
* Tweak: Extension class added ability to check for required minimum PHP versions
* Tweak: Made the `GravityView_Plugin::$theInstance` private and renamed it to `GravityView_Plugin::$instance`. If you're a developer using this, please use `GravityView_Plugin::getInstance()` instead.
* Updated: French translation

= 1.11.2 on July 22 =
* Fixed: Bug when comparing empty values with `[gvlogic]`
* Fixed: Remove extra whitespace when comparing values using `[gvlogic]`
* Modified: Allow Avada theme Javascript in "No-Conflict Mode"
* Updated: French translation

= 1.11.1 on July 20 =
* Added: New filter hook to customise the cancel Edit Entry link: `gravityview/edit_entry/cancel_link`
* Fixed: Extension translations
* Fixed: Dropdown inputs with long field names could overflow field and widget settings
* Modified: Allow Genesis Framework CSS and Javascript in "No-Conflict Mode"
* Updated: Danish translation (thanks [@jaegerbo](https://www.transifex.com/accounts/profile/jaegerbo/)!) and German translation

= 1.11 on July 15 =
* Added: GravityView now updates WordPress user profiles when an entry is updated while using the Gravity Forms User Registration Add-on
* Fixed: Removed User Registration Add-on validation when updating an entry
* Fixed: Field custom class not showing correctly on the table header
* Fixed: Editing Time fields wasn't displaying saved value
* Fixed: Conflicts with the date range search when search inputs are empty
* Fixed: Conflicts with the Other Entries field when placing a search:
    - Developer note: the filter hook `gravityview/field/other_entries/args` was replaced by "gravityview/field/other_entries/criteria". If you are using this filter, please [contact support](mailto:support@gravitykit.com) before updating so we can help you transition
* Updated: Turkish translation (thanks [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)!) and Mexican translation (thanks [@jorgepelaez](https://www.transifex.com/accounts/profile/jorgepelaez/)!)

= 1.10.1 on July 2 =
* Fixed: Edit Entry link and Delete Entry link in embedded Views go to default view url
* Fixed: Duplicated fields on the Edit Entry view
* Fixed: Warning on bulk edit

= 1.10 on June 26 =
* Update: Due to the new Edit Entry functionality, GravityView now requires Gravity Forms 1.9 or higher
* Fixed: Editing Hidden fields restored
* Fixed: Edit Entry and Delete Entry may not always show in embedded Views
* Fixed: Search Bar "Clear" button Javascript warning in Internet Explorer
* Fixed: Edit Entry styling issues with input sizes. Edit Entry now uses 100% Gravity Forms styles.
* Added: `[gv_edit_entry_link]` and `[gv_delete_entry_link]` shortcodes. [Read how to use them](https://docs.gravitykit.com/article/287-edit-entry-and-delete-entry-shortcodes)

= 1.9.1 on June 24 =
* Fixed: Allow "Admin Only" fields to appear in Edit Entry form
	- New behavior: If the Edit Entry tab isn't configured in GravityView (which means all fields will be shown by default), GravityView will hide "Admin Only" fields from being edited by non-administrators. If the Edit Entry tab is configured, then GravityView will use the field settings in the configuration, overriding Gravity Forms settings.
* Tweak: Changed `gravityview/edit-entry/hide-product-fields` filter to `gravityview/edit_entry/hide-product-fields` for consistency

= 1.9 on June 23 =
* Added: Edit Entry now takes place in the Gravity Forms form layout, not in the previous layout. This means:
	- Edit Entry now supports Conditional Logic - as expected, fields will show and hide based on the form configuration
	- Edit Entry supports [Gravity Forms CSS Ready Classes](https://docs.gravityforms.com/list-of-css-ready-classes/) - the layout you have configured for your form will be used for Edit Entry, too.
	- If you customized the CSS of your Edit Entry layout, **you will need to update your stylesheet**. Sorry for the inconvenience!
	- If visiting an invalid Edit Entry link, you are now provided with a back link
	- Product fields are now hidden by default, since they aren't editable. If you want to instead display the old message that "product fields aren't editable," you can show them using the new `gravityview/edit_entry/hide-product-fields` filter
* Added: Define column widths for fields in each field's settings (for Table and DataTable View Types only)
* Added: `{created_by}` Merge Tag that displays information from the creator of the entry ([learn more](https://docs.gravitykit.com/article/281-the-createdby-merge-tag))
* Added: Edit Entry field setting to open link in new tab/window
* Added: CSS classes to the Update/Cancel/Delete buttons ([learn more](https://docs.gravitykit.com/article/63-css-guide#edit-entry))
* Fixed: Shortcodes not processing properly in DataTables Extension
* Tweak: Changed support widget to a Live Chat customer support and feedback form widget

= 1.8.3 on June 12 =
* Fixed: Missing title and subtitle field zones on `list-single.php` template

= 1.8.2 on June 10 =
* Fixed: Error on `list-single.php` template

= 1.8.1 on June 9 =
* Added: New search filter for Date fields to allow searching over date ranges ("from X to Y")
* Updated: The minimum required version of Gravity Forms is now 1.8.7. **GravityView will be requiring Gravity Forms 1.9 soon.** Please update Gravity Forms if you are running an older version!
* Fixed: Conflicts with [A-Z Filter Extension](https://www.gravitykit.com/extensions/a-z-filter/) and View sorting due to wrong field mapping
* Fixed: The "links" field type on the GravityView WordPress search widget was opening the wrong page
* Fixed: IE8 Javascript error when script debugging is on. Props, [@Idealien](https://github.com/Idealien). [Issue #361 on Github](https://github.com/katzwebservices/GravityView/issues/361)
* Fixed: PHP warning when trashing entries. [Issue #370 on Github](https://github.com/katzwebservices/GravityView/issues/370)
* Tweak: Updated the `list-single.php`, `table-body.php`, `table-single.php` templates to use `GravityView_View->getFields()` method

= 1.8 on May 26 =
* View settings have been consolidated to a single location. [Learn more about the new View Settings layout](https://docs.gravitykit.com/article/275-view-settings).
* Added: Custom Link Text in Website fields
* Added: Poll Addon GravityView widget
* Added: Quiz Addon support: add Quiz score fields to your View configuration
* Added: Possibility to search by entry creator on Search Bar and Widget
* Fixed: `[gvlogic]` shortcode now properly handles comparing empty values.
    * Use `[gvlogic if="{example} is=""]` to determine if a value is blank.
    * Use `[gvlogic if="{example} isnot=""]` to determine if a value is not blank.
    * See "Matching blank values" in the [shortcode documentation](https://docs.gravitykit.com/article/252-gvlogic-shortcode)
* Fixed: Sorting by full address. Now defaults to sorting by city. Use the `gravityview/sorting/address` filter to modify what data to use ([here's how](https://gist.github.com/zackkatz/8b8f296c6f7dc99d227d))
* Fixed: Newly created entries cannot be directly accessed when using the custom slug feature
* Fixed: Merge Tag autocomplete hidden behind the Field settings (did you know you can type `{` in a field that has Merge Tags enabled and you will get autocomplete?)
* Fixed: For sites not using [Permalinks](http://codex.wordpress.org/Permalinks), the Search Bar was not working for embedded Views
* Tweak: When GravityView is disabled, only show "Could not activate the Extension; GravityView is not active." on the Plugins page
* Tweak: Added third parameter to `gravityview_widget_search_filters` filter that passes the search widget arguments
* Updated Translations:
    - Italian translation by [@Lurtz](https://www.transifex.com/accounts/profile/Lurtz/)
	- Bengali translation by [@tareqhi](https://www.transifex.com/accounts/profile/tareqhi/)
    - Danish translation by [@jaegerbo](https://www.transifex.com/accounts/profile/jaegerbo/)

= 1.7.6.2 on May 12 =
* Fixed: PHP warning when trying to update an entry with the approved field.
* Fixed: Views without titles in the "Connected Views" dropdown would appear blank

= 1.7.6.1 on May 7 =
* Fixed: Pagination links not working when a search is performed
* Fixed: Return false instead of error if updating approved status fails
* Added: Hooks when an entry approval is updated, approved, or disapproved:
    - `gravityview/approve_entries/updated` - Approval status changed (passes $entry_id and status)
    - `gravityview/approve_entries/approved` - Entry approved (passes $entry_id)
    - `gravityview/approve_entries/disapproved` - Entry disapproved (passes $entry_id)

= 1.7.6 on May 5 =
* Added WordPress Multisite settings page support
    - By default, settings aren't shown on single blogs if GravityView is Network Activated
* Fixed: Security vulnerability caused by the usage of `add_query_arg` / `remove_query_arg`. [Read more about it](https://blog.sucuri.net/2015/04/security-advisory-xss-vulnerability-affecting-multiple-wordpress-plugins.html)
* Fixed: Not showing the single entry when using Advanced Filter (`ANY` mode) with complex fields types like checkboxes
* Fixed: Wrong width for the images in the list template (single entry view)
* Fixed: Conflict with the "The Events Calendar" plugin when saving View Advanced Filter configuration
* Fixed: When editing an entry in the frontend it gets unapproved when not using the approve form field
* Added: Option to convert text URI, www, FTP, and email addresses on a paragraph field in HTML links
* Fixed: Activate/Check License buttons weren't properly visible
* Added: `gravityview/field/other_entries/args` filter to modify arguments used to generate the Other Entries list. This allows showing other user entries from any View, not just the current view
* Added: `gravityview/render/hide-empty-zone` filter to hide empty zone. Use `__return_true` to prevent wrapper `<div>` from being rendered
* Updated Translations:
	- Bengali translation by [@tareqhi](https://www.transifex.com/accounts/profile/tareqhi/)
	- Turkish translation by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)
	- Hungarian translation by [@Darqebus](https://www.transifex.com/accounts/profile/Darqebus/)

= 1.7.5.1 on April 10 =
* Fixed: Path issue with the A-Z Filters Extension

= 1.7.5 on April 10 =
* Added: `[gvlogic]` Shortcode - allows you to show or hide content based on the value of merge tags in Custom Content fields! [Learn how to use the shortcode](https://docs.gravitykit.com/article/252-gvlogic-shortcode).
* Fixed: White Screen error when license key wasn't set and settings weren't migrated (introduced in 1.7.4)
* Fixed: No-Conflict Mode not working (introduced in 1.7.4)
* Fixed: PHP notices when visiting complex URLs
* Fixed: Path to plugin updater file, used by Extensions
* Fixed: Extension global settings layout improved (yet to be implemented)
* Tweak: Restructure plugin file locations
* Updated: Dutch translation by [@erikvanbeek](https://www.transifex.com/accounts/profile/erikvanbeek/). Thanks!

= 1.7.4.1 on April 7 =
* Fixed: Fatal error when attempting to view entry that does not exist (introduced in 1.7.4)
* Updated: Turkish translation by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/). Thanks!

= 1.7.4 on April 6 =
* Modified: The List template is now responsive! Looks great on big and small screens.
* Fixed: When editing an entry in the frontend it gets unapproved
* Fixed: Conflicts between the Advanced Filter extension and the Single Entry mode (if using `ANY` mode for filters)
* Fixed: Sorting by full name. Now sorts by first name by default.
    * Added `gravityview/sorting/full-name` filter to sort by last name ([see how](https://gist.github.com/zackkatz/cd42bee4f361f422824e))
* Fixed: Date and Time fields now properly internationalized (using `date_i18n` instead of `date`)
* Added: `gravityview_disable_change_entry_creator` filter to disable the Change Entry Creator functionality
* Modified: Migrated to use Gravity Forms settings
* Modified: Updated limit to 750 users (up from 300) in Change Entry Creator dropdown.
* Confirmed WordPress 4.2 compatibility
* Updated: Dutch translation (thanks, [@erikvanbeek](https://www.transifex.com/accounts/profile/erikvanbeek/)!)

= 1.7.3 on March 25 =
* Fixed: Prevent displaying a single Entry that doesn't match configured Advanced Filters
* Fixed: Issue with permalink settings needing to be re-saved after updating GravityView
* Fixed: Embedding entries when not using permalinks
* Fixed: Hide "Data Source" metabox links in the Screen Options tab in the Admin
* Added: `gravityview_has_archive` filter to enable View archive (see all Views by going to [sitename.com]/view/)
* Added: Third parameter to `GravityView_API::entry_link()` method:
    * `$add_directory_args` *boolean* True: Add URL parameters to help return to directory; False: only include args required to get to entry
* Tweak: Register `entry` endpoint even when not using rewrites
* Tweak: Clear `GravityView_View->_current_entry` after the View is displayed (fixes issue with Social Sharing Extension, coming soon!)
* Added: Norwegian translation (thanks, [@aleksanderespegard](https://www.transifex.com/accounts/profile/aleksanderespegard/)!)

= 1.7.2 on March 18 =
* Added: Other Entries field - Show what other entries the entry creator has in the current View
* Added: Ability to hide the Approve/Reject column when viewing Gravity Forms entries ([Learn how](https://docs.gravitykit.com/article/248-how-to-hide-the-approve-reject-entry-column))
* Fixed: Missing Row Action links for non-View types (posts, pages)
* Fixed: Embedded DataTable Views with `search_value` not filtering correctly
* Fixed: Not possible to change View status to 'Publish'
* Fixed: Not able to turn off No-Conflict mode on the Settings page (oh, the irony!)
* Fixed: Allow for non-numeric search fields in `gravityview_get_entries()`
* Fixed: Social icons displaying on GravityView settings page
* Tweak: Improved Javascript & PHP speed and structure

= 1.7.1 on March 11 =
* Fixed: Fatal error on the `list-body.php` template

= 1.7 on March 10 =
* Added: You can now edit most Post Fields in Edit Entry mode
    - Supports Post Content, Post Title, Post Excerpt, Post Tags, Post Category, and most Post Custom Field configurations ([Learn more](https://docs.gravitykit.com/article/245-editable-post-fields))
* Added: Sort Table columns ([read how](https://docs.gravitykit.com/article/230-how-to-enable-the-table-column-sorting-feature))
* Added: Post ID field now available - shows the ID of the post that was created by the Gravity Forms entry
* Fixed: Properly reset `$post` after Live Post Data is displayed
* Tweak: Display spinning cursor while waiting for View configurations to load
* Tweak: Updated GravityView Form Editor buttons to be 1.9 compatible
* Added: `gravityview/field_output/args` filter to modify field output settings before rendering
* Fixed: Don't show date field value if set to Unix Epoch (1/1/1970), since this normally means that in fact, no date has been set
* Fixed: PHP notices when choosing "Start Fresh"
* Fixed: If Gravity Forms is installed using a non-standard directory name, GravityView would think it wasn't activated
* Fixed: Fixed single entry links when inserting views with `the_gravityview()` template tag
* Updated: Portuguese translation (thanks, Luis!)
* Added: `gravityview/fields/email/javascript_required` filter to modify message displayed when encrypting email addresses and Javascript is disabled
* Added: `GFCommon:js_encrypt()` method to encrypt text for Javascript email encryption
* Fixed: Recent Entries widget didn't allow externally added settings to save properly
* Fixed: Delete Entry respects previous pagination and sorting
* Tweak: Updated View Presets to have improved Search Bar configurations
* Fixed: `gravityview/get_all_views/params` filter restored (Modify Views returned by the `GVCommon::get_all_views()` method)
* GravityView will soon require Gravity Forms 1.9 or higher. If you are running Gravity Forms Version 1.8.x, please update to the latest version.

= 1.6.2 on February 23 =
* Added: Two new hooks in the Custom Content field to enable conditional logic or enable `the_content` WordPress filter which will trigger the Video embed ([read how](https://docs.gravitykit.com/article/227-how-can-i-transform-a-video-link-into-a-player-using-the-custom-content-field))
* Fixed: Issue when embedding multiple DataTables views in the same page
* Tweak: A more robust "Save View" procedure to prevent losing field configuration on certain browsers
* Updated Translations:
	- Bengali translation by [@tareqhi](https://www.transifex.com/accounts/profile/tareqhi/)
	- Turkish translation by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)

= 1.6.1 on February 17 =
* Added: Allow Recent Entries to have an Embed Page ID
* Fixed: # of Recent Entries not saving
* Fixed: Link to Embed Entries how-to on the Welcome page
* Fixed: Don't show "Please select View to search" message until Search Widget is saved
* Fixed: Minor Javascript errors for new WordPress Search Widget
* Fixed: Custom template loading from the theme directory
* Fixed: Adding new search fields to the Search Bar widget in the Edit View screen
* Fixed: Entry creators can edit their own entries in Gravity Forms 1.9+
* Fixed: Recent Entries widget will be hidden in the Customizer preview until View ID is configured
* Tweak: Added Floaty icon to Customizer widget selectors
* Updated: Hungarian, Norwegian, Portuguese, Swedish, Turkish, and Spanish translations (thanks to all the translators!)

= 1.6 on February 12 =
* Our support site has moved to [docs.gravitykit.com](https://docs.gravitykit.com). We hope you enjoy the improved experience!
* Added: GravityView Search Widget - Configure a WordPress widget that searches any of your Views. [Read how to set it up](https://docs.gravitykit.com/article/222-the-search-widget)
* Added: Duplicate View functionality allows you to clone a View from the All Views screen. [Learn more](https://docs.gravitykit.com/article/105-how-to-duplicate-or-copy-a-view)
* Added: Recent Entries WordPress Widget - show the latest entries for your View. [Learn more](https://docs.gravitykit.com/article/223-the-recent-entries-widget)
* Added: Embed Single Entries - You can now embed entries in a post or page! [See how](https://docs.gravitykit.com/article/105-how-to-duplicate-or-copy-a-view)
* Fixed: Fatal errors caused by Gravity Forms 1.9.1 conflict
* Fixed: Respect Custom Input Labels added in Gravity Forms 1.9
* Fixed: Edit Entry Admin Bar link
* Fixed: Single Entry links didn't work when previewing a draft View
* Fixed: Edit entry validation hooks not running when form has multiple pages
* Fixed: Annoying bug where you would have to click Add Field / Add Widget buttons twice to open the window
* Added: `gravityview_get_link()` function to standardize generating HTML anchors
* Added: `GravityView_API::entry_link_html()` method to generate entry link HTML
* Added: `gravityview_field_entry_value_{$field_type}` filter to modify the value of a field (in `includes/class-api.php`)
* Added: `field_type` key has been added to the field data in the global `$gravityview_view->field_data` array
* Added: `GravityView_View_Data::maybe_get_view_id()` method to determine whether an ID, post content, or object passed to it is a View or contains a View shortcode.
* Added: Hook to customise the text message "You have attempted to view an entry that is not visible or may not exist." - `gravityview/render/entry/not_visible`
* Added: Included in hook `gravityview_widget_search_filters` the labels for search all, entry date and entry id.
* Tweak: Allow [WordPress SEO](http://wordpress.org/plugins/wordpress-seo/) scripts and styles when in "No Conflict Mode"
* Fixed: For Post Dynamic Data, make sure Post ID is set
* Fixed: Make sure search field choices are available before displaying field

= 1.5.4 on January 29, 2015 =
* Added: "Hide View data until search is performed" setting - only show the Search Bar until a search is entered
* Added: "Clear" button to your GravityView Search Bar - allows easy way to remove all searches & filters
* Added: You can now add Custom Content GravityView Widgets (not just fields) - add custom text or HTMLin the header or footer of a View
* Added: `gravityview/comments_open` filter to modify whether comments are open or closed for GravityView posts (previously always false)
* Added: Hook to filter the success Edit Entry message and link `gravityview/edit_entry/success`
* Added: Possibility to add custom CSS classes to multiple view widget wrapper ([Read how](https://www.gravitykit.com/support/documentation/204144575/))
* Added: Field option to enable Live Post Data for Post Image field
* Fixed: Loading translation files for Extensions
* Fixed: Edit entry when embedding multiple views for the same form in the same page
* Fixed: Conflicts with Advanced Filter extension when embedding multiple views for the same form in the same page
* Fixed: Go Back link on embedded single entry view was linking to direct view url instead of page permalink
* Fixed: Searches with quotes now work properly
* Tweak: Moved `includes/css/`, `includes/js/` and `/images/` folders into `/assets/`
* Tweak: Improved the display of the changelog (yes, "this is *so* meta!")
* Updated: Swedish translation - thanks, [@adamrehal](https://www.transifex.com/accounts/profile/adamrehal/)
* Updated: Hungarian translation - thanks, [@Darqebus](https://www.transifex.com/accounts/profile/Darqebus/) (a new translator!) and [@dbalage](https://www.transifex.com/accounts/profile/dbalage/)

= 1.5.3 on December 22 =
* Fixed: When adding more than 100 fields to the View some fields weren't saved.
* Fixed: Do not set class tickbox for non-images files
* Fixed: Display label "Is Fulfilled" on the search bar
* Fixed: PHP Notice with Gravity Forms 1.9 and PHP 5.4+
* Tested with Gravity Forms 1.9beta5 and WordPress 4.1
* Updated: Turkish translation by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/) and Hungarian translation by [@dbalage](https://www.transifex.com/accounts/profile/dbalage/). Thanks!

= 1.5.2 on December 11 =
* Added: Possibility to show the label of Dropdown field types instead of the value ([learn more](https://www.gravitykit.com/support/documentation/202889199/ "How to display the text label (not the value) of a dropdown field?"))
* Fixed: Sorting numeric columns (field type number)
* Fixed: View entries filter for Featured Entries extension
* Fixed: Field options showing delete entry label
* Fixed: PHP date formatting now keeps backslashes from being stripped
* Modified: Allow license to be defined in `wp-config.php` ([Read how here](https://www.gravitykit.com/support/documentation/202870789/))
* Modified: Added `$post_id` parameter as the second argument for the `gv_entry_link()` function. This is used to define the entry's parent post ID.
* Modified: Moved `GravityView_API::get_entry_id_from_slug()` to `GVCommon::get_entry_id_from_slug()`
* Modified: Added second parameter to `gravityview_get_entry()`, which forces the ability to fetch an entry by ID, even if custom slugs are enabled and `gravityview_custom_entry_slug_allow_id` is false.
* Updated Translations:
	- Bengali translation by [@tareqhi](https://www.transifex.com/accounts/profile/tareqhi/)
	- Romanian translation by [@ArianServ](https://www.transifex.com/accounts/profile/ArianServ/)
	- Mexican Spanish translation by [@jorgepelaez](https://www.transifex.com/accounts/profile/jorgepelaez/)

= 1.5.1 on December 2 =

* Added: Delete Entry functionality!
	- New "User Delete" setting allows the user who created an entry to delete it
	- Adds a "Delete" link in the Edit Entry form
	- Added a new "Delete Link" Field to the Field Picker
* Fixed: DataTables Extension hangs when a View has Custom Content fields
* Fixed: Search Bar - When searching on checkbox field type using multiselect input not returning results
* Fixed: Search Bar - supports "Match Any" search mode by default ([learn more](https://www.gravitykit.com/support/documentation/202722979/ "How do I modify the Search mode?"))
* Fixed: Single Entry View title when view is embedded
* Fixed: Refresh the results cache when an entry is deleted or is approved/disapproved
* Fixed: When users are created using the User Registration Addon, the resulting entry is now automatically assigned to them
* Fixed: Change cache time to one day (from one week) so that Edit Link field nonces aren't invalidated
* Fixed: Incorrect link shortening for domains when it is second-level (for example, `example.co.uk` or `example.gov.za`)
* Fixed: Cached directory link didn't respect page numbers
* Fixed: Edit Entry Admin Bar link wouldn't work when using Custom Entry Slug
* Added: Textarea field now supports an option to trim the number of words shown
* Added: Filter to alter the default behaviour of wrapping images (or image names) with a link to the content object ([learn more](https://www.gravitykit.com/support/documentation/202705059/ "Read the support doc for the filter"))
* Updated: Portuguese translation (thanks [@luistinygod](https://www.transifex.com/accounts/profile/luistinygod/)), Mexican translation (thanks, [@jorgepelaez](https://www.transifex.com/accounts/profile/jorgepelaez/)), Turkish translation (thanks [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/))

= 1.5 on November 12 =
* Added: New "Edit Entry" configuration
	- Configure which fields are shown when editing an entry
	- Set visibility for the fields (Entry Creator, Administrator, etc.)
	- Set custom edit labels
* Fixed: Single entry view now respects View settings
	- If an entry isn't included in View results, the single entry won't be available either
	- If "Show Only Approved" is enabled, prevent viewing of unapproved entries
	- Respects View filters, including those added by the Advanced Filtering extension
* Fixed: Single entry Go back button context on Embedded Views
* Fixed: Delete signature fields in Edit Entry (requires the Gravity Forms Signature Addon)
* Fixed: Gravity Forms tooltip translations being overridden
* Added: Choose to open the link from a website field in the same window (field option)
* Updated: Spanish (Mexican) translation by [@jorgepelaez](https://www.transifex.com/accounts/profile/jorgepelaez/), Dutch translation by [@erikvanbeek](https://www.transifex.com/accounts/profile/erikvanbeek/) and [@leooosterloo](https://www.transifex.com/accounts/profile/leooosterloo/), Turkish translation by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)

= 1.4 on October 28 =
* Added: Custom entry slug capability. Instead of `/entry/123`, you can now use entry values in the URL, like `/entry/{company name}/` or `/entry/{first name}-{last name}/`. Requires some customization; [learn more here](https://www.gravitykit.com/support/documentation/202239919)
* Fixed: GravityView auto-updater script not showing updates
* Fixed: Edit Entry when a form has required Upload Fields
* Fixed: "Return to Directory" link not always working for sites in subdirectories
* Fixed: Broken links to single entries when viewing paginated results
* Fixed: Loaded field configurations when using "Start Fresh" presets
* Fixed: Searches ending in a space caused PHP warning
* Fixed: Custom "Edit Link Text" settings respected
* Fixed: Don't rely on Gravity Forms code for escaping query
* Fixed: When multiple Views are displayed on a page, Single Entry mode displays empty templates.
* Fixed: PHP error when displaying Post Content fields using Live Data for a post that no longer is published
* Tweak: Search Bar "Links" Input Type
	- Make link bold when filter is active
	- Clicking on an active filter removes the filter
* Tweak: Fixed updates for Multisite installations
* Modified: Now you can override which post a single entry links to. For example, if a shortcode is embedded on a home page and you want single entries to link to a page with an embedded View, not the View itself, you can pass the `post_id` parameter. This accepts the ID of the page where the View is embedded.
* Modified: Added `$add_pagination` parameter to `GravityView_API::directory_link()`
* Added: Indonesian translation (thanks, [@sariyanta](https://www.transifex.com/accounts/profile/sariyanta/))!
* Updated: Swedish translation 100% translated - thanks, [@adamrehal](https://www.transifex.com/accounts/profile/adamrehal/)!
* Updated: Dutch translation (thanks, [@leooosterloo](https://www.transifex.com/accounts/profile/leooosterloo/))!

= 1.3 on October 13 =
* Speed improvements - [Learn more about GravityView caching](https://www.gravitykit.com/support/documentation/202827685/)
	- Added caching functionality that saves results to be displayed
	- Automatically clean up expired caches
	- Reduce number of lookups for where template files are located
	- Store the path to the permalink for future reference when rendering a View
	- Improve speed of Gravity Forms fetching field values
* Modified: Allow `{all_fields}` and `{pricing_fields}` Merge Tags in Custom Content field. [See examples of how to use these fields](https://www.gravitykit.com/support/documentation/201874189/).
* Fixed: Message restored when creating a new View
* Fixed: Searching advanced input fields
* Fixed: Merge Tags available immediately when adding a new field
* Fixed: Issue where jQuery Cookie script wouldn't load due to `mod_security` issues. [Learn more here](http://docs.woothemes.com/document/jquery-cookie-fails-to-load/)
* Fixed (hopefully): Auto-updates for WordPress Multisite
* Fixed: Clicking overlay to close field/widget settings no longer scrolls to top of page
* Fixed: Make sure Gravity Forms scripts are added when embedding Gravity Forms shortcodes in a Custom Field
* Fixed: Remove double images of Floaty in the warning message when GravityView is disabled
* Fixed: PHP warnings related to Section field descriptions
* Fixed: When using an advanced input as a search field in the Search Bar, the label would always show the parent field's label (Eg: "Address" when it should have shown "City")
	- Added: `gravityview_search_field_label` filter to allow modifying search bar labels
* Fixed: Field label disappears on closing settings if the field title is empty
* Fixed: Sub-fields retain label after opening field settings in the View Configuration
* Modified: Allow passing an array of form IDs to `gravityview_get_entries()`
* Tweak: If the View hasn't been configured yet, don't show embed shortcode in Publish metabox
* Tweak: Add version info to scripts and styles to clear caches with plugin updates
* Added: Swedish translation (thanks, [@adamrehal](https://www.transifex.com/accounts/profile/adamrehal/))!
* Updated: Spanish (Mexican) translation by, [@jorgepelaez](https://www.transifex.com/accounts/profile/jorgepelaez/), Dutch translation by [@erikvanbeek](https://www.transifex.com/accounts/profile/erikvanbeek/), and Turkish translation by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)
* Updated: Changed Turkish language code from `tr` to `tr_TR` to match WordPress locales

= 1.2 on October 8 =
* Added: New Search Bar!
	- No longer check boxes in each field to add a field to the search form
	- Add any searchable form fields, not just fields added to the View
	- Easy new drag & drop way to re-order fields
	- Horizontal and Vertical layouts
	- Choose how your search fields are displayed (if you have a checkbox field, for example, you can choose to have a drop-down, a multiselect field, checkboxes, radio buttons, or filter links)
	- Existing search settings will be migrated over on upgrade
* Added: "Custom Content" field type
	- Insert arbitrary text or HTML in a View
	- Supports shortcodes (including Gravity Forms shortcodes)!
* Added: Support for Gravity Forms Section & HTML field types
* Added: Improved textarea field support. Instead of using line breaks, textareas now output with paragraphs.
	- Added new `/templates/fields/textarea.php` file
* Added: A new File Upload field setting. Force uploads to be displayed as links and not visually embedded by checking the "Display as a Link" checkbox.
* Added: Option to disable "Map It" link for the full Address field.
	- New `gravityview_get_map_link()` function with `gravityview_map_link` filter. To learn how to modify the map link, [refer to this how-to article](https://www.gravitykit.com/support/documentation/201608159)
	- The "Map It" string is now translatable
* Added: When editing a View, there are now links in the Data Source box to easily access the Form: edit form, form entries, form settings and form preview
* Added: Additional information in the "Add Field" or "Add Widget" picker (also get details about an item by hovering over the name in the View Configuration)
* Added: Change Entry Creator functionality. Easily change the creator of an entry when editing the entry in the Gravity Forms Edit Entry page
	- If you're using the plugin downloaded from [the how-to page](https://www.gravitykit.com/support/documentation/201991205/), you can de-activate it
* Modified: Changed translation textdomain to `gravityview` instead of `gravity-view`
* Modified: Always show label by default, regardless of whether in List or Table View type
* Modified: It's now possible to override templates on a Form ID, Post ID, and View ID basis. This allows custom layouts for a specific View, rather than site-wide. See "Template File Hierarchy" in [the override documentation](http://www.gravitykit.com/support/documentation/202551113/) to learn more.
* Modified: File Upload field output no longer run through `wpautop()` function
* Modified: Audio and Video file uploads are now displayed using WordPress' built-in [audio](http://codex.wordpress.org/Audio_Shortcode) and [video](http://codex.wordpress.org/Video_Shortcode) shortcodes (requires WordPress 3.6 or higher)
	- Additional file type support
	- Added `gravityview_video_settings` and `gravityview_audio_settings` filters to modify the parameters passed to the shortcode
* Fixed: Shortcode attributes not overriding View defaults
* Fixed: Uploading and deleting files works properly in Edit Entry mode
* Fixed: Configurations get truncated when configuring Views with many fields
* Fixed: Empty `<span class="gv-field-label">` tags no longer output
	- Modified: `gv_field_label()` no longer returns the label with a trailing space. Instead, we use the `.gv-field-label` CSS class to add spacing using CSS padding.
* Fixed: Conflict with Relevanssi plugin
* Fixed: If a date search isn't valid, remove the search parameter so it doesn't cause an error in Gravity Forms
* Fixed: Email field was displaying label even when email was empty.
* Settings page improvements
	- When changing the license value and saving the form, GravityView now re-checks the license status
	- Improved error messages
	- Made license settings translatable
* Modified: Added support for Gravity Forms "Post Image" field captions, titles, and descriptions.
* Updated list of allowed image formats to include `.bmp`, `.jpe`, `.tiff`, `.ico`
* Modified: `/templates/fields/fileupload.php` file - removed the logic for how to output the different file types and moved it to the `gravityview_get_files_array()` function in `includes/class-api.php`
* Modified: `gv_value()` no longer needs the `$field` parameter
* Tweak: Fixed email setting description text.
* Tweak: Don't show Entry Link field output on single entry
* Tweak: Improved Javascript performance in the Admin
* Tweak: "Custom Label" is now shown as the field title in View Configuration
* Tweak: Fixed "Left Footer" box not properly cleared
* Tweak: Show warning if the Directory plugin is running
* Tweak: Use icon font in Edit Entry mode for the download/delete file buttons. Now stylable using `.gv-edit-entry-wrapper .dashicons` CSS class.
* Updated: Turkish translation by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/), Dutch translation by [@leooosterloo](https://www.transifex.com/accounts/profile/leooosterloo/), Portuguese translation by [@luistinygod](https://www.transifex.com/accounts/profile/luistinygod/)

= 1.1.6 on September 8 =
* Fixed: Approve / Disapprove all entries using Gravity Forms bulk edit entries form (previously, only visible entries were affected)
* Added: Email field settings
	- Email addresses are now encrypted by default to prevent scraping by spammers
	- Added option to display email plaintext or as a link
	- Added subject and body settings: when the link is clicked, you can choose to have these values pre-filled
* Added: Source URL field settings, including show as a link and custom link text
* Added: Signature field improvements (when using the Gravity Forms Signature Add-on) - now shows full size
* Fixed: Empty truncated URLs no longer get shown
* Fixed: License Activation works when No-Conflict Mode is enabled
* Fixed: When creating a new View, "View Type" box was visible when there were no existing Gravity Forms
* Fixed: Fields not always saving properly when adding lots of fields with the "Add All Fields" button
* Fixed: Recognizing single entry when using WordPress "Default" Permalink setting
* Fixed: Date Created field now respects the blog's timezone setting, instead of using UTC time
* Fixed: Edit Entry issues
	* Fixed form validation errors when a scheduled form has expired and also when a form has reached its entry limit
	* Fixed PHP warning messages when editing entries
	* When an Edit Entry form is submitted and there are errors, the submitted values stay in the form; the user won't need to fill in the form again.
* Fixed: Product sub-fields (Name, Quantity & Price) displayed properly
* Fixed: Empty entry display when using Job Board preset caused by incorrect template files being loaded
* Fixed: Files now can be deleted when a non-administrator is editing an entry
* Fixed: PHP Notices on Admin Views screen for users without edit all entries capabilities
* Modified: Added ability to customize and translate the Search Bar's date picker. You can now fully customize the date picker.
	* Added: Full localization for datepicker calendar (translate the days of the week, month, etc)
	* Modified: Changed year picker to +/- 5 years instead of +20/-100
* Tweak: Enabled Merge Tags for Table view "Custom CSS Class" field settings
* Tweak: In the Edit View screen, show a link icon when a field is being used as a link to the Single Entry mode
* Tweak: Added helper text when a new form is created by GravityView
* Tweak: Renamed "Description" drop zone to "Other Fields" to more accurately represent use
* Tweak: Remove all fields from a zone by holding down the Alt key while clicking the remove icon

#### Developers

* Modified: `template/fields/date_created.php` file
* Added: `gravityview_date_created_adjust_timezone` filter to disable timezone support and use UTC (returns boolean)
* Added: `get_settings()` and `get_setting()` methods to the `GravityView_Widget` class. This allows easier access to widget settings.
* Modified: Added `gravityview_js_localization` filter to add Javascript localization
* Added: `gravityview_datepicker_settings` filter to modify the datepicker settings using the setting names from the [jQuery DatePicker options](http://api.jqueryui.com/datepicker/)
* Modified: `gravityview_entry_class` filter to modify the CSS class for each entry wrapper
* Modified: Added `gravityview_widget_search_filters` filter to allow reordering search filters, so that they display in a different order in search widget
* Modified: Addded `gravityview_default_page_size` filter to modify default page size for Views (25 by default)
* Modified: Added actions to the `list-body.php` template file:
	- `gravityview_list_body_before`: Before the entry output
	- `gravityview_entry_before`: Inside the entry wrapper
	- `gravityview_entry_title_before`, `gravityview_entry_title_after`: Before and after the entry title and subtitle output
	- `gravityview_entry_content_before`, `gravityview_entry_content_after`: Before and after the entry content area (image and description zones)
	- `gravityview_entry_footer_before`, `gravityview_entry_footer_after`: Before and after the entry footer
	- `gravityview_entry_after`: Before the entry wrapper closing tag
	- `gravityview_list_body_after`: After entry output
* Modified: Added `gravityview_get_entry_ids()` function to fetch array of entry IDs (not full entry arrays) that match a search result
* Tweak: Removed duplicate `GravityView_frontend::hide_field_check_conditions()` and `GravityView_frontend::filter_fields()` methods
* Modified: Added `get_cap_choices()` method to be used for fetching GravityView roles array

= 1.1.5 =
* Added: "Edit" link in Gravity Forms Entries screen
* Fixed: Show tooltips when No Conflict Mode is enabled
* Fixed: Merge Vars for labels in Single Entry table layouts
* Fixed: Duplicate "Edit Entry" fields in field picker
* Fixed: Custom date formatting for Date Created field
* Fixed: Searching full names or addresses now works as expected
* Fixed: Custom CSS classes are now added to cells in table-based Views
* Updated: Turkish translation by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)
* Tweak: Redirect to Changelog instead of Getting Started if upgrading

= 1.1.4 =
* Fixed: Sort & Filter box not displaying
* Fixed: Multi-select fields now display as drop-down field instead of text field in the search bar widget
* Fixed: Edit Entry now compatibile with Gravity Forms forms when "No Duplicates" is enabled
* Added: `gravityview_field_output()` function to generate field output.
* Added: `gravityview_page_links_args` filter to modify the Page Links widget output. Passes standard [paginate_links()](http://codex.wordpress.org/Function_Reference/paginate_links) arguments.
* Modified: `list-body.php` and `list-single.php` template files - field output are now generated using the `gravityview_field_output()` function

= 1.1.3 =
* Fixed: Fatal error on activation when running PHP 5.2
* Fixed: PHP notice when in No-Conflict mode

= 1.1.2 =
* Added: Extensions framework to allow for extensions to auto-update
* Fixed: Entries not displaying in Visual Composer plugin editor
* Fixed: Allow using images as link to entry
* Fixed: Updated field layout in Admin to reflect actual layout of listings (full-width title and subtitle above image)
* Fixed: Editing entry updates the Approved status
* Fixed: When trying to access an entry that doesn't exist (it had been permanently deleted), don't throw an error
* Fixed: Default styles not being enqueued when embedded using the shortcode (fixes vertical pagination links)
* Fixed: Single entry queries were being run twice
* Fixed: Added Enhanced Display style in Edit Entry mode
* Modified: How single entries are accessed; now allows for advanced filtering. Converted `gravityview_get_entry()` to use `GFAPI::get_entries()` instead of `GFAPI::get_entry()`
* Modified: Form ID can be 0 in `gravityview_get_entries()`
* Modified: Improved Edit Entry styling
* Modified: Convert to using `GravityView_View_Data::get_default_args()` instead of duplicating the settings arrays. Used for tooltips, insert shortcode dialog and View metaboxes.
* Modified: Add a check for whether a view exists in `GravityView_View_Data::add_view()`
* Modified: Convert `GravityView_Admin_Views::render_select_option()` to use the key as the value and the value as the label instead of using associative array with `value` and `label` keys.
* Translation updates - thank you, everyone!
	* Romanian translation by [@ArianServ](https://www.transifex.com/accounts/profile/ArianServ/)
	* Finnish translation by [@harjuja](https://www.transifex.com/accounts/profile/harjuja/)
	* Spanish translation by [@jorgepelaez](https://www.transifex.com/accounts/profile/jorgepelaez/)

= 1.1.1 =
* __We fixed license validation and auto-updates__. Sorry for the inconvenience!
* Added: View Setting to allow users to edit only entries they created.
* Fixed: Could not edit an entry with Confirm Email fields
* Fixed: Field setting layouts not persisting
* Updated: Bengali translation by [@tareqhi](https://www.transifex.com/accounts/profile/tareqhi/)
* Fixed: Logging re-enabled in Admin
* Fixed: Multi-upload field button width no longer cut off
* Tweak: Added links to View Type picker to live demos of presets.
* Tweak: Added this "List of Changes" tab.

= 1.1 =
* Refactored (re-wrote) View data handling. Now saves up to 10 queries on each page load.
* Fixed: Infinite loop for rendering `post_content` fields
* Fixed: Page length value now respected for DataTables
* Fixed: Formatting of DataTables fields is now processed the same way as other fields. Images now work, for example.
* Modified: Removed redundant `gravityview_hide_empty_fields` filters
* Fixed/Modified: Enabled "wildcard" search instead of strict search for field searches.
* Added: `gravityview_search_operator` filter to modify the search operator used by the search.
* Added: `gravityview_search_criteria` filter to modify all search criteria before being passed to Gravity Forms
* Added: Website Field setting to display shortened link instead of full URL
* Fixed: Form title gets replaced properly in merge tags
* Modified: Tweaked preset templates

= 1.0.10 =
* Added: "Connected Views" in the Gravity Forms Toolbar. This makes it simple to see which Views are using the current form as a data source.
* Fixed: Edit Entry link in Multiple Entries view

= 1.0.9 on July 18 =
* Added: Time field support, with date format default and options
* Added: "Event Listings" View preset
* Added: "Show Entry On Website" Gravity Forms form button. This is meant to be an opt-in checkbox that the user sees and can control, unlike the "Approve/Reject" button, which is designed for adminstrators to manage approval.
* Modified: Improved horizontal search widget layout
* Modified: Improved "Start Fresh" and "Switch View" visual logic when Starting Fresh and switching forms
* Fixed: Single Entry showing 404 errors
* Fixed: PHP notice on WooCommerce pages
* Fixed: Don't display empty date/time value
* Fixed: Only show Edit Entry link to logged-in users
* Fixed: Re-enabled "Minimum Gravity Forms Version" error message
* Updated: Dutch translation by [@leooosterloo](https://www.transifex.com/accounts/profile/leooosterloo/) (100% coverage, thank you!)
* Tweak: Added "Preview" link to Data Source
* Modified: Created new `class-post-types.php` include file to handle post type & URL rewrite actions.

= 1.0.8.1 on July 17 =
* Fixed: DataTables
	- Restored pageSize
	- Prevented double-initilization
	- FixedHeader & FixedColumns work (now prevent scrolling)
	- Changed default Scroller height from 400 to 500px
* Fixed: Filtering by date
* Fixed: PHP warning in `gv_class()`
* Fixed: Debug Bar integration not printing Warnings
* Removed settings panel tracking script

= 1.0.7 & 1.0.8 on July 17 =
* __Edit Entry__ - you can add an Edit Entry link using the "Add Field" buttons in either the Multiple Entries or Single Entry tab.
	- For now, if the user has the ability to edit entries in Gravity Forms, they’ll be able to edit entries in GravityView. Moving forward, we'll be adding refined controls over who can edit which entries.
	- It supports modifying existing Entry uploads and the great Multiple-File Upload field.
* Modified: Approved Entry functionality
	* Approve/Reject Entries now visible on all forms, regardless of whether the form has an "Approved" field.
	* The Approved field now supports being renamed
* Added: Very cool DataTables extensions:
	* Scroller: dynamically load in new entries as you scroll - no need for pagination)
	* TableTools: Export your entries to CSV and PDF
	* FixedHeader: As you scroll a large DataTable result, the headers of the table stay at the top of the screen. Also, FixedColumns, which does the same for the main table column.
* Added: Shortcodes for outputting Widgets such as pagination and search. Note: they only work on embedded views if the shortcode has already been processed. This is going to be improved.
* Added: Search form fields now displayed horizontally by default.
* Added: Easy links to "Edit Form", "Settings" and "Entries" for the Data Source Gravity Forms form in the All Views admin screen
* Added: Integration with the [Debug Bar](http://wordpress.org/plugins/debug-bar/) plugin - very helpful for developers to see what's going on behind the scenes.
* Fixed: Insert View embed code.
* Fixed: Now supports View shortcodes inside other shortcodes (such as `[example][gravityview][/example]`)
* Fixed: Conflict with WordPress SEO OpenGraph meta data generators
* Fixed: Enforced image max-width so images don't spill out of their containers
* Fixed: Sanitized "Custom Class" field setting values to make sure the HTML doesn't break.
* Fixed: Search field with "default" permalink structure
* Fixed: 1.0.8 fixes an issue accessing single entries that was introduced in 1.0.7
* Modified: Updated `GravityView_Admin_Views::is_gravityview_admin_page()` to fetch post if not yet set.
* Modified: Enabled merge tags in Custom Class field settings
* Modified: Set margin and padding to `0` on pagination links to override theme conflicts
* Modified: Updated `gv_class()` calls to pass form and entry fields to allow for merge tags
* Modified: Default visibility capabilities: added "Can View/Edit Gravity Forms Entries" as options
* Modified: Added custom `class` attribute sanitizer function
`gravityview_sanitize_html_class`
* Tweak: Improved the Embed View form layout
* Tweak: Hide "Switch View" button when already choosing a view
* Tweak: Moved shortcode hint to Publish metabox and added ability to easily select the text
* Tweak: Added tooltips to fields in the View editor
* Tweak: Remove WordPress SEO score calculation on Views
* Tweak: Use `$User->ID` instead of `$User->id` in Name fields
* Tweak: Added tooltip capability to field settings by using `tooltip` parameter. Uses the Gravity Forms tooltip array key.
* Translation updates - thank you, everyone! The # of strings will stay more stable once the plugin's out of beta :-)
	* Added: Portuguese translation by [@luistinygod](https://www.transifex.com/accounts/profile/luistinygod/) - thanks!
	* Updated: Bengali translation by [@tareqhi](https://www.transifex.com/accounts/profile/tareqhi/)
	* Updated: Turkish translation by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)
	* Updated: Dutch translation by [@leooosterloo](https://www.transifex.com/accounts/profile/leooosterloo/)
	* If you'd like to contribute translations, [please sign up here](https://www.transifex.com/projects/p/gravityview/).


= 1.0.6 on June 26 =
* Fixed: Fatal error when Gravity Forms is inactive
* Fixed: Undefined index for `id` in Edit View
* Fixed: Undefined variable: `merge_class`
* Fixed: Javascript error when choosing a Start Fresh template. (Introduced by the new Merge Tags functionality in 1.0.5)
* Fixed: Merge Tags were available in Multiple Entries view for the Table layout
* Fixed: Remove Merge Tags when switching forms
* Fixed: That darn settings gear showing up when it shouldn't
* Fixed: Disappearing dialog when switching forms
* Fixed: Display of Entry Link field
* Fixed: Per-field settings weren't working
	* Added: "Link to the post" setting for Post fields
	* Added: "Use live post data" setting for Post fields. Allows you to use the current post information (like title, tags, or content) instead of the original submitted data.
	* Added: Link to category or tag setting for Post Categories and Post Tags fields
	* Added: "Link Text" setting for the Entry Link field
* Modified: Moved admin functionality into new files
	- AJAX calls now live in `class-ajax.php`
	- Metaboxes now live in `class-metabox.php`
* Tweak: Updated change forms dialog text
* Tweak: Removed "use as search filter" from Link to Entry field options
* Translation updates.
	* Added: French translation by [@franckt](https://www.transifex.com/accounts/profile/franckt/) - thanks!
	* Updated: Bengali translation by [@tareqhi](https://www.transifex.com/accounts/profile/tareqhi/)
	* Updated: Turkish translation by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)
	* If you'd like to contribute translations, [please sign up here](https://www.transifex.com/projects/p/gravityview/).

= 1.0.5 =
* Added: Lightbox for images (in View Settings metabox)
* Added: Merge Tags - You can now modify labels and settings using dynamic text based on the value of a field. (requires Gravity Forms 1.8.6 or higher)
* Added: Customize the return to directory link anchor text (in the View Settings metabox, under Single Entry Settings)
* Added: Set the title for the Single Entry
* Added: Choose whether to hide empty fields on a per-View basis
* Improved: DataTables styling now set to `display` by default. Can be overridden by using the filter `gravityview_datatables_table_class`
* Improved: Speed!
	* Added `form` item to global `$gravityview_view` data instead of looking it up in functions. Improves `gv_value()` and `gv_label()` speed.
	* Added `replace_variables()` method to `GravityView_API` to reduce time to process merge tags by checking if there are any curly brackets first.
* Improved: "No Views found" text now more helpful for getting started.
* Fixed: Approve Entries column not displaying when clicking Forms > Entries link in admin menu
* Fixed: Field Settings gear no longer showing for widgets without options
* Fixed: Added Gravity Forms minimum version notice when using < 1.8
* Fixed: Column "Data Source" content being displayed in other columns

= 1.0.4 =
* Added: __DataTables integration__ Created a new view type for existing forms that uses the [DataTables](http://datatables.net) script.
We're just getting started with what can be done with DataTables. We'll have much more cool stuff like [DataTables Extensions](http://datatables.net/extensions/index).
* Added: "Add All Fields" option to bottom of the "Add Field" selector
* Added: Per-field-type options structure to allow for different field types to override default Field Settings
	* Added: Choose how to display User data. In the User field settings, you can now choose to display the "Display Name", username, or ID
	* Added: Custom date format using [PHP date format](https://www.php.net//manual/en/function.date.php) available for Entry Date and Date fields
	* Fixed: Default setting values working again
	* Fixed: Field type settings now working
* Added: `search_field` parameter to the shortcode. This allows you to specify a field ID where you want the search performed (The search itself is defined in `search_value`)
* Added: [Using the Shortcode](https://docs.gravitykit.com/article/73-using-the-shortcode) help article
* Added: Data Source added to the Views page
* Fixed: Field labels escaping issue (`It's an Example` was displaying as `It\'s an Example`)
* Fixed: Settings "gear" not showing when adding a new field
* Fixed: Sorting issues
	- Remove the option to sort by composite fields like Name, Address, Product; Gravity Forms doesn't process those sort requests properly
	- Remove List and Paragraph fields from being sortable
	- Known bug: Price fields are sorted alphabetically, not numerically. For example, given $20,000, $2,000 and $20, Gravity Forms will sort the array like this: $2,000, $20, $20,000. We've filed a bug report with Gravity Forms.
* Improved: Added visibility toggles to some Field Settings. For example, if the "Show Label" setting is not checked, then the "Custom Label" setting is hidden.
* Modified how data is sent to the template: removed the magic methods getter/setters setting the `$var` variable - not data is stored directly as object parameters.
* Added many translations. Thanks everyone!
	* Bengali translation by [@tareqhi](https://www.transifex.com/accounts/profile/tareqhi/)
	* German translation by [@seschwarz](https://www.transifex.com/accounts/profile/seschwarz/)
	* Turkish translation by [@suhakaralar](https://www.transifex.com/accounts/profile/suhakaralar/)
	* Dutch translation by [@leooosterloo](https://www.transifex.com/accounts/profile/leooosterloo/)
	* If you'd like to contribute translations, [please sign up here](https://www.transifex.com/projects/p/gravityview/). Thanks again to all who have contributed!

= 1.0.3 =
* Added: Sort by field, sort direction, Start & End date now added to Post view
	- Note: When using the shortcode, the shortcode settings override the View settings.
* Fixed: Fatal errors caused by Gravity Forms not existing.
* Added a setting for Support Email - please make sure your email is accurate; otherwise we won't be able to respond to the feedback you send
* Fixed: Custom CSS classes didn't apply to images in list view
* Improved Settings layout
* Tweak: Hide WordPress SEO, Genesis, and WooThemes metaboxes until a View has been created
* Tweak: Field layout improvements; drag-and-drop works smoother now
* Tweak: Add icon to Multiple Entries / Single Entry tabs
* Tweak: Dialog boxes now have a backdrop
* Fixed: Don't show field/widget settings link if there are no settings (like on the Show Pagination Info widget)
* Fixed: Security warning by the WordFence plugin: it didn't like a line in a sample entry data .csv file
* Fixed: Don't show welcome screen on editing the plugin using the WordPress Plugin Editor
* Tweak: Close "Add Field" and "Add Widget" boxes by pressing the escape key
* Added: Hungarian translation. Thanks, [@dbalage](https://www.transifex.com/accounts/profile/dbalage/)!
* Added: Italian translation. Thanks, [@ClaraDiGennaro](https://www.transifex.com/accounts/profile/ClaraDiGennaro/)
* If you'd like to contribute translations, [please sign up here](https://www.transifex.com/projects/p/gravityview/).

= 1.0.2 =
* Added: Show Views in Nav menu builder
* Fixed: "Add Fields" selector no longer closes when clicking to drag the scrollbar
* Fixed: Issue affecting Gravity Forms styles when Gravity Forms' "No Conflict Mode" is enabled
* Fixed: Footer widget areas added back to Single Entry views using Listing layout
* Changed the look and feel of the Add Fields dialog and field settings. Let us know what you think!

= 1.0.1 =
* Added: "Getting Started" link to the Views menu
* Fixed: Fatal error for users with Gravity Forms versions 1.7 or older
* Fixed: Entries in trash no longer show in View
* Tweak: When modifying the "Only visible to logged in users with role" setting, if choosing a role other than "Any", check the checkbox.
* Tweak: `gravityview_field_visibility_caps` filter to add/remove capabilities from the field dropdowns
* Added: Translation files. If you'd like to contribute translations, [please sign up here](https://www.transifex.com/projects/p/gravityview/).

= 1.0 =

* Liftoff!

== Upgrade Notice ==

= 1.0.1 =
* Added: "Getting Started" link to the Views menu
* Fixed: Fatal error for users with Gravity Forms versions 1.7 or older
* Fixed: Entries in trash no longer show in View
* Tweak: When modifying the "Only visible to logged in users with role" setting, if choosing a role other than "Any", check the checkbox.
* Tweak: `gravityview_field_visibility_caps` filter to add/remove capabilities from the field dropdowns
* Added: Translation files. If you'd like to contribute translations, [please sign up here](https://www.transifex.com/projects/p/gravityview/).

= 1.0 =

* Liftoff!
