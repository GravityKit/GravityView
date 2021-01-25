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
                    <img src="<?php echo plugins_url( 'assets/images/screenshots/add-view-button.png', GRAVITYVIEW_FILE ); ?>" alt="Screenshot of Add View button" />
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

				<h3>2.9.4 on January 25, 2020</h3>

				<ul>
					<li>Added: Apply <code>{get}</code> merge tag replacements in <code>[gvlogic]</code> attributes and content</li>
					<li>Modified: Made View Settings changes preparing for a big <a href="https://gravityview.co/extensions/math/">Math by GravityView</a> update!</li>
					<li>Fixed: "Change Entry Creator" would not work with Gravity Forms no-conflict mode enabled</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added: <code>gravityview/metaboxes/multiple_entries/after</code> action to <code>includes/admin/metabox/views/multiple-entries.php</code> to allow extending Multiple Entries View settings</li>
				</ul>

				<h3>2.9.3 on December 15, 2020</h3>

				<ul>
					<li>Improved: Add search field to the Entry Creator drop-down menu</li>
					<li>Tweak: Hide field icons (for now) when editing a View...until our refreshed design is released 😉</li>
					<li>Fixed: Some JavaScript warnings on WordPress 5.6</li>
					<li>Fixed: Duplicate Entry field doesn't appear for users with custom roles</li>
					<li>Fixed: Search entries by Payment Date would not yield results</li>
					<li>Fixed: Uncaught error when one of GravityView's methods is used before WordPress finishes loading</li>
					<li>Fixed: Duplicate Entry link would only be displayed to users with an administrator role</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added: <code>gravityview/search-trim-input</code> filter to strip or preserve leading/trailing whitespaces in Search Bar values</li>
					<li>Tweak: Improved logging output</li>
					<li>Modified: <code>gravityview_date_created_adjust_timezone</code> default is now set to false (use UTC value)</li>
				</ul>

				<h3>2.9.2.1 on October 26, 2020</h3>

				<ul>
					<li>Improved: Plugin license information layout when running Gravity Forms 2.5</li>
					<li>Fixed: View Settings overflow their container (introduced in 2.9.2)</li>
				</ul>

				<h3>2.9.2 on October 21, 2020</h3>

				<ul>
					<li>Added: GravityView is now 100% compatible with upcoming <a href="https://www.gravityforms.com/gravity-forms-2-5-beta-2/">Gravity Forms 2.5</a>!</li>
					<li>Added: New View setting to redirect users to a custom URL after deleting an entry</li>
					<li>Added: An option to display "Powered by GravityView" link under your Views. If you're a <a href="https://gravityview.co/account/affiliate/">GravityView affiliate</a>, you can earn 20% of sales generated from your link!</li>
					<li>Improved: Duplicate Entry field is only visible for logged-in users with edit or duplicate entry permissions</li>
					<li>Modified: Remove HTML from Website and Email fields in CSV output</li>
					<li>Fixed: Possible fatal error when Gravity Forms is inactive</li>
					<li>Fixed: Export of View entries as a CSV would result in a 404 error on some hosts</li>
					<li>Fixed: Entries filtered by creation date using relative dates (e.g., "today", "-1 day") did not respect WordPress's timezone offset</li>
					<li>Fixed: Partial entries edited in GravityView were being duplicated</li>
					<li>Fixed: Trying to activate a license disabled due to a refund showed an empty error message</li>
					<li>Tweak: Improvements to tooltip behavior in View editor</li>
					<li>Tweak: When "Make Phone Number Clickable" is checked, disable the "Link to single entry" setting in Phone field settings</li>
					<li>Tweak: Don't show "Open links in new window" for Custom Content field</li>
					<li>Tweak: Removed "Open link in the same window?" setting from Website field
						<ul>
							<li>Note: For existing Views, if both "Open link in the same window?" and "Open link in a new tab or window?" settings were checked, the link will now <em>not open in a new tab</em>. We hope no one had them both checked; this would have caused a rift in space-time and a room full of dark-matter rainbows.</li>
						</ul></li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added brand-new unit testing and acceptance testing...stay tuned for a write-up on how to easily run the GravityView test suite</li>
					<li>Changed: <code>/templates/fields/field-website-html.php</code> and <code>/templates/deprecated/fields/website.php</code> to use new <code>target=_blank</code> logic</li>
					<li>Fixed: License key activation when <code>GRAVITYVIEW_LICENSE_KEY</code> was defined</li>
					<li>Deprecated: Never used method <code>GravityView_Delete_Entry::set_entry()</code></li>
				</ul>

				<h3>2.9.1 on September 1, 2020</h3>

				<ul>
					<li>Improved: Changed the Support Port icon &amp; text to make it clearer</li>
					<li>Updated: Updater script now handles WordPress 5.5 auto-updates</li>
					<li>Fixed: Add Yoast SEO 14.7 scripts to the No-Conflict approved list</li>
					<li>Fixed: Available Gravity Forms forms weren't appearing in the Gravity Forms widget when configuring a View</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Improved: Gravity Forms 2.5 beta support</li>
					<li>Fixed: Issue when server doesn't support <code>GLOB_BRACE</code></li>
					<li>Fixed: Removed references to non-existent source map files</li>
				</ul>

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
