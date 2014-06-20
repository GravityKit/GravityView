=== GravityView ===
Tags: gravity forms, directory, gravity forms directory
Requires at least: 3.3
Tested up to: 3.9.1
Stable tag: trunk
Contributors: katzwebservices, luistinygod
License: GPL 3 or higher

Beautifully display your Gravity Forms entries.

== Description ==

== Screenshots ==


== Installation ==

1. Upload plugin files to your plugins folder, or install using WordPress' built-in Add New Plugin installer
2. Activate the plugin
3. Follow the instructions

== Frequently Asked Questions ==

== Changelog ==

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
