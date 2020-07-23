<?php
/**
 * Welcome Page Class
 *
 * @package   GravityView
 * @author    Zack Katz <zack@gravityview.co>
 * @link      https://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * GravityView_Welcome Class
 *
 * A general class for About page.
 *
 * @since 1.0
 */
class GravityView_Welcome {

	/**
	 * @var string The capability users should have to view the page
	 */
	public $minimum_capability = 'gravityview_getting_started';

	/**
	 * Get things started
	 *
	 * @since 1.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus'), 200 );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome'    ) );
		add_filter( 'gravityview_is_admin_page', array( $this, 'is_dashboard_page'), 10, 2 );
	}

	/**
	 * Register the Dashboard Pages which are later hidden but these pages
	 * are used to render the Welcome pages.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function admin_menus() {

		// Add help page to GravityView menu
		add_submenu_page(
			'edit.php?post_type=gravityview',
			__('GravityView: Getting Started', 'gravityview'),
			__('Getting Started', 'gravityview'),
			$this->minimum_capability,
			'gv-getting-started',
			array( $this, 'getting_started_screen' )
		);

		// Changelog Page
		add_submenu_page(
			'edit.php?post_type=gravityview',
			__( 'Changelog', 'gravityview' ),
			__( 'Changelog', 'gravityview' ),
			$this->minimum_capability,
			'gv-changelog',
			array( $this, 'changelog_screen' )
		);

		// Credits Page
		add_submenu_page(
			'edit.php?post_type=gravityview',
			__( 'Credits', 'gravityview' ),
			__( 'Credits', 'gravityview' ),
			$this->minimum_capability,
			'gv-credits',
			array( $this, 'credits_screen' )
		);

	}

	/**
	 * Is this page a GV dashboard page?
	 *
	 * @return boolean  $is_page   True: yep; false: nope
	 */
	public function is_dashboard_page($is_page = false, $hook = NULL) {
		global $plugin_page;

		if($is_page) { return $is_page; }

		return in_array( $plugin_page, array( 'gv-about', 'gv-credits', 'gv-getting-started' ) );
	}

	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function admin_head() {
		global $plugin_page;

		remove_submenu_page( 'edit.php?post_type=gravityview', 'gv-credits' );
		remove_submenu_page( 'edit.php?post_type=gravityview', 'gv-changelog' );

		if( !$this->is_dashboard_page() ) { return; }

		?>
        <style type="text/css" media="screen" xmlns="http://www.w3.org/1999/html">
		/*<![CDATA[*/
		.update-nag { display: none; }
		/*]]>*/
		</style>
		<?php
	}

	/**
	 * Navigation tabs
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function tabs() {
		global $plugin_page;

		// Don't fetch -beta, etc.
		list( $display_version ) = explode( '-', GravityView_Plugin::version );

		$selected = !empty( $plugin_page ) ? $plugin_page : 'gv-getting-started';

		echo gravityview_get_floaty( 132 );
		?>

		<h1><?php printf( esc_html__( 'Welcome to GravityView %s', 'gravityview' ), $display_version ); ?></h1>
		<div class="about-text"><?php esc_html_e( 'Thank you for installing GravityView. Beautifully display your Gravity Forms entries.', 'gravityview' ); ?></div>

		<h2 class="nav-tab-wrapper clear">
			<a class="nav-tab <?php echo $selected == 'gv-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-getting-started', 'post_type' => 'gravityview'), 'edit.php' ) ) ); ?>">
				<?php _e( "Getting Started", 'gravityview' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'gv-changelog' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-changelog', 'post_type' => 'gravityview'), 'edit.php' ) ) ); ?>">
				<?php _e( "List of Changes", 'gravityview' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'gv-credits' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-credits', 'post_type' => 'gravityview'), 'edit.php' ) ) ); ?>">
				<?php _e( 'Credits', 'gravityview' ); ?>
			</a>
		</h2>
		<?php
	}

	/**
	 * Render About Screen
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function getting_started_screen() {
	?>
		<div class="wrap about-wrap">
			<?php $this->tabs(); ?>
		</div>

		<div class="about-wrap">

            <h2 class="about-headline-callout">Configuring a View</h2>

            <div class="feature-video"  style="text-align:center;">
                <iframe height="315" src="https://www.youtube-nocookie.com/embed/WrXsZhqKRY8?rel=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>

                <p style="text-align:center; padding-top: 1em;"><a class="button button-primary button-hero" href="https://docs.gravityview.co/category/24-category">Read more: Setting Up Your First View</a></p>
            </div>

			<div class="feature-section two-col has-2-columns is-fullwidth">
				<div class="col column">
					<h3>Create a View</h3>

                    <ol class="ol-decimal">
						<li>Go to <a href="<?php echo admin_url('post-new.php?post_type=gravityview'); ?>">Views &gt; New View</a></li>
						<li>If you want to <strong>create a new form</strong>, click the "Use a Form Preset" button</li>
						<li>If you want to <strong>use an existing form&rsquo;s entries</strong>, select from the dropdown.</li>
						<li>Select the type of View you would like to create. There are two core types of Views: <strong>Table</strong> and <strong>Listing</strong>.
							<ul class="ul-square">
								<li><strong>Table Views</strong> output entries as tables; a grid of data.</li>
								<li><strong>Listing Views</strong> display entries in a more visual layout.</li>
							</ul>
						</li>
                        <li>On the View Configuration metabox, click on the "+Add Field" button to add form fields to the active areas of your View. These are the fields that will be displayed in the frontend.</li>
					</ol>
				</div>
                <div class="col column">
                    <h4>What is a View?</h4>
                    <p>When a form is submitted in Gravity Forms, an entry is created. Without GravityView, Gravity Forms entries are visible only in the WordPress dashboard, and only to users with permission.</p>
                    <p>GravityView allows you to display entries on the front of your site. In GravityView, when you arrange the fields you want displayed and save the configuration, it's called a "View".</p>
                </div>
			</div>

            <hr />

            <div class="feature-section two-col has-2-columns is-fullwidth">
                <div class="col column">
                    <h3>Embed Views in Posts &amp; Pages</h3>
                    <p>Views don&rsquo;t need to be embedded in a post or page, but you can if you want. Embed Views using the "Add View" button above your content editor.</p>
                </div>
                <div class="col column">
                    <img src="<?php echo plugins_url( 'assets/images/screenshots/add-view-button.png', GRAVITYVIEW_FILE ); ?>" />
                </div>
            </div>

            <hr />

			<div class="feature-section two-col has-2-columns is-fullwidth">
                <div class="col column">
                    <h3>Configure Multiple Entry, Single Entry, and Edit Entry Layouts</h3>

                    <p>You can configure what fields are displayed in <strong>Multiple Entry</strong>, <strong>Single Entry</strong>, and <strong>Edit Entry</strong> modes. These can be configured by clicking on the tabs in "View Configuration."</p>

                    <ul class="ul-disc">
                        <li>Click "+ Add Field" to add a field to a zone</li>
                        <li>Click the name of the field you want to display</li>
                        <li>Once added, fields can be dragged and dropped to be re-arranged. Hover over the field until you see a cursor with four arrows, then drag the field.</li>
                        <li>Click the <a href="#" style="text-decoration:none;"><i class="dashicons dashicons-admin-generic"></i></a> gear icon on each field to configure the <strong>Field Settings</strong></li>
                    </ul>
                </div>
                <div class="col column">
                    <img src="<?php echo plugins_url( 'assets/images/screenshots/add-field.png', GRAVITYVIEW_FILE ); ?>" alt="Add a field dialog box" />
                </div>
			</div>
		</div>
		<?php
	}


	/**
	 * Render Changelog Screen
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public function changelog_screen() {

	?>
		<div class="wrap about-wrap">

			<?php $this->tabs(); ?>

            <div class="feature-section col two-col has-2-columns is-fullwidth" style="padding: 0;">
				<div class="column col col-2">
					<div class="media-container"><img alt="Gravity Forms" src="<?php echo plugins_url( 'assets/images/screenshots/gravityforms.png', GRAVITYVIEW_FILE ); ?>" style="border: none"></div>
					<h4 class="higher">New: Gravity Forms Widget</h4>
					<p>If you want to allow easily adding new entries to your View, it&rsquo;s now simple to embed a Gravity Forms form as a Widget: click "Add Widget" and select Gravity Forms. Configure the widget, and you&rsquo;re all set.</p>
				</div>
	            <div class="column col col-2">
		            <div class="media-container"><img alt="Gravatar" src="<?php echo plugins_url( 'assets/images/screenshots/gravatar.jpg', GRAVITYVIEW_FILE ); ?>" style="border: none"></div>
		            <h4 class="higher">Gravatar field</h4>
		            <p>Gravatars are images that represent you online. They're associated with email addresses and can be managed at <a href="https://gravatar.com">Gravatar.com</a>. Now, GravityView has a Gravatar
		            field where you can choose to show the Gravatar of the entry creator or the image associated with a submitted email.</p>
	            </div>
            </div>

			<div class="changelog point-releases" style="border-bottom: 0">

                <div class="headline-feature" style="max-width: 100%">
                    <h2 style="border-bottom: 1px solid #ccc; padding-bottom: 1em; margin-bottom: 0; margin-top: 0"><?php esc_html_e( 'What&rsquo;s New', 'gravityview' ); ?></h2>
                </div>

				<h3>2.9.0.1 on July 23, 2020</h3>

				<ul>
					<li>Fixed: Loading all Gravity Forms forms on the frontend
						<ul>
							<li>Fixes Map Icons field not working</li>
							<li>Fixes conflict with gAppointments and Gravity Perks</li>
						</ul></li>
					<li>Fixed: Fatal error when Gravity Forms is inactive</li>
				</ul>

				<h3>2.9 on July 16, 2020</h3>

				<ul>
					<li>Added: A "Gravity Forms" widget to easily embed a form above and below a View</li>
					<li>Added: Settings for changing the "No Results" text and "No Search Results" text</li>
					<li>Added: "Date Updated" field to field picker and sorting options</li>
					<li>Modified: When clicking the "GravityView" link in the Admin Toolbar, go to GravityView settings</li>
					<li>Improved: Add new Yoast SEO plugin scripts to the No-Conflict approved list</li>
					<li>Improved: Add Wicked Folders plugin scripts to the No-Conflict approved list</li>
					<li>Fixed: Don't allow sorting by the Duplicate field</li>
					<li>Fixed: Multi-site licenses not being properly shared with single sites when GravityView is not Network Activated</li>
					<li>Fixed: Potential fatal error for Enfold theme</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Fixed: Settings not able to be saved when using the <code>GRAVITYVIEW_LICENSE_KEY</code> constant</li>
					<li>Fixed: License not able to be activated when using the <code>GRAVITYVIEW_LICENSE_KEY</code> constant</li>
					<li>Fixed: Potential PHP warning when using the <code>{created_by}</code> Merge Tag</li>
					<li>Modified: Added index of the current file in the loop to the <code>gravityview/fields/fileupload/file_path</code> filter</li>
				</ul>

				<h3>2.8.1 on April 22, 2020</h3>

				<ul>
					<li>Added: Better inline documentation for View Settings</li>
					<li>Improved: When clicking "Add All Form Fields" in the "+ Add Field" picker</li>
					<li>Modified: Changed default settings for new Views to "Show only approved entries"</li>
					<li>Modified: When adding a field to a table-based layout, "+ Add Field" now says "+ Add Column"</li>
					<li>Fixed: Single Entry "Hide empty fields" not working in Table and DataTables layouts</li>
				</ul>

				<h3>2.8 on April 16, 2020 </h3>

				<ul>
					<li>Added: User Fields now has many more options, including avatars, first and last name combinations, and more</li>
					<li>Added: A new <a href="https://en.gravatar.com">Gravatar (Globally Recognized Avatar)</a> field</li>
					<li>Added: "Display as HTML" option for Paragraph fields - By default, safe HTML will be shown. If disabled, only text will be shown.</li>
					<li>Added: Support for Gravity Forms Partial Entries Add-On. When editing an entry, the entry's "Progress" will now be updated.</li>
					<li>Modified: Sort forms by title in Edit View, rather than Date Created (thanks, Rochelle!)</li>
					<li>Modified: The <a href="https://docs.gravityview.co/article/281-the-createdby-merge-tag"><code>{created_by}</code> Merge Tag</a>
						<ul>
							<li>When an entry was created by a logged-out user, <code>{created_by}</code> will now show details for a logged-out user (ID <code>0</code>), instead of returning an unmodified Merge Tag</li>
							<li>When <code>{created_by}</code> is passed without any modifiers, it now will return the ID of the user who created the entry</li>
							<li>Fixed PHP warning when <code>{created_by}</code> Merge Tag was passed without any modifiers</li>
						</ul></li>
					<li>Fixed: The "Single Entry Title" setting was not working properly</li>
					<li>Fixed: Recent Entries widget filters not being applied</li>
					<li>Updated translations: Added Formal German translation (thanks, Felix K!) and updated Polish translation (thanks, Dariusz!)</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added: <code>gravityview/fields/textarea/allow_html</code> filter to toggle whether Paragraph field output should allow HTML or should be sanitized with <code>esc_html()</code></li>
					<li>Added: <code>gravityview/field/created_by/name_display</code> filter for custom User Field output.</li>
					<li>Added: <code>gravityview/field/created_by/name_display/raw</code> allow raw (unescaped) output for <code>gravityview/field/created_by/name_display</code>.</li>
					<li>Added: <code>gravityview/fields/gravatar/settings</code> filter to modify the new Gravatar field's settings</li>
					<li>Added: <code>gravityview/search/sieve_choices</code> filter in Version 2.5 that enables only showing choices in the Search Bar that exist in entries (<a href="https://docs.gravityview.co/article/701-show-choices-that-exist">learn more about this filter</a>)</li>
					<li>Modified: <code>gravityview_get_forms()</code> and <code>GVCommon::get_forms()</code> have new <code>$order_by</code> and <code>$order</code> parameters (Thanks, Rochelle!)</li>
					<li>Fixed: <code>gravityview/edit_entry/user_can_edit_entry</code> and <code>gravityview/capabilities/allow_logged_out</code> were not reachable in Edit Entry and Delete Entry since Version 2.5</li>
				</ul>

				<h3>2.7.1 on February 24, 2020</h3>

				<ul>
					<li>Fixed: Fatal error when viewing entries using WPML or Social Sharing & SEO extensions</li>
				</ul>

				<h3>2.7 on February 20, 2020 =</h3>

				<ul>
					<li>Added: "Enable Edit Locking" View setting to toggle on and off entry locking (in the "Edit Entry" tab of the View Settings)</li>
					<li>Fixed: Broken Toolbar link to Gravity Forms' entry editing while editing an entry in GravityView</li>
					<li>Fixed: PHP undefined index when editing an entry with empty File Upload field</li>
					<li>Fixed: When adding a field in the View Configuration, the browser window would resize</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Modified: The way Hidden Fields are rendered in Edit Entry no fields are configured. <a href="https://docs.gravityview.co/article/678-edit-entry-hidden-fields-field-visibility#timeline">Read what has changed around Hidden Fields</a>
						<ul>
							<li>Fixed: Rendering Hidden Fields as <code>input=hidden</code> when no fields are configured in Edit Entry (fixing a regression in 2.5)</li>
							<li>Modified: The default value for the <code>gravityview/edit_entry/reveal_hidden_field</code> filter is now <code>false</code></li>
							<li>Added: <code>gravityview/edit_entry/render_hidden_field</code> filter to modify whether to render Hidden Field HTML in Edit Entry (default: <code>true</code>)</li>
						</ul></li>
					<li>Modified: Changed <code>GravityView_Edit_Entry_Locking::enqueue_scripts()</code> visibility to protected</li>
				</ul>

				<h3>2.6 on February 12, 2020</h3>

				<ul>
					<li>Added: Implement Gravity Forms Entry Locking - see when others are editing an entry at the same time (<a href="https://docs.gravityview.co/article/676-entry-locking">learn more</a>)</li>
					<li>Added: Easily duplicate entries in Gravity Forms using the new "Duplicate" link in Gravity Forms Entries screen (<a href="https://docs.gravityview.co/article/675-duplicate-gravity-forms-entry">read how</a>)</li>
					<li>Improved: Speed up loading of Edit View screen</li>
					<li>Improved: Speed of adding fields in the View Configuration screen</li>
					<li>Modified: Reorganized some settings to be clearer</li>
					<li>Fixed: Potential fatal error when activating extensions with GravityView not active</li>
					<li>Updated: Russian translation (thank you, Victor S!)</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added: <code>gravityview/duplicate/backend/enable</code> filter to disable adding a "Duplicate" link for entries</li>
					<li>Added: <code>gravityview/request/is_renderable</code> filter to modify what request classes represent valid GravityView requests</li>
					<li>Added: <code>gravityview/widget/search/form/action</code> filter to change search submission URL as needed</li>
					<li>Added: <code>gravityview/entry-list/link</code> filter to modify Other Entries links as needed</li>
					<li>Added: <code>gravityview/edit/link</code> filter to modify Edit Entry link as needed</li>
					<li>Fixed: A rare issue where a single entry is prevented from displaying with Post Category filters</li>
					<li>Modified: Important! <code>gravityview_get_entry()</code> and <code>GVCommon::get_entry()</code> require a View object as the fourth parameter. While the View will be retrieved from the context if the parameter is missing, it's important to supply it.</li>
					<li>Modified: <code>GVCommon::check_entry_display</code> now requires a View object as the second parameter. Not passing it will return an error.</li>
					<li>Modified: <code>gravityview/common/get_entry/check_entry_display</code> filter has a third View parameter passed from <code>GVCommon::get_entry</code></li>
					<li>Modified: Bumped future minimum Gravity Forms version to 2.4</li>
				</ul>

				<h3>2.5.1 on December 14, 2019</h3>

				<ul>
					<li>Modified: "Show Label" is now off by default for non-table layouts</li>
					<li>Improved: The View Configuration screen has been visually simplified. Fewer borders, larger items, and rounder corners.</li>
					<li>Accessibility improvements. Thanks to <a href="https://rianrietveld.com">Rian Rietveld</a> and Gravity Forms for their support.
						<ul>
							<li>Color contrast ratios now meet <a href="https://www.w3.org/TR/WCAG20/">Web Content Accessibility Guidelines (WCAG) 2.0</a> recommendations</li>
							<li>Converted links that act as buttons to actual buttons</li>
							<li>Added keyboard navigation support for "Add Field" and "Add Widget" pickers</li>
							<li>Auto-focus the field search field when Add Field is opened</li>
							<li>Improved Search Bar HTML structure for a better screen reader experience</li>
							<li>Added ARIA labels for Search Bar configuration buttons</li>
							<li>Improved touch target size and spacing for Search Bar add/remove field buttons</li>
						</ul></li>
					<li>Fixed: "Search All" with Multiple Forms plugin now works as expected in both "any" and "all" search modes.</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added: <code>gravityview_lightbox_script</code> and <code>gravityview_lightbox_style</code> filters.</li>
					<li>Deprecated: <code>gravity_view_lightbox_script</code> and <code>gravity_view_lightbox_style</code> filters. Use <code>gravityview_lightbox_script</code> and <code>gravityview_lightbox_style</code> instead.</li>
				</ul>

				<h3>2.5 on December 5, 2019</h3>

                <p>This is a <strong>big update</strong>! Lots of improvements and fixes.</p>

                <h4>All changes:</h4>

                <ul>
                    <li><strong>GravityView now requires WordPress 4.7 or newer.</strong></li>
                    <li>Added: A new "Duplicate Entry" allows you to duplicate entries from the front-end</li>
                    <li>View Configuration
                        <ul>
                            <li>Added: You can now add labels for Custom Content in the View editor (this helps keep track of many Custom Content fields at once!)</li>
                            <li>Modified: New Views will be created with a number of default widgets preset</li>
                            <li>Fixed: View configuration could be lost when the "Update" button was clicked early in the page load or multiple times rapidly</li>
                            <li>Fixed: Some users were unable to edit a View, although having the correct permissions</li>
                        </ul></li>
                    <li>Improved CSV output
                        <ul>
                            <li>Modified: Multiple items in exported CSVs are now separated by a semicolon instead of new line. This is more consistent with formatting from other services.</li>
                            <li>Fixed: Checkbox output in CSVs will no longer contain HTML by default</li>
                            <li>Fixed: Textarea (Paragraph) output in CSVs will no longer contain <code>&lt;br /&gt;</code> tags by default</li>
                        </ul></li>
                    <li>Edit Entry
                        <ul>
                            <li>Added: Directly embed the Edit Entry screen using the shortcode <code>[gventry edit="1"]</code></li>
                            <li>Fixed: Editing an entry with Approve/Disapprove field hidden would disapprove an unapproved entry</li>
                            <li>Fixed: Field visibility when editing entries. Hidden fields remain hidden unless explicitly allowed via field configuration.</li>
                            <li>Fixed: Hidden calculation fields were being recalculated on Edit Entry</li>
                        </ul></li>
                    <li>Sorting and Search
                        <ul>
                            <li>Fixed: User sorting does not work when the <code>[gravityview]</code> shortcode defines a sorting order</li>
                            <li>Fixed: Proper sorting capabilities for Time and Date fields</li>
                            <li>Fixed: Page Size widget breaks when multiple search filters are set</li>
                            <li>Fixed: Page Size widget resets itself when a search is performed</li>
                        </ul></li>
                    <li><a href="https://gravityview.co/extensions/multiple-forms/">Multiple Forms</a> fixes
                        <ul>
                            <li>Fixed: Global search not working with joined forms</li>
                            <li>Fixed: Custom Content fields now work properly with Multiple Forms</li>
                            <li>Fixed: <a href="https://gravitypdf.com">Gravity PDF</a> support with Multiple Forms plugin and Custom Content fields</li>
                            <li>Fixed: Entry Link, Edit Link and Delete Link URLs may be incorrect with some Multiple Forms setups</li>
                        </ul></li>
                    <li>Integrations
                        <ul>
                            <li>Added: "Show as score" setting for Gravity Forms Survey fields</li>
                            <li>Added: Support for <a href="https://www.gravityforms.com/add-ons/pipe-video-recording/">Gravity Forms Pipe Add-On</a></li>
                            <li>Added: Track the number of pageviews entries get by using the new <code>[gv_pageviews]</code> shortcode integration with the lightweight <a href="https://pageviews.io/">Pageviews</a> plugin</li>
                            <li>Fixed: <a href="https://gravitywiz.com/documentation/gravity-forms-nested-forms/">GP Nested Forms</a> compatibility issues</li>
                            <li>Fixed: PHP warnings appeared when searching Views for sites running GP Populate Anything with "Default" permalinks enabled</li>
                        </ul></li>
                    <li>Improved: When a View is embedded on a post or page with an incompatible URL Slug, show a warning (<a href="https://docs.gravityview.co/article/659-reserved-urls">read more</a>)</li>
                    <li>Fixed: Number field decimal precision formatting not being respected</li>
                    <li>Fixed: Lifetime licenses showed "0" instead of "Unlimited" sites available</li>
                    <li>Updated: Polish translation (Thanks, Dariusz!)</li>
                </ul>

                <p><strong>Developer Updates:</strong></p>

                <ul>
                    <li>Added: <code>[gventry edit="1"]</code> mode where edit entry shortcodes can be used now (experimental)</li>
                    <li>Added: <code>gravityview/template/field/csv/glue</code> filter to modify the glue used to separate multiple values in the CSV export (previously "\n", now default is ';')</li>
                    <li>Added: <code>gravityview/shortcodes/gventry/edit/success</code> filter to modify [gventry] edit success message</li>
                    <li>Added: <code>gravityview/search/sieve_choices</code> filter that sieves Search Widget field filter choices to only ones that have been used in entries (a UI is coming soon)</li>
                    <li>Added: <code>gravityview/search/filter_details</code> filter for developers to modify search filter configurations</li>
                    <li>Added: <code>gravityview/admin/available_fields</code> filter for developers to add their own assignable fields to View configurations</li>
                    <li>Added: <code>gravityview/features/paged-edit</code> A super-secret early-bird filter to enable multiple page forms in Edit Entry</li>
                    <li>Added: <code>$form_id</code> parameter for the <code>gravityview_template_$field_type_options</code> filter</li>
                    <li>Added: <code>gravityview/security/require_unfiltered_html</code> filter now has 3 additional parameters: <code>user_id</code>, <code>cap</code> and <code>args</code>.</li>
                    <li>Added: <code>gravityview/gvlogic/atts</code> filter for <code>[gvlogic]</code></li>
                    <li>Added: <code>gravityview/edit_entry/page/success</code> filter to alter the message between edit entry pages.</li>
                    <li>Added: <code>gravityview/approve_entries/update_unapproved_meta</code> filter to modify entry update approval status.</li>
                    <li>Added: <code>gravityview/search/searchable_fields/whitelist</code> filter to modify allowed URL-based searches.</li>
                    <li>Fixed: Some issues with <code>unfiltered_html</code> user capabilities being not enough to edit a View</li>
                    <li>Fixed: Partial form was being passed to <code>gform_after_update_entry</code> filter after editing an entry. Full form will now be passed.</li>
                    <li>Fixed: Widget form IDs would not change when form ID is changed in the View Configuration screen</li>
                    <li>Fixed: Intermittent <code>[gvlogic2]</code> and nested <code>else</code> issues
                        <ul>
                            <li>The <code>[gvlogic]</code> shortcode has been rewritten for more stable, stateless behavior</li>
                        </ul></li>
                    <li>Fixed: <code>GravityView_Entry_Notes::get_notes()</code> can return null; cast <code>$notes</code> as an array in <code>templates/fields/field-notes-html.php</code> and <code>includes/extensions/entry-notes/fields/notes.php</code> template files</li>
                    <li>Fixed: Prevent error logs from filling with "union features not supported"</li>
                    <li>Modified: Cookies will no longer be set for Single Entry back links</li>
                    <li>Modified: Default 250px <code>image_width</code> setting for File Upload images is now easily overrideable</li>
                    <li>Removed: The <code>gravityview/gvlogic/parse_atts/after</code> action is no longer available. See <code>gravityview/gvlogic/atts</code> filter instead</li>
                    <li>Removed: The <code>GVLogic_Shortcode</code> class is now a lifeless stub. See <code>\GV\Shortcodes\gvlogic</code>.</li>
                    <li>Deprecated: <code>gravityview_get_current_view_data</code> — use the <code>\GV\View</code> API instead</li>
                </ul>

                <h3>2.4.1.1 on August 16, 2019</h3>

                <ul>
                    <li>Fixed: Inconsistent sorting behavior for Views using Table layouts</li>
                    <li>Fixed: Searching all fields not searching Multi Select fields</li>
                    <li>Fixed: Error activating GravityView when Gravity Forms is disabled</li>
                    <li>Fixed: "Getting Started" and "List of Changes" page layouts in WordPress 5.3</li>
                    <li>Fixed: Don't show error messages twice when editing a View with a missing form</li>
                    <li>Tweak: Don't show "Create a View" on trashed forms action menus</li>
                </ul>

				<p style="text-align: center;">
					<a href="https://gravityview.co/changelog/" class="aligncenter button button-primary button-hero" style="margin: 0 auto; display: inline-block; text-transform: capitalize"><?php esc_html_e( 'View change history', 'gravityview' ); ?></a>
				</p>

				<div class="clear"></div>
			</div>

		</div>
	<?php
	}

	/**
	 * Render Credits Screen
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function credits_screen() { ?>
		<div class="wrap about-wrap">

			<?php $this->tabs(); ?>
			<p class="about-description"><?php _e( 'GravityView is brought to you by:', 'gravityview' ); ?></p>

            <style>
                .feature-section h3 a {
                    text-decoration: none;
                    display: inline-block;
                    margin-left: .2em;
                    line-height: 1em;
                }
				.about-wrap .cols {
					display: flex;
					flex-wrap: wrap;
					flex-direction: row;
					justify-content: space-between;
				}
				.col {
					width: 45%;
					margin-right: 5%;
				}
            </style>
			<div class="cols">

				<div class="col">
					<h3>Zack Katz <a href="https://twitter.com/zackkatz"><span class="dashicons dashicons-twitter" title="Follow Zack on Twitter"></span></a> <a href="https://katz.co" title="View Zack&rsquo;s website"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4 style="font-weight:0; margin-top:0">Project Lead &amp; Developer</h4>
					<p><img alt="Zack Katz" style="float:left; margin: 0 15px 10px 0;" src="<?php echo plugins_url( 'assets/images/zack.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Zack has been developing integrations with Gravity Forms since 2009. He runs GravityView and lives with his wife (and cat) in <a href="https://wikipedia.org/wiki/Denver">Denver, Colorado</a>.</p>
				</div>

                <div class="col">
					<h3>Rafael Ehlers <a href="https://twitter.com/rafaehlers" title="Follow Rafael on Twitter"><span class="dashicons dashicons-twitter"></span></a> <a href="https://heropress.com/essays/journey-resilience/" title="View Rafael&rsquo;s WordPress Journey"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4 style="font-weight:0; margin-top:0">Project Manager, Support Lead &amp; Customer&nbsp;Advocate</h4>
					<p><img alt="Rafael Ehlers" style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/rafael.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Rafael helps guide GravityView development priorities and keep us on track. He&rsquo;s the face of our customer support and helps customers get the most out of the product. Rafael hails from <a href="https://wikipedia.org/wiki/Porto_Alegre">Porto Alegre, Brazil</a>.</p>
				</div>

                <div class="col">
                    <h3>Vlad K.</h3>
                    <h4 style="font-weight:0; margin-top:0">Core Developer</h4>
                    <p><img alt="Vlad K." style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/vlad.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Vlad, while being the &ldquo;new kid on the block&rdquo; at GravityView, is not new to WordPress, having previously worked on the top newsletter plugin. He&rsquo;s a full-stack developer who focuses on GravityView's user-facing code in the Dashboard and front end. Vlad comes from Russia and lives in Canada.</p>
                </div>

				<div class="col">
					<h3>Rafael Bennemann <a href="https://twitter.com/rafaelbe" title="Follow Rafael on Twitter"><span class="dashicons dashicons-twitter"></span></a></h3>
					<h4 style="font-weight:0; margin-top:0">Support Specialist</h4>
					<p><img alt="Rafael Bennemann" style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/rafaelb.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Rafael dedicated most of his adult life to helping people and companies take their ideas to the web, first as a developer and now as a Customer Advocate at GravityView. He will do his best to help you too, all the while sipping a <a href="https://en.wikipedia.org/wiki/Spritz_Veneziano">Spritz Veneziano</a> in Northern Italy, where he currently lives with his family.</p>
				</div>
			</div>

			<hr class="clear" />

			<div class="feature-section">
				<div>
					<h2><?php esc_attr_e( 'Contributors', 'gravityview' ); ?></h2>

					<ul class="wp-people-group">
						<li class="wp-person">Core &amp; Extension development by <a href="http://tinygod.pt" class="block">Luis Godinho</a>, <a href="https://codeseekah.com" class="block">Gennady Kovshenin</a>, and <a href="https://mrcasual.com" class="block">Vlad K.</a></li>
						<li class="wp-person">Bengali translation by <a href="https://www.transifex.com/accounts/profile/tareqhi/">@tareqhi</a></li>
						<li class="wp-person">German translation by <a href="https://www.transifex.com/user/profile/hubert123456/">@hubert123456</a>, <a href="https://www.transifex.com/accounts/profile/seschwarz/">@seschwarz</a>, <a href="https://www.transifex.com/accounts/profile/abdmc/">@abdmc</a>, <a href="https://www.transifex.com/accounts/profile/deckerweb/">@deckerweb</a></li>
						<li class="wp-person">Turkish translation by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a></li>
						<li class="wp-person">Dutch translation by <a href="https://www.transifex.com/accounts/profile/leooosterloo/">@leooosterloo</a>, <a href="https://www.transifex.com/accounts/profile/Weergeven/">@Weergeven</a>, and <a href="https://www.transifex.com/accounts/profile/erikvanbeek/">@erikvanbeek</a>, and <a href="https://www.transifex.com/user/profile/SilverXp/">Thom (@SilverXp)</a></li>
						<li class="wp-person">Hungarian translation by <a href="https://www.transifex.com/accounts/profile/dbalage/">@dbalage</a> and <a href="https://www.transifex.com/accounts/profile/Darqebus/">@Darqebus</a></li>
						<li class="wp-person">Italian translation by <a href="https://www.transifex.com/accounts/profile/Lurtz/">@Lurtz</a> and <a href="https://www.transifex.com/accounts/profile/ClaraDiGennaro/">@ClaraDiGennaro</a></li>
						<li class="wp-person">French translation by <a href="https://www.transifex.com/accounts/profile/franckt/">@franckt</a> and <a href="https://www.transifex.com/accounts/profile/Newbdev/">@Newbdev</a></li>
						<li class="wp-person">Portuguese translation by <a href="https://www.transifex.com/accounts/profile/luistinygod/">@luistinygod</a>, <a href="https://www.transifex.com/accounts/profile/marlosvinicius.info/">@marlosvinicius</a>, and <a href="https://www.transifex.com/user/profile/rafaehlers/">@rafaehlers</a></li>
						<li class="wp-person">Romanian translation by <a href="https://www.transifex.com/accounts/profile/ArianServ/">@ArianServ</a></li>
						<li class="wp-person">Finnish translation by <a href="https://www.transifex.com/accounts/profile/harjuja/">@harjuja</a></li>
						<li class="wp-person">Spanish translation by <a href="https://www.transifex.com/accounts/profile/jorgepelaez/">@jorgepelaez</a>, <a href="https://www.transifex.com/accounts/profile/luisdiazvenero/">@luisdiazvenero</a>, <a href="https://www.transifex.com/accounts/profile/josemv/">@josemv</a>, <a href="https://www.transifex.com/accounts/profile/janolima/">@janolima</a> and <a href="https://www.transifex.com/accounts/profile/matrixmercury/">@matrixmercury</a>, <a href="https://www.transifex.com/user/profile/jplobaton/">@jplobaton</a></li>
						<li class="wp-person">Swedish translation by <a href="https://www.transifex.com/accounts/profile/adamrehal/">@adamrehal</a></li>
						<li class="wp-person">Indonesian translation by <a href="https://www.transifex.com/accounts/profile/sariyanta/">@sariyanta</a></li>
						<li class="wp-person">Norwegian translation by <a href="https://www.transifex.com/accounts/profile/aleksanderespegard/">@aleksanderespegard</a></li>
						<li class="wp-person">Danish translation by <a href="https://www.transifex.com/accounts/profile/jaegerbo/">@jaegerbo</a></li>
						<li class="wp-person">Chinese translation by <a href="https://www.transifex.com/user/profile/michaeledi/">@michaeledi</a></li>
                        <li class="wp-person">Persian translation by <a href="https://www.transifex.com/user/profile/azadmojtaba/">@azadmojtaba</a>, <a href="https://www.transifex.com/user/profile/amirbe/">@amirbe</a>, <a href="https://www.transifex.com/user/profile/Moein.Rm/">@Moein.Rm</a></li>
						<li class="wp-person">Russian translation by <a href="https://www.transifex.com/user/profile/gkovaleff/">@gkovaleff</a>, <a href="https://www.transifex.com/user/profile/awsswa59/">@awsswa59</a></li>
                        <li class="wp-person">Polish translation by <a href="https://www.transifex.com/user/profile/dariusz.zielonka/">@dariusz.zielonka</a></li>
						<li class="wp-person">Accessibility contributions by <a href="https://github.com/RianRietveld">@RianRietveld</a></li>
						<li class="wp-person">Code contributions by <a href="https://github.com/ryanduff">@ryanduff</a>, <a href="https://github.com/dmlinn">@dmlinn</a>, <a href="https://github.com/mgratch">@mgratch</a>, <a href="https://github.com/ViewFromTheBox">@ViewFromTheBox</a>, <a href="https://github.com/stevehenty">@stevehenty</a>, <a href="https://github.com/naomicbush">@naomicbush</a>, and <a href="https://github.com/mrcasual">@mrcasual</a></li>
					</ul>

					<h4><?php esc_attr_e( 'Want to contribute?', 'gravityview' ); ?></h4>
					<p><?php echo sprintf( esc_attr__( 'If you want to contribute to the code, %syou can on Github%s. If your contributions are accepted, you will be thanked here.', 'gravityview'), '<a href="https://github.com/katzwebservices/GravityView">', '</a>' ); ?></p>
				</div>
			</div>

			<hr class="clear" />

			<div class="changelog">

				<h4>Thanks to the following open-source software:</h4>

				<ul>
					<li><a href="https://datatables.net/">DataTables</a> - amazing tool for table data display. Many thanks!</li>
					<li><a href="https://github.com/10up/flexibility">Flexibility</a> - Adds support for CSS flexbox to Internet Explorer 8 &amp; 9</li>
					<li><a href="https://github.com/GaryJones/Gamajo-Template-Loader">Gamajo Template Loader</a> - makes it easy to load template files with user overrides</li>
					<li><a href="https://github.com/carhartl/jquery-cookie">jQuery Cookie plugin</a> - Access and store cookie values with jQuery</li>
					<li><a href="https://gravityview.co/gravityforms">Gravity Forms</a> - If Gravity Forms weren't such a great plugin, GravityView wouldn't exist!</li>
					<li>GravityView uses icons made by Freepik, Adam Whitcroft, Amit Jakhu, Zurb, Scott de Jonge, Yannick, Picol, Icomoon, TutsPlus, Dave Gandy, SimpleIcon from <a href="https://www.flaticon.com" title="Flaticon">www.flaticon.com</a></li>
					<li>GravityView uses free vector art by <a href="https://www.vecteezy.com">vecteezy.com</a></li>
					<li><a href="https://github.com/jnicol/standalone-phpenkoder">PHPEnkoder</a> script encodes the email addresses.</li>
					<li>The Duplicate View functionality is based on the excellent <a href="https://lopo.it/duplicate-post-plugin/">Duplicate Post plugin</a> by Enrico Battocchi</li>
					<li>Browser testing by <a href="https://www.browserstack.com">BrowserStack</a></li>
					<li><a href="https://easydigitaldownloads.com/downloads/software-licensing/">Easy Digital Downloads</a> makes auto-upgrades possible</li>
				</ul>

			</div>

		</div>
	<?php
	}


	/**
	 * Sends user to the Welcome page on first activation of GravityView as well as each
	 * time GravityView is upgraded to a new version
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function welcome() {
		global $plugin_page;

		// Bail if we're just editing the plugin
		if( $plugin_page === 'plugin-editor.php' ) { return; }

		// Bail if no activation redirect
		if ( ! get_transient( '_gv_activation_redirect' ) ) { return; }

		// Delete the redirect transient
		delete_transient( '_gv_activation_redirect' );

		$upgrade = get_option( 'gv_version_upgraded_from' );

		// Don't do anything if they've already seen the new version info
		if( $upgrade === GravityView_Plugin::version ) {
			return;
		}

		// Add "Upgraded From" Option
		update_option( 'gv_version_upgraded_from', GravityView_Plugin::version );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) { return; }

		// First time install
		if( ! $upgrade ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=gravityview&page=gv-getting-started' ) ); exit;
		}
		// Update
		else {
			wp_safe_redirect( admin_url( 'edit.php?post_type=gravityview&page=gv-changelog' ) ); exit;
		}
	}
}
new GravityView_Welcome;
