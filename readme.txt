=== GravityView ===
Tags: gravity forms, directory, gravity forms directory
Requires at least: 3.3
Tested up to: 3.9.1
Stable tag: trunk
Contributors: katzwebservices, luistinygod
License: GPL 3 or higher

Beautifully display your Gravity Forms entries.

== Installation ==

1. Upload plugin files to your plugins folder, or install using WordPress' built-in Add New Plugin installer
2. Activate the plugin
3. Follow the instructions

== Changelog ==

= 1.1 =
* Refactored (re-wrote) View data handling. Now saves up to 10 queries on each page load.
* Fixed: Infinite loop for rendering `post_content` fields
* Fixed: Page length value now respected for DataTables
* Fixed: Formatting of DataTables fields is now processed the same way as other fields. Images now work, for example.
* Modified: Removed redundant `gravityview_hide_empty_fields` filters

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
