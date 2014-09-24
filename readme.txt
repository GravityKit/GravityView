=== GravityView ===
Tags: gravity forms, directory, gravity forms directory
Requires at least: 3.3
Tested up to: 4.0
Stable tag: trunk
Contributors: katzwebservices, luistinygod
License: GPL 3 or higher

Beautifully display your Gravity Forms entries.

== Description ==

Beautifully display your Gravity Forms entries. Learn more on [GravityView.co](https://gravityview.co).

== Installation ==

1. Upload plugin files to your plugins folder, or install using WordPress' built-in Add New Plugin installer
2. Activate the plugin
3. Follow the instructions

== Changelog ==

= 1.1.7 =
* Added: Support for Gravity Forms Section & HTML field types
* Added: Improved textarea field support. Instead of using line breaks, textareas now output with paragraphs.
	- Added new `/templates/fields/textarea.php` file
* Added: A new File Upload field setting. Force uploads to be displayed as links and not visually embedded by checking the "Display as a Link" checkbox.
* Added: Option to disable "Map It" link for the full Address field.
	- New `gravityview_get_map_link()` function with `gravityview_map_link` filter. To learn how to modify the map link, [refer to this how-to article](https://gravityview.co/support/documentation/201608159)
	- The "Map It" string is now translatable
* Modified: File Upload field output no longer run through `wpautop()` function
* Modified: Audio and Video file uploads are now displayed using WordPress' built-in [audio](http://codex.wordpress.org/Audio_Shortcode) and [video](http://codex.wordpress.org/Video_Shortcode) shortcodes (requires WordPress 3.6 or higher)
	- Additional file type support
	- Added `gravityview_video_settings` and `gravityview_audio_settings` filters to modify the parameters passed to the shortcode
* Fixed: Empty `<span class="gv-field-label">` tags no longer output
	- Modified: `gv_field_label()` no longer returns the label with a trailing space. Instead, we use the `.gv-field-label` CSS class to add spacing using CSS padding.
* Modified: Added support for Gravity Forms "Post Image" field captions, titles, and descriptions.
* Updated list of allowed image formats to include `.bmp`, `.jpe`, `.tiff`, `.ico`
* Modified: `/templates/fields/fileupload.php` file - removed the logic for how to output the different file types and moved it to the `gravityview_get_files_array()` function in `includes/class-api.php`

= 1.1.6 on September 8 =
* Fixed: Approve / Disapprove all entries using Gravity Forms bulk edit entries form (previously, only visible entries were affected)
* Added: Email field settings
	- Email addresses are now encrypted by default to prevent scraping by spammers
	- Added option to display email plaintext or as a link
	- Added subject and body settings: when the link is clicked, you can choose to have these values pre-filled
* Added: Source URL field settings, including show as a link and custom link text
* Fixed: Empty truncated URLs no longer get shown
* Fixed: License Activation works when No-Conflict Mode is enabled
* Fixed: When creating a new View, "View Type" box was visible when there were no existing Gravity Forms
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
* Added: Shortcodes for outputting Widgets such as pagination and search. Note: they only work on embedded views if the shortcode has already been processed. This is going to be improved. [Read the documentation](https://katzwebservices.zendesk.com/hc/en-us/articles/201103045)
* Added: Search form fields now displayed horizontally by default. [That can be changed](https://katzwebservices.zendesk.com/hc/en-us/articles/201119765).
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
* Added: [Using the Shortcode](https://katzwebservices.zendesk.com/hc/en-us/articles/202934188) help article
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
