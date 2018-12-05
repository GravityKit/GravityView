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

			<div class="feature-section two-col">
				<div class="col">
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
                <div class="col">
                    <h4>What is a View?</h4>
                    <p>When a form is submitted in Gravity Forms, an entry is created. Without GravityView, Gravity Forms entries are visible only in the WordPress dashboard, and only to users with permission.</p>
                    <p>GravityView allows you to display entries on the front of your site. In GravityView, when you arrange the fields you want displayed and save the configuration, it's called a "View".</p>
                </div>
			</div>

            <hr />

            <div class="feature-section two-col">
                <div class="col">
                    <h3>Embed Views in Posts &amp; Pages</h3>
                    <p>Views don&rsquo;t need to be embedded in a post or page, but you can if you want. Embed Views using the "Add View" button above your content editor.</p>
                </div>
                <div class="col">
                    <img src="<?php echo plugins_url( 'assets/images/screenshots/add-view-button.png', GRAVITYVIEW_FILE ); ?>" />
                </div>
            </div>

            <hr />

			<div class="feature-section two-col">
                <div class="col">
                    <h3>Configure Multiple Entry, Single Entry, and Edit Entry Layouts</h3>

                    <p>You can configure what fields are displayed in <strong>Multiple Entry</strong>, <strong>Single Entry</strong>, and <strong>Edit Entry</strong> modes. These can be configured by clicking on the tabs in "View Configuration."</p>

                    <ul class="ul-disc">
                        <li>Click "+ Add Field" to add a field to a zone</li>
                        <li>Click the name of the field you want to display</li>
                        <li>Once added, fields can be dragged and dropped to be re-arranged. Hover over the field until you see a cursor with four arrows, then drag the field.</li>
                        <li>Click the <a href="#" style="text-decoration:none;"><i class="dashicons dashicons-admin-generic"></i></a> gear icon on each field to configure the <strong>Field Settings</strong></li>
                    </ul>
                </div>
                <div class="col">
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

            <div class="feature-section col two-col" style="margin:0 0 2em 0; padding: 0;">

                <div class="col col-1">
                    <div class="media-container"><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gravityview&page=gv-admin-installer' ) ); ?>"><img alt="Admin installer of GravityView plugins &amp; extensions" src="<?php echo plugins_url( 'assets/images/screenshots/installer.png', GRAVITYVIEW_FILE ); ?>" style="border: none"></a></div>
                    <h4 class="higher">Admin Installer</h4>
                    <p>Download and manage all your GravityView plugins and extensions from one convenient place&mdash;you no longer need to download plugins from your GravityView Account page!</p>
                    <p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gravityview&page=gv-admin-installer' ) ); ?>" class="button button-primary button-large">Check out the Installer</a></p>
                </div>
                <div class="col col-2">
                    <div class="media-container"><img alt="Approval notifications" src="<?php echo plugins_url( 'assets/images/screenshots/approval-notifications.png', GRAVITYVIEW_FILE ); ?>" style="border: none"></div>
                    <h4 class="higher">Approval Notifications</h4>
                    <p>Notify users or administrators when entries have been approved or disapproved.</p>
                    <p><a href="https://docs.gravityview.co/article/488-notification-when-entry-approved" class="button button-primary button-large">Learn How to Set Up</a></p>
                </div>
            </div>

			<div class="changelog point-releases" style="border-bottom: 0">

                <div class="headline-feature" style="max-width: 100%">
                    <h2 style="border-bottom: 1px solid #ccc; padding-bottom: 1em; margin-bottom: 0; margin-top: 0"><?php esc_html_e( 'What&rsquo;s New', 'gravityview' ); ?></h2>
                </div>

                <h3>2.2.1 on December 4, 2018</h3>

                <ul>
                    <li>Confirmed compatibility with WordPress 5.0 and the new Gutenberg editor (<a href="https://docs.gravityview.co/article/526-does-gravityview-support-gutenberg">use the shortcode block to embed</a>)</li>
                    <li>Added: Support for upcoming <a href="https://gravityview.co/extensions/multiple-forms/">Multiple Forms plugin</a></li>
                    <li>Fixed: Edit Entry writes incorrectly-formatted empty values in some cases.</li>
                    <li>Fixed: "Hide View data until search is performed" not working for <a href="https://gravityview.co/extensions/maps/">Maps layout</a></li>
                    <li>Fixed: Entries are not accessible when linked to from second page of results</li>
                    <li>Fixed: Search redirects to home page when previewing an unpublished View</li>
                </ul>

                <p><strong>Developer Updates:</strong></p>

                <ul>
                    <li>Fixed: Error loading GravityView when server has not defined <code>GLOB_BRACE</code> value for the <code>glob()</code> function</li>
                    <li>Added: <code>gravityview/entry/slug</code> filter to modify entry slug. It runs after the slug has been generated by <code>GravityView_API::get_entry_slug()</code></li>
                    <li>Added: <code>\GV\Entry::is_multi()</code> method to check whether the request's entry is a <code>Multi_Entry</code> (contains data from multiple entries because of joins)</li>
                </ul>


                <h3>2.2 on November 28, 2018</h3>

                <ul>
                    <li>Yes, GravityView is fully compatible with Gravity Forms 2.4!</li>
                    <li>Added: Choose where users go after editing an entry</li>
                    <li>Added: Search entries by approval status with new "Approval Status" field in the Search Bar</li>
                    <li>Added: More search input types added for "Created By" searches</li>
                    <li>Added: When searching "Created By", set the input type to "text" to search by user email, login and name fields</li>
                    <li>Fixed: Issue installing plugins from the Extensions page on a Multisite network</li>
                    <li>Fixed: When a View is embedded on the homepage of a site, Single Entry and Edit Entry did not work (404 not found error)</li>
                    <li>Fixed: Stray "Advanced Custom Fields" editor at the bottom of Edit View pages</li>
                    <li>Fixed: Labels and quantities removed when editing an entry that had product calculations</li>
                    <li>Fixed: When multiple Views are embedded on a page, Single Entry could sometimes show "You are not allowed to view this content"</li>
                    <li>Fixed: Major search and filtering any/all mode combination issues, especially with "Show only approved entries" mode, A-Z Filters, Featured Entries, Advanced Filtering plugins</li>
                    <li>Fixed: Support all <a href="https://docs.gravityview.co/article/115-changing-the-format-of-the-search-widgets-date-picker">documented date formats</a> in Search Bar date fields</li>
                    <li>Fixed: Issues with <a href="https://gravityview.co/extensions/advanced-filter/">Advanced Filtering</a> date fields (including human strings, less than, greater than)</li>
                    <li>Fixed: Security issue when Advanced Filter was configured with an "Any form field" filter (single entries were not properly secured)</li>
                    <li>Fixed: The Quiz Letter Grade is lost if Edit Entry does not contain all Gravity Forms Quiz Add-On fields</li>
                </ul>

                <p><strong>Developer Updates:</strong></p>

                <ul>
                    <li>Updated: <code>search-field-select.php</code> template to gracefully handle array values</li>
                    <li>Added: Filters for new "Created By" search. <a href="https://docs.gravityview.co/article/523-created-by-text-search">Learn how to modify what fields are searched</a>.</li>
                </ul>


                <h3>2.1.1 on October 26, 2018</h3>

                <ul>
                    <li>Added: A "Connected Views" menu on the Gravity Forms Forms page - hover over a form to see the new Connected Views menu!</li>
                    <li>Fixed: Additional slashes being added to the custom date format for Date fields</li>
                    <li>Fixed: Quiz letter grade not updated after editing an entry that has Gravity Forms Quiz fields</li>
                    <li>Fixed: Single Entry screen is inaccessible when the category is part of a URL path (using the <code>%category%</code> tag in the site's Permalinks settings)</li>
                    <li>Fixed: Issue where GravityView CSS isn't loading in the Dashboard for some customers</li>
                    <li>Fixed: Display uploaded files using Gravity Forms' secure link URL format, if enabled</li>
                </ul>

                <p><strong>Developer Updates:</strong></p>

                <ul>
                    <li>Fixed: Fixed an issue when using <a href="https://docs.gravityview.co/article/57-customizing-urls">custom entry slugs</a> where non-unique values across forms cause the entries to not be accessible</li>
                    <li>Added: <code>gravityview/template/table/use-legacy-style</code> filter to  use the legacy Table layout stylesheet without any responsive layout styles (added in GravityView 2.1) - <a href="https://gist.github.com/zackkatz/45d869e096cd5114a87952d292116d3f">Here's code you can use</a></li>
                    <li>Added: <code>gravityview/view/can_render</code> filter to allow you to override whether a View can be rendered or not</li>
                    <li>Added: <code>gravityview/widgets/search/datepicker/format</code> filter to allow you to modify only the format used, rather than using the <code>gravityview_search_datepicker_class</code> filter</li>
                    <li>Fixed: Undefined index PHP warning in the GravityView Extensions screen</li>
                    <li>Fixed: Removed internal usage of deprecated GravityView functions</li>
                    <li>Limitation: "Enable lightbox for images" will not work on images when using Gravity Forms secure URL format. <a href="mailto:support@gravityview.co">Contact support</a> for a work-around, or use a <a href="https://docs.gravityview.co/article/277-using-the-foobox-lightbox-plugin-instead-of-the-default">different lightbox script</a>.</li>
                </ul>

                <h3>2.1.0.2 &amp; 2.1.0.3 on September 28, 2018</h3>

                <ul>
                    <li>Fixed: Slashes being added to field quotes</li>
                    <li>Fixed: Images showing as links for File Upload fields</li>
                </ul>

                <h3>2.1.0.1 on September 27, 2018</h3>

                <ul>
                    <li>Fixed: Responsive table layout labels showing sorting icon HTML</li>
                    <li>Fixed: Responsive table layout showing table footer</li>
                </ul>

                <h2>2.1 on September 27, 2018</h2>

                <ul>
                    <li>Added: You can now send email notifications when an entry is approved, disapproved, or the approval status has changed. <a href="https://docs.gravityview.co/article/488-notification-when-entry-approved">Learn how</a></li>
                    <li>Added: Automatically un-approve an entry when it has been updated by an user without the ability to moderate entries</li>
                    <li>Added: Easy way to install GravityView Extensions and our stand-alone plugins <a href="https://docs.gravityview.co/article/489-managing-extensions">Learn how</a></li>
                    <li>Added: Enable CSV output for Views <a href="https://docs.gravityview.co/article/491-csv-export">Learn how</a></li>
                    <li>Added: A "Page Size" widget allows users to change the number of entries per page</li>
                    <li>Added: Support for displaying a single input value of a Chained Select field</li>
                    <li>Added: The Table layout is now mobile-responsive!</li>
                    <li>Improved: Added a shortcut to reset entry approval on the front-end of a View: "Option + Click" on the Entry Approval field</li>
                    <li>Fixed: Custom date format not working with the <code>{date_created}</code> Merge Tag</li>
                    <li>Fixed: Embedding a View inside an embedded entry didn't work</li>
                    <li>Fixed: "Link to entry" setting not working for File Upload fields</li>
                    <li>Fixed: Approval Status field not showing anything</li>
                    <li>Updated translations - thank you, translators!
                        <ul>
                            <li>Polish translated by <a href="https://www.transifex.com/user/profile/dariusz.zielonka/">@dariusz.zielonka</a></li>
                            <li>Russian translated by <a href="https://www.transifex.com/user/profile/awsswa59/">@awsswa59</a></li>
                            <li>Turkish translated by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a></li>
                            <li>Chinese translated by <a href="https://www.transifex.com/user/profile/michaeledi/">@michaeledi</a></li>
                        </ul></li>
                </ul>

                <p><strong>Developer Notes:</strong></p>

                <ul>
                    <li>Added: Process shortcodes inside [gv<em>entry</em>link] shortcodes</li>
                    <li>Added: <code>gravityview/shortcodes/gv_entry_link/output</code> filter to modify output of the <code>[gv_entry_link]</code> shortcode</li>
                    <li>Added <code>gravityview/widget/page_size/settings</code> and <code>gravityview/widget/page_size/page_sizes</code> filters to modify new Page Size widget</li>
                    <li>Modified: Added <code>data-label</code> attributes to all Table layout cells to make responsive layout CSS-only</li>
                    <li>Modified: Added responsive CSS to the Table layout CSS ("table-view.css")</li>
                    <li>Improved: Reduced database lookups when using custom entry slugs</li>
                    <li>Introduced <code>\GV\View-&gt;can_render()</code> method to reduce code duplication</li>
                    <li>Fixed: Don't add <code>gvid</code> unless multiple Views embedded in a post</li>
                    <li>Fixed: PHP 5.3 warning in when using <code>array_combine()</code> on empty arrays</li>
                    <li>Fixed: Apply <code>addslashes</code> to View Configuration when saving, fixing <code>{date_created}</code> format</li>
                    <li>REST API: Allow setting parent post or page with the REST API request using <code>post_id={id}</code> (<a href="https://docs.gravityview.co/article/468-rest-api">learn more</a>)</li>
                    <li>REST API: Added <code>X-Item-Total</code> header and meta to REST API response</li>
                </ul>

                <h3>2.0.14.1 on July 19, 2018</h3>

                <ul>
                    <li>Fixed: Potential XSS ("Cross Site Scripting") security issue. <strong>Please update.</strong></li>
                    <li>Fixed: GravityView styles weren't being loaded for some users</li>
                </ul>

                <h3>2.0.14 on July 9, 2018</h3>

                <ul>
                    <li>Added: Allow filtering entries by Unapproved status in Gravity Forms</li>
                    <li>Added: Reset entry approval status by holding down Option/Alt when clicking entry approval icon</li>
                    <li>Fixed: Merge Tags not working in field Custom Labels</li>
                    <li>Fixed: Enable sorting by approval status all the time, not just when a form has an Approval field</li>
                    <li>Fixed: When a View is saved without a connected form, don't show "no longer exists" message</li>
                    <li>Fixed: Inline Edit plugin not updating properly when GravityView is active</li>
                </ul>

                <p><strong>Developer Notes:</strong></p>

                <ul>
                    <li>Added: <code>gravityview/approve_entries/after_submission/default_status</code> filter to modify the default status of an entry as it is created.</li>
                    <li>Modified: No longer delete <code>is_approved</code> entry meta when updating entry status - leave the value to be <code>GravityView_Entry_Approval_Status::UNAPPROVED</code> (3)</li>
                    <li>Fixed: Allow for "in" and "not_in" comparisons when using <code>GravityView_GFFormsModel::is_value_match</code></li>
                    <li>Tweak: If "Search Mode" key is set, but there is no value, use "all"</li>
                    <li>Tweak: Reduced number of database queries when rendering a View</li>
                </ul>

                <h3>2.0.13.1 on June 26, 2018</h3>

                <ul>
                    <li>Fixed: Custom Content fields not working with DIY Layout</li>
                    <li>Fixed: Error when displaying plugin updates on a single site of a Multisite installation</li>
                </ul>
                
                <h3>2.0.13 on June 25, 2018</h3>

                <ul>
                    <li>Fixed: Custom Content fields not working with DIY Layout since 2.0.11</li>
                    <li>Fixed: Fatal error when migrating settings from (very) old versions of GravityView</li>
                    <li>Added: Code for Entry Notes to work properly with future version of DataTables</li>
                </ul>

                <h3>2.0.12 on June 13, 2018</h3>

                <ul>
                    <li>Fixed: On the Plugins page, "Update now" not working for GravityView Premium Plugins, Views & Extensions</li>
                    <li>Fixed: Always show that plugin updates are available, even if a license is expired</li>
                </ul>

                <h3>2.0.11 on June 12, 2018</h3>

                <ul>
                    <li>Added: Search for fields by name when adding fields to your View configuration (it's really great!)</li>
                    <li>Fixed: GravityView license details not saving when the license was activated (only when the Update Settings button was clicked)</li>
                    <li>Fixed: Entry filtering for single entries</li>
                    <li>Fixed: Per-user language setting not being used in WordPress 4.7 or newer</li>
                </ul>

                <p><strong>Developer Notes</strong></p>

                <ul>
                    <li>Added: <code>\GV\View::get_joins()</code> method to fetch array of <code>\GV\Joins</code> connected with a View</li>
                    <li>Added: <code>\GV\View::get_joined_forms()</code> method to get array of <code>\GV\GF_Forms</code> connected with a View</li>
                </ul>


                <h3>2.0.10 on June 6, 2018</h3>

                <ul>
                    <li>Added: Search for fields by name when adding fields to your View configuration (it's really great!)</li>
                    <li>Fixed: Password-protected Views were showing "You are not allowed to view this content" instead of the password form</li>
                    <li>Fixed: When Map View is embedded, Search Bar pointed to View URL, not page URL</li>
                </ul>

				<p style="text-align: center">
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
            </style>
			<div class="feature-section col three-col">

				<div class="col">
					<h3>Zack Katz <a href="https://twitter.com/zackkatz"><span class="dashicons dashicons-twitter" title="Follow Zack on Twitter"></span></a> <a href="https://katz.co" title="View Zack&rsquo;s website"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4 style="font-weight:0; margin-top:0">Project Lead &amp; Developer</h4>
					<p><img style="float:left; margin: 0 15px 10px 0;" src="<?php echo plugins_url( 'assets/images/zack.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Zack has been developing integrations with Gravity Forms since 2009. He runs GravityView and lives with his wife (and cat) in <a href="https://wikipedia.org/wiki/Denver">Denver, Colorado</a>.</p>
				</div>

                <div class="col">
					<h3>Rafael Ehlers <a href="https://twitter.com/rafaehlers" title="Follow Rafael on Twitter"><span class="dashicons dashicons-twitter"></span></a> <a href="https://heropress.com/essays/journey-resilience/" title="View Rafael&rsquo;s WordPress Journey"><span class="dashicons dashicons-admin-site"></span></a></p></h3>
					<h4 style="font-weight:0; margin-top:0">Project Manager, Support Lead &amp; Customer&nbsp;Advocate</h4>
					<p><img style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/rafael.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Rafael helps guide GravityView development priorities and keep us on track. He&rsquo;s the face of our customer support and helps customers get the most out of the product. Rafael hails from <a href="https://wikipedia.org/wiki/Porto_Alegre">Porto Alegre, Brazil</a>.</p>
				</div>

                <div class="col last-feature">
                    <h3>Gennady Kovshenin <a href="https://twitter.com/soulseekah" title="Follow Gennady on Twitter"><span class="dashicons dashicons-twitter"></span></a> <a href="https://codeseekah.com" title="View Gennady&rsquo;s Blog"><span class="dashicons dashicons-admin-site"></span></a></h3>
                    <h4 style="font-weight:0; margin-top:0">Core Developer</h4>
                    <p><img style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/gennady.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Gennady works on the GravityView core, improving everything behind the scenes. He is an active member of the WordPress community and loves exotic tea. Gennady lives and runs long distances in <a href="https://wikipedia.org/wiki/Saint_Petersburg" rel="external">St. Petersburg, Russia</a>.</p>
                </div>

                <div class="col">
                    <h3>Vlad K.</h3>
                    <h4 style="font-weight:0; margin-top:0">Core Developer</h4>
                    <p><img style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/vlad.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Vlad, while being the &ldquo;new kid on the block&rdquo; at GravityView, is not new to WordPress, having previously worked on the top newsletter plugin. He&rsquo;s a full-stack developer who focuses on GravityView's user-facing code in the Dashboard and front end. Vlad comes from Russia and lives in Canada.</p>
                </div>
			</div>

			<hr class="clear" />

			<div class="feature-section">
				<div>
					<h2><?php esc_attr_e( 'Contributors', 'gravityview' ); ?></h2>

					<ul class="wp-people-group">
						<li class="wp-person">Core &amp; Extension development by <a href="http://tinygod.pt" class="block">Luis Godinho</a> and <a href="https://codeseekah.com" class="block">Gennady Kovshenin</a></li>
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
