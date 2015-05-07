=== GravityView ===
Tags: gravity forms, directory, gravity forms directory
Requires at least: 3.3
Tested up to: 4.2
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
* Modified: Now you can override which post a single entry links to. For example, if a shortcode is embedded on a home page and you want single entries to link to a page with an embedded View, not the View itself, you can pass the `post_id` parameter. This accepts the ID of the page where the View is embedded.
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
