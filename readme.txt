=== GravityView ===
Tags: gravity forms, directory, gravity forms directory
Requires at least: 3.3
Tested up to: 4.8.1
Stable tag: trunk
Contributors: The GravityView Team
License: GPL 3 or higher

Beautifully display and edit your Gravity Forms entries.

== Description ==

Beautifully display your Gravity Forms entries. Learn more on [gravityview.co](https://gravityview.co).

== Installation ==

1. Upload plugin files to your plugins folder, or install using WordPress' built-in Add New Plugin installer
2. Activate the plugin
3. Follow the instructions

== Changelog ==

= 1.22 on September 4, 2017 =

* Added: Support for Gravity Forms 2.3
* Fixed: Fatal error when Divi (and other Elegant Themes) try to load GravityView widgets while editing a post with a sidebar block in it—now the sidebar block will not be rendered
* Fixed: Inline Edit plugin not working when displaying a single entry
* Fixed: Featured Entries plugin not adding correct CSS selector to the single entry container

__Developer Updates:__

* Modified: Template files `list-header.php`, `list-single.php`, `table-header.php`, `table-single.php`
* Fixed: When `GRAVITYVIEW_LICENSE_KEY` constant is defined, it will always be used, and the license field will be disabled
* Fixed: List View and Table View templates have more standardized CSS selectors for single & multiple contexts ([Learn more](http://docs.gravityview.co/article/63-css-guide))
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

* Added: The `{current_post}` Merge Tag adds information about the current post. [Read more about it](http://docs.gravityview.co/article/412-currentpost-merge-tag).
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

* Fixed: Advanced Filters no longer filtered 😕
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
    - 🇩🇰 Danish *100% translated*
    - 🇳🇴 Norwegian *100% translated*
    - 🇸🇪 Swedish translation updated

__Developer Notes: __

* New: We're starting the migration to a new wrapper API that will awesome. We will be rolling out new functionality and documentation over time. For now, we are just using it to load the plugin. [Very exciting time](https://i.imgur.com/xmkONOD.gif)!
* Fixed: Issue fetching image sizes when using `GravityView_Image` class and fetching from a site with invalid SSL cert.
* Added: `gravityview_directory_link` to modify the URL to the View directory context (in `GravityView_API::directory_link()`)

= 1.19.3 on January 9, 2017 =

First update of 2017! We've got great things planned for GravityView and our Extensions. As always, [contact us](mailto:support@gravityview.co) with any questions or feedback. We don't bite!

* Fixed: List field inputs not loading in Edit Entry when values were empty or the field was hidden initially because of Conditional Logic
* Fixed: Prevent Approve Entry and Delete Entry fields from being added to Edit Entry field configuration
* Fixed: Don't render Views outside "the loop", prevents conflicts with other plugins that run `the_content` filter outside normal places
* Fixed: Only display "You have attempted to view an entry that is not visible or may not exist." warning once when multiple Views are embedded on a page
* Fixed: The `[gravityview]` shortcode would not be parsed properly due to HTML encoding when using certain page builders, including OptimizePress
* Fixed: Potential errors when non-standard form fields are added to Edit Entry configurations ("Creating default object from empty value" and "Cannot use object of type stdClass as array")
* Updated translations:
    - 🇨🇳 Chinese *100% translated* (thank you, Michael Edi!)
    - 🇫🇷 French *100% translated*
    - 🇧🇷 Brazilian Portuguese *100% translated* (thanks, Rafael!)
    - 🇳🇱 Dutch translation updated (thank you, Erik van Beek!)
    - 🇸🇪 Swedish translation updated
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

* New: __Front-end entry moderation__! You can now approve and disapprove entries from the front of a View - [learn how to use front-end entry approval](https://docs.gravityview.co/article/390-entry-approval)
    - Add entry moderation to your View with the new "Approve Entries" field
    - Displaying the current approval status by using the new "Approval Status" field
    - Views have a new "Show all entries to administrators" setting. This allows administrators to see entries with any approval status. [Learn how to use this new setting](http://docs.gravityview.co/article/390-entry-approval#clarify-step-16)
* Fixed: Approval values not updating properly when using the "Approve/Reject" and "User Opt-In" fields
* Tweak: Show inactive forms in the Data Source form dropdown
* Tweak: If a View is connected to a form that is in the trash or does not exist, an error message is now shown
* Tweak: Don't show "Lost in space?" message when searching existing Views
* Added: New Russian translation - thank you, [George Kovalev](https://www.transifex.com/user/profile/gkovaleff/)!
    - Updated: Spanish translation (thanks [@matrixmercury](https://www.transifex.com/user/profile/matrixmercury/))

__Developer Notes:__

* Added: `field-approval.css` CSS file. [Learn how to override the design here](http://docs.gravityview.co/article/388-front-end-approval-css).
* Modified: Removed the bottom border on the "No Results" text (`.gv-no-results` CSS selector)
* Fixed: Deprecated `get_bloginfo()` usage

= 1.18.1 on November 3, 2016 =

* Updated: 100% Chinese translation—thank you [Michael Edi](https://www.transifex.com/user/profile/michaeledi/)!
* Fixed: Entry approval not working when using [custom entry slugs](http://docs.gravityview.co/article/57-customizing-urls)
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
    - New [user capabilities](http://docs.gravityview.co/article/311-gravityview-capabilities) to limit access (`gravityview_add_entry_notes`, `gravityview_view_entry_notes`, `gravityview_delete_entry_notes`, `gravityview_email_entry_notes`)
* Added: Merge Tag modifiers - now set a maximum length of content, and automatically add paragraphs to Merge Tags. [Read how to use the new Merge Tag modifiers](https://docs.gravityview.co/article/350-merge-tag-modifiers).
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
* Tested and works with [Gravity Forms 2.0 Beta 1](https://www.gravityhelp.com/gravity-forms-v2-0-beta-1-released/)

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
* Fixed: Large images in a gallery not resizing (when using [.gv-gallery](http://docs.gravityview.co/article/247-create-a-gallery))
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
* Added: Merge Tags. [See all GravityView Merge Tags](http://docs.gravityview.co/article/76-merge-tags)
    * `{date_created}` The date an entry was created. [Read how to use it here](http://docs.gravityview.co/article/331-date-created-merge-tag).
    * `{payment_date}` The date the payment was received. Formatted using [the same modifiers](http://docs.gravityview.co/article/331-date-created-merge-tag) as `{date_created}`
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
* Fixed: `gravityview/sortable/formfield_{form}_{field_id}` filter [detailed here](http://docs.gravityview.co/article/231-how-to-disable-the-sorting-control-on-one-table-column)
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
* Added: `{get}` Merge Tag that allows passing data via URL to be safely displayed in Merge Tags. [Learn how this works](http://docs.gravityview.co/article/314-the-get-merge-tag).
	- Example: When adding `?first-name=Floaty` to a URL, the Custom Content `My name is {get:first-name}` would be replaced with `My name is Floaty`
* Added: GravityView Capabilities: restrict access to GravityView functionality to certain users and roles. [Learn more](http://docs.gravityview.co/article/311-gravityview-capabilities).
	- Fixed: Users without the ability to create Gravity Forms forms are able to create a new form via "Start Fresh"
	- Only add the Approve Entries column if user has the `gravityview_moderate_entries` capability (defaults to Editor role or higher)
	- Fixed: Contributors now have access to the GravityView "Getting Started" screen
* Added: `[gv_entry_link]` shortcode to link directly to an entry. [Learn more](http://docs.gravityview.co/article/287-edit-entry-and-delete-entry-shortcodes).
	- Existing `[gv_delete_entry_link]` and `[gv_edit_entry_link]` shortcodes will continue to work
* Added: Ability to filter View by form in the Admin. [Learn more](http://docs.gravityview.co/article/313-the-views-list-on-the-dashboard).
* Added: Option to delete GravityView data when the plugin is uninstalled, then deleted. [Learn more](http://docs.gravityview.co/article/312-how-to-delete-the-gravityview-data-when-the-plugin-is-uninstalled).
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
* New: Released a new [GravityView Codex](http://codex.gravityview.co) for developers

= 1.13 on August 20 =
* Fixed: Wildcard search broken for Gravity Forms 1.9.12+
* Fixed: Edit Entry validation messages not displaying for Gravity Forms 1.9.12+
* Added: Number field settings
	- Format number: Display numbers with thousands separators
	- Decimals: Precision of the number of decimal places. Leave blank to use existing precision.
* Added: `detail` parameter to the `[gravityview]` shortcode. [Learn more](http://docs.gravityview.co/article/73-using-the-shortcode#detail-parameter)
* Added: `context` parameter to the `[gvlogic]` shortcode to show/hide content based on current mode (Multiple Entries, Single Entry, Edit Entry). [Learn more](http://docs.gravityview.co/article/252-gvlogic-shortcode#context)
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
* Updated: "Single Entry Title" and "Back Link Label" settings now support shortcodes, allowing for you to use [`[gvlogic]`](http://docs.gravityview.co/article/252-gvlogic-shortcode)
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
    - Developer note: the filter hook `gravityview/field/other_entries/args` was replaced by "gravityview/field/other_entries/criteria". If you are using this filter, please [contact support](mailto:support@gravityview.co) before updating so we can help you transition
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
* Added: `[gv_edit_entry_link]` and `[gv_delete_entry_link]` shortcodes. [Read how to use them](http://docs.gravityview.co/article/287-edit-entry-and-delete-entry-shortcodes)

= 1.9.1 on June 24 =
* Fixed: Allow "Admin Only" fields to appear in Edit Entry form
	- New behavior: If the Edit Entry tab isn't configured in GravityView (which means all fields will be shown by default), GravityView will hide "Admin Only" fields from being edited by non-administrators. If the Edit Entry tab is configured, then GravityView will use the field settings in the configuration, overriding Gravity Forms settings.
* Tweak: Changed `gravityview/edit-entry/hide-product-fields` filter to `gravityview/edit_entry/hide-product-fields` for consistency

= 1.9 on June 23 =
* Added: Edit Entry now takes place in the Gravity Forms form layout, not in the previous layout. This means:
	- Edit Entry now supports Conditional Logic - as expected, fields will show and hide based on the form configuration
	- Edit Entry supports [Gravity Forms CSS Ready Classes](https://www.gravityhelp.com/css-ready-classes-for-gravity-forms/) - the layout you have configured for your form will be used for Edit Entry, too.
	- If you customized the CSS of your Edit Entry layout, **you will need to update your stylesheet**. Sorry for the inconvenience!
	- If visiting an invalid Edit Entry link, you are now provided with a back link
	- Product fields are now hidden by default, since they aren't editable. If you want to instead display the old message that "product fields aren't editable," you can show them using the new `gravityview/edit_entry/hide-product-fields` filter
* Added: Define column widths for fields in each field's settings (for Table and DataTable View Types only)
* Added: `{created_by}` Merge Tag that displays information from the creator of the entry ([learn more](http://docs.gravityview.co/article/281-the-createdby-merge-tag))
* Added: Edit Entry field setting to open link in new tab/window
* Added: CSS classes to the Update/Cancel/Delete buttons ([learn more](http://docs.gravityview.co/article/63-css-guide#edit-entry))
* Fixed: Shortcodes not processing properly in DataTables Extension
* Tweak: Changed support widget to a Live Chat customer support and feedback form widget

= 1.8.3 on June 12 =
* Fixed: Missing title and subtitle field zones on `list-single.php` template

= 1.8.2 on June 10 =
* Fixed: Error on `list-single.php` template

= 1.8.1 on June 9 =
* Added: New search filter for Date fields to allow searching over date ranges ("from X to Y")
* Updated: The minimum required version of Gravity Forms is now 1.8.7. **GravityView will be requiring Gravity Forms 1.9 soon.** Please update Gravity Forms if you are running an older version!
* Fixed: Conflicts with [A-Z Filter Extension](https://gravityview.co/extensions/a-z-filter/) and View sorting due to wrong field mapping
* Fixed: The "links" field type on the GravityView WordPress search widget was opening the wrong page
* Fixed: IE8 Javascript error when script debugging is on. Props, [@Idealien](https://github.com/Idealien). [Issue #361 on Github](https://github.com/katzwebservices/GravityView/issues/361)
* Fixed: PHP warning when trashing entries. [Issue #370 on Github](https://github.com/katzwebservices/GravityView/issues/370)
* Tweak: Updated the `list-single.php`, `table-body.php`, `table-single.php` templates to use `GravityView_View->getFields()` method

= 1.8 on May 26 =
* View settings have been consolidated to a single location. [Learn more about the new View Settings layout](http://docs.gravityview.co/article/275-view-settings).
* Added: Custom Link Text in Website fields
* Added: Poll Addon GravityView widget
* Added: Quiz Addon support: add Quiz score fields to your View configuration
* Added: Possibility to search by entry creator on Search Bar and Widget
* Fixed: `[gvlogic]` shortcode now properly handles comparing empty values.
    * Use `[gvlogic if="{example} is=""]` to determine if a value is blank.
    * Use `[gvlogic if="{example} isnot=""]` to determine if a value is not blank.
    * See "Matching blank values" in the [shortcode documentation](http://docs.gravityview.co/article/252-gvlogic-shortcode)
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
* Added: `[gvlogic]` Shortcode - allows you to show or hide content based on the value of merge tags in Custom Content fields! [Learn how to use the shortcode](http://docs.gravityview.co/article/252-gvlogic-shortcode).
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
* Added: Ability to hide the Approve/Reject column when viewing Gravity Forms entries ([Learn how](http://docs.gravityview.co/article/248-how-to-hide-the-approve-reject-entry-column))
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
    - Supports Post Content, Post Title, Post Excerpt, Post Tags, Post Category, and most Post Custom Field configurations ([Learn more](http://docs.gravityview.co/article/245-editable-post-fields))
* Added: Sort Table columns ([read how](http://docs.gravityview.co/article/230-how-to-enable-the-table-column-sorting-feature))
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
* Added: Two new hooks in the Custom Content field to enable conditional logic or enable `the_content` WordPress filter which will trigger the Video embed ([read how](http://docs.gravityview.co/article/227-how-can-i-transform-a-video-link-into-a-player-using-the-custom-content-field))
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
* Our support site has moved to [docs.gravityview.co](http://docs.gravityview.co). We hope you enjoy the improved experience!
* Added: GravityView Search Widget - Configure a WordPress widget that searches any of your Views. [Read how to set it up](http://docs.gravityview.co/article/222-the-search-widget)
* Added: Duplicate View functionality allows you to clone a View from the All Views screen. [Learn more](http://docs.gravityview.co/article/105-how-to-duplicate-or-copy-a-view)
* Added: Recent Entries WordPress Widget - show the latest entries for your View. [Learn more](http://docs.gravityview.co/article/223-the-recent-entries-widget)
* Added: Embed Single Entries - You can now embed entries in a post or page! [See how](http://docs.gravityview.co/article/105-how-to-duplicate-or-copy-a-view)
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
* Added: Possibility to add custom CSS classes to multiple view widget wrapper ([Read how](https://gravityview.co/support/documentation/204144575/))
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
* Added: Possibility to show the label of Dropdown field types instead of the value ([learn more](https://gravityview.co/support/documentation/202889199/ "How to display the text label (not the value) of a dropdown field?"))
* Fixed: Sorting numeric columns (field type number)
* Fixed: View entries filter for Featured Entries extension
* Fixed: Field options showing delete entry label
* Fixed: PHP date formatting now keeps backslashes from being stripped
* Modified: Allow license to be defined in `wp-config.php` ([Read how here](https://gravityview.co/support/documentation/202870789/))
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
* Fixed: Search Bar - supports "Match Any" search mode by default ([learn more](https://gravityview.co/support/documentation/202722979/ "How do I modify the Search mode?"))
* Fixed: Single Entry View title when view is embedded
* Fixed: Refresh the results cache when an entry is deleted or is approved/disapproved
* Fixed: When users are created using the User Registration Addon, the resulting entry is now automatically assigned to them
* Fixed: Change cache time to one day (from one week) so that Edit Link field nonces aren't invalidated
* Fixed: Incorrect link shortening for domains when it is second-level (for example, `example.co.uk` or `example.gov.za`)
* Fixed: Cached directory link didn't respect page numbers
* Fixed: Edit Entry Admin Bar link wouldn't work when using Custom Entry Slug
* Added: Textarea field now supports an option to trim the number of words shown
* Added: Filter to alter the default behaviour of wrapping images (or image names) with a link to the content object ([learn more](https://gravityview.co/support/documentation/202705059/ "Read the support doc for the filter"))
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
* Added: Custom entry slug capability. Instead of `/entry/123`, you can now use entry values in the URL, like `/entry/{company name}/` or `/entry/{first name}-{last name}/`. Requires some customization; [learn more here](https://gravityview.co/support/documentation/202239919)
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
* Speed improvements - [Learn more about GravityView caching](https://gravityview.co/support/documentation/202827685/)
	- Added caching functionality that saves results to be displayed
	- Automatically clean up expired caches
	- Reduce number of lookups for where template files are located
	- Store the path to the permalink for future reference when rendering a View
	- Improve speed of Gravity Forms fetching field values
* Modified: Allow `{all_fields}` and `{pricing_fields}` Merge Tags in Custom Content field. [See examples of how to use these fields](https://gravityview.co/support/documentation/201874189/).
* Fixed: Message restored when creating a new View
* Fixed: Searching advanced input fields
* Fixed: Merge Tags available immediately when adding a new field
* Fixed: Issue where jQuery Cookie script wouldn't load due to `mod_security` issues. [Learn more here](http://docs.woothemes.com/document/jquery-cookie-fails-to-load/)
* Fixed (hopefully): Auto-updates for WordPress Multisite
* Fixed: Clicking overlay to close field/widget settings no longer scrolls to top of page
* Fixed: Make sure Gravity Forms scripts are added when embedding Gravity Forms shortcodes in a Custom Field
* Fixed: Remove double images of Floaty in the warning message when Gravity View is disabled
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
	- New `gravityview_get_map_link()` function with `gravityview_map_link` filter. To learn how to modify the map link, [refer to this how-to article](https://gravityview.co/support/documentation/201608159)
	- The "Map It" string is now translatable
* Added: When editing a View, there are now links in the Data Source box to easily access the Form: edit form, form entries, form settings and form preview
* Added: Additional information in the "Add Field" or "Add Widget" picker (also get details about an item by hovering over the name in the View Configuration)
* Added: Change Entry Creator functionality. Easily change the creator of an entry when editing the entry in the Gravity Forms Edit Entry page
	- If you're using the plugin downloaded from [the how-to page](https://gravityview.co/support/documentation/201991205/), you can de-activate it
* Modified: Changed translation textdomain to `gravityview` instead of `gravity-view`
* Modified: Always show label by default, regardless of whether in List or Table View type
* Modified: It's now possible to override templates on a Form ID, Post ID, and View ID basis. This allows custom layouts for a specific View, rather than site-wide. See "Template File Hierarchy" in [the override documentation](http://gravityview.co/support/documentation/202551113/) to learn more.
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
* Added: [Using the Shortcode](http://docs.gravityview.co/article/73-using-the-shortcode) help article
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
