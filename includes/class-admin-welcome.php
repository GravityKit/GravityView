<?php
/**
 * Welcome Page Class
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
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
	public $minimum_capability = 'manage_options';

	/**
	 * Get things started
	 *
	 * @since 1.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus') );
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
			__('GravityView: Getting Started', 'gravity-view'),
			__('Getting Started', 'gravity-view'),
			$this->minimum_capability,
			'gv-getting-started',
			array( $this, 'getting_started_screen' )
		);

		// Changelog Page
		add_submenu_page(
			'edit.php?post_type=gravityview',
			__( 'Changelog', 'gravity-view' ),
			__( 'Changelog', 'gravity-view' ),
			$this->minimum_capability,
			'gv-changelog',
			array( $this, 'changelog_screen' )
		);

		// Credits Page
		add_submenu_page(
			'edit.php?post_type=gravityview',
			__( 'Credits', 'gravity-view' ),
			__( 'Credits', 'gravity-view' ),
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
		<style type="text/css" media="screen">
		/*<![CDATA[*/

		.update-nag { display: none; }
		.clear { clear: both; display: block; width: 100%; }
		.gv-welcome-screenshots {
			float: right;
			clear:right;
			max-width:50%;
			border: 1px solid #ccc;
			margin: 0 10px 10px 1.25rem!important;
		}
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
		?>

		<h1><img class="alignleft" src="<?php echo plugins_url( 'images/astronaut-200x263.png', GRAVITYVIEW_FILE ); ?>" width="100" height="132" /><?php printf( __( 'Welcome to GravityView %s', 'gravity-view' ), $display_version ); ?></h1>
		<div class="about-text"><?php _e( 'Thank you for Installing GravityView. Beautifully display your Gravity Forms entries.', 'gravity-view' ); ?></div>

		<h2 class="nav-tab-wrapper clear">
			<a class="nav-tab <?php echo $selected == 'gv-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-getting-started', 'post_type' => 'gravityview'), 'edit.php' ) ) ); ?>">
				<?php _e( "Getting Started", 'gravity-view' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'gv-changelog' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-changelog', 'post_type' => 'gravityview'), 'edit.php' ) ) ); ?>">
				<?php _e( "List of Changes", 'gravity-view' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'gv-credits' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-credits', 'post_type' => 'gravityview'), 'edit.php' ) ) ); ?>">
				<?php _e( 'Credits', 'gravity-view' ); ?>
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

		<div style="text-align:center; padding-top: 1em;">
			<h2>Read more articles on using GravityView</h2>
			<p><a class="button button-primary button-hero" href="https://gravityview.co/support/documentation/?zen_section=200480627">Getting Started Articles</a></p>
		</div>

		<div class="wrap about-wrap">

			<div class="changelog"><h2 class="about-headline-callout">Configuring a View</h2></div>

			<div class="feature-section col two-col" style="margin-top:1em;">

				<div>

					<h2>Create a View</h2>

					<ol class="ol-decimal">
						<li>Go to <a href="<?php echo admin_url('post-new.php?post_type=gravityview'); ?>">Views &gt; New View</a></li>
						<li>If you want to <strong>create a new form</strong>, click the "Start Fresh" button</li>
						<li>If you want to <strong>use an existing form&rsquo;s entries</strong>, select from the dropdown.</li>
						<li>Select the type of View you would like to create. There are two core types of Views: <strong>Table</strong>, <strong>Listing</strong>, and <strong>DataTables</strong>.
							<ul class="ul-square">
								<li><strong>Table Views</strong> output entries as tables; a grid of data.</li>
								<li><strong>Listing Views</strong> display entries in a more visual layout.</li>
								<li><strong>DataTables</strong> display entries in a dynamic table with advanced sorting capabilities provided by the <a href="http://datatables.net">DataTables</a> script.</li>
							</ul>
						</li>
					</ol>
				</div>

				<div class="last-feature">
				<h2>Embed Views in Posts &amp; Pages</h2>
					<p><img src="<?php echo plugins_url( 'images/screenshots/add-view-button.png', GRAVITYVIEW_FILE ); ?>" class="gv-welcome-screenshots" height="35" width="97" />Unlike the Gravity Forms Directory plugin, views are stand-alone; they don&rsquo;t need to always be embedded, but you can still embed Views using the "Add View" button.</p>
				</div>

			</div>

			<div class="feature-section clear">
				<h2>Configure Mulitple Entry &amp; Single Entry Layouts</h2>
				<p><img src="<?php echo plugins_url( 'images/screenshots/add-field.png', GRAVITYVIEW_FILE ); ?>" alt="Add a field dialog box" class="gv-welcome-screenshots" />You can configure how <strong>Multiple Entry</strong> and <strong>Single Entry</strong>. These can be configured by using the tabs under "View Configuration."</p>

				<ul class="ul-disc">
					<li>Click "+ Add Field" to add a field to a zone</li>
					<li>Fields can be dragged and dropped to be re-arranged. Hover over the field until you see a cursor with four arrows, then drag the field.</li>
					<li>Click the <a href="#" style="text-decoration:none;"><i class="dashicons dashicons-admin-generic"></i></a> gear icon on each field to configure the <strong>Field Settings</strong>:
					<ul class="ul-square">
						<li><em>Custom Label</em>: Change how the label is shown on the website. Default: the name of the field</li>
						<li><em>Custom CSS Class</em>: Add additional CSS classes to the field container</li>
						<li><em>Use this field as a search filter</em>: Allow searching the text of a field, or narrowing visible results using the field.</li>
						<li><em>Only visible to logged in users with role</em>: Make certain fields visible only to users who are logged in.</li>
					</ul>
					</li>
				</ul>
			</div>

			<div class="clear">
				<h2>What is a View?</h2>
				<p>When a form is submitted in Gravity Forms, an entry is created. Without GravityView, Gravity Forms entries are visible only in the WordPress dashboard, and only to users with permission.</p>

				<p>GravityView allows you to display entries on the front of your site. In GravityView, when you arrange the fields you want displayed and save the configuration, it's called a "View".</p>
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

			<div class="changelog point-releases">

				<h3>What changed in 1.1.6</h3>
				<ul>
					<li><img src="<?php echo plugins_url( 'images/screenshots/single-entry-link.png', GRAVITYVIEW_FILE ); ?>" class="gv-welcome-screenshots alignright" />A link icon is shown when a field is being used as a link to the Single Entry mode (see screenshot)</li>
					<li>Fixed: Approve / Disapprove all entries using Gravity Forms bulk edit entries form (previously, only visible entries were affected)</li>
					<li>Email addresses are now encrypted by default to prevent spammers</li>
					<li>Fixed: License Activation works when No-Conflict Mode is enabled</li>
					<li>Fixed: Fields not always saving properly when adding lots of fields with the "Add All Fields" button</li>
					<li>Fixed: Recognize single entry when using WordPress "Default" Permalink setting</li>
					<li>Fixed: Edit Entry issues
						<ul>
							<li>Fixed form validation errors when a scheduled form has expired and also when a form has reached its entry limit</li>
							<li>Fixed PHP warning messages when editing entries</li>
							<li>When an Edit Entry form is submitted and there are errors, the submitted values stay in the form; the user won't need to fill in the form again.</li>
						</ul>
					</li>
					<li>Added: Email field settings
						<ul>
							<li>Added option to display email plaintext or as a link</li>
							<li>Added subject and body settings: when the link is clicked, you can choose to have these values pre-filled</li>
						</ul>
					</li>
					<li>Source URL field settings, including show as a link and custom link text</li>
					<li>Fixed: Empty truncated URLs no longer get shown</li>
					<li>Fixed: Date Created field now respects the blog's timezone setting, instead of using UTC time</li>
					<li>Fixed: Product sub-fields (Name, Quantity &amp; Price) displayed properly</li>
					<li>Fixed: Empty entry display when using Job Board preset caused by incorrect template files being loaded</li>
					<li>Fixed: Files now can be deleted when a non-administrator is editing an entry</li>
					<li>Fixed: PHP Notices on Admin Views screen for users without edit all entries capabilities</li>
					<li>Modified: Added ability to customize and translate the Search Bar's date picker. You can now fully customize the date picker.</li>
					<li>Tweak: Added helper text when a new form is created by GravityView</li>
					<li>Tweak: Renamed "Description" drop zone to "Other Fields" to more accurately represent use</li>
					<li>Tweak: Remove all fields from a zone by holding down the Alt key while clicking the remove icon</li>
					<li><strong>And much more!</strong> See <a href="<?php echo plugins_url('readme.txt', GRAVITYVIEW_FILE ); ?>">the full plugin changelog</a> for more information.</li>
				</ul>


				<h3>What changed in 1.1.5</h3>
				<ul>
					<li>Added: New "Edit" link in Gravity Forms Entries screen - allows you to easily access the Edit screen for an entry.</li>
					<li>Fixed: Show tooltips when No Conflict Mode is enabled</li>
					<li>Fixed: Merge Vars for labels in Single Entry table layouts</li>
					<li>Fixed: Duplicate "Edit Entry" fields in field picker</li>
					<li>Fixed: Custom date formatting for Date Created field</li>
					<li>Fixed: Searching full names or addresses now works</li>
					<li>Fixed: Custom CSS classes are now added to cells in table-based Views</li>
					<li>Updated: Turkish translation by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a></li>
				</ul>

				<h3>What changed in 1.1.4</h3>
				<ul>
					<li>Fixed: Sort &amp; Filter box not displaying</li>
					<li>Fixed: Multi-select fields now display as drop-down field instead of text field in the search bar widget</li>
					<li>Fixed: Edit Entry now compatibile with Gravity Forms forms when "No Duplicates" is enabled</li>
				</ul>

				<h3>What changed in 1.1.3</h3>
				<ul>
					<li>Fixed: Fatal error on activation when running PHP 5.2</li>
					<li>Fixed: PHP notice when in No-Conflict mode</li>
				</ul>

				<h3>What changed in 1.1.2</h3>
				<ul>
					<li>Added: Extensions framework to allow for extensions to auto-update</li>
					<li>Fixed: Entries not displaying in Visual Composer plugin editor</li>
					<li>Fixed: Allow using images as link to entry</li>
					<li>Fixed: Updated field layout in Admin to reflect actual layout of listings (full-width title and subtitle above image)</li>
					<li>Fixed: When trying to access an entry that doesn't exist (it had been permanently deleted), don't throw an error</li>
					<li>Fixed: Default styles not being enqueued when embedded using the shortcode (fixes vertical pagination links)</li>
					<li>Fixed: Improved style for Edit Entry mode</li>
					<li>Fixed: Editing entry updates the Approved status</li>
					<li>New translations - thank you, everyone!
						<ul>
							<li>Romanian translation by <a href="https://www.transifex.com/accounts/profile/ArianServ/">@ArianServ</a></li>
							<li>Finnish translation by <a href="https://www.transifex.com/accounts/profile/harjuja/">@harjuja</a></li>
							<li>Spanish translation by <a href="https://www.transifex.com/accounts/profile/jorgepelaez/">@jorgepelaez</a></li>
						</ul>
					</li>
				</ul>

				<h3>What changed in 1.1.1</h3>

				<ul>
					<li><strong>We fixed license validation and auto-updates</strong>. Sorry for the inconvenience of having to re-download the plugin!</li>
					<li>Added: View Setting to allow users to edit only entries they created.</li>
					<li>Fixed: Could not edit an entry with Confirm Email fields</li>
					<li>Fixed: Field setting layouts not persisting</li>
					<li>Updated: Bengali translation by <a href="https://www.transifex.com/accounts/profile/tareqhi/">@tareqhi</a></li>
					<li>Fixed: Logging re-enabled in Admin</li>
					<li>Tweak: Added links to View Type picker to live demos of presets.</li>
					<li>Tweak: Added this "List of Changes" tab.</li>
				</ul>

				<h3>What changed in 1.1</h3>
				<ul>
					<li>Refactored (re-wrote) View data handling. Now saves up to 10 queries on each page load.</li>
					<li>Fixed: Infinite loop for rendering <code>post_content</code> fields</li>
					<li>Fixed: Page length value now respected for DataTables</li>
					<li>Fixed: Formatting of DataTables fields is now processed the same way as other fields. Images now work, for example.</li>
					<li>Modified: Removed redundant <code>gravityview_hide_empty_fields</code> filters</li>
					<li>Fixed/Modified: Enabled <q>wildcard</q> search instead of strict search for field searches.</li>
					<li>Added: <code>gravityview_search_operator</code> filter to modify the search operator used by the search.</li>
					<li>Added: <code>gravityview_search_criteria</code> filter to modify all search criteria before being passed to Gravity Forms</li>
					<li>Added: Website Field setting to display shortened link instead of full URL</li>
					<li>Fixed: Form title gets replaced properly in merge tags</li>
					<li>Modified: Tweaked preset templates</li>
				</ul>


				<h3>What changed in 1.0.9</h3>
				<div class="alignright">
					<img src="<?php echo plugins_url( 'images/screenshots/edit-form-buttons.png', GRAVITYVIEW_FILE ); ?>" alt="Edit Form Buttons" class="gv-welcome-screenshots" />
					<p class="howto" style="text-align:center;">New Buttons form Gravity Forms</p>
				</div>
				<ul>
					<li>Added: Time field support, with date format default and options</li>
					<li>Added: <q>Event Listings</q> View preset</li>
					<li>Added: <q>Show Entry On Website</q> Gravity Forms form button. This is meant to be an opt-in checkbox that the user sees and can control, unlike the <q>Approve/Reject</q> button, which is designed for adminstrators to manage approval.</li>
					<li>Improved horizontal search widget layout</li>
					<li>Fixed: Only show Edit Entry link to logged-in users</li>
					<li>Updated: Dutch translation by <a href="https://www.transifex.com/accounts/profile/leooosterloo/">@leooosterloo</a> (100% coverage, thank you!)</li>
				</ul>

				<h3>What changed in 1.0.8</h3>
				<ul>
					<li><img src="<?php echo plugins_url( 'images/screenshots/edit-entry-link.png', GRAVITYVIEW_FILE ); ?>" alt="Edit Entry Link" class="gv-welcome-screenshots alignright" /><strong>Edit Entry</strong> - you can add an Edit Entry link using the <q>Add Field</q> buttons in either the Multiple Entries or Single Entry tab.
						<ul>
							<li>For now, if the user has the ability to edit entries in Gravity Forms, they’ll be able to edit entries in GravityView. Moving forward, we&#39;ll be adding refined controls over who can edit which entries.</li>
							<li>It supports modifying existing Entry uploads and the great Multiple-File Upload field.</li>
						</ul>
					</li>
					<li>Fixed: Insert View embed code now works again</li>
					<li>Fixed: Filtering by date now working</li>
				</ul>
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

			<p class="about-description"><?php _e( 'GravityView is brought to you by:', 'gravity-view' ); ?></p>

			<div class="feature-section col two-col">

				<div>
					<h2>Zack Katz</h2>
					<h4 style="font-weight:0; margin-top:0">Project Lead &amp; Developer</h4>
					<p></p>
					<p><img style="float:left; margin: 0 15px 0 0;" src="<?php echo plugins_url( 'images/zack.png', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Zack has been developing integrations with Gravity Forms since 2009. He is the President of Katz Web Services and lives with his wife and cat in Denver, Colorado.</p>
					<p><a href="https://katz.co">View Zack&rsquo;s website</a></p>
				</div>

				<div class="last-feature">
					<h2>Luis Godinho</h2>
					<h4 style="font-weight:0; margin-top:0">Developer &amp; Support</h4>
					<p><img class="alignleft avatar" src="<?php echo plugins_url( 'images/luis.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Luis is a WordPress developer passionate about WordPress. He is a co-founder and partner of GOMO, a digital agency located in Lisbon, Portugal.</p>
					<p><a href="http://tinygod.pt">View Luis&rsquo;s website</a></p>
				</div>

			</div>

			<hr class="clear" />

			<div class="feature-section">
				<div>
					<h2><?php esc_attr_e( 'Contributors', 'gravity-view' ); ?></h2>

					<ul class="wp-people-group">
						<li class="wp-person">Bengali translation by <a href="https://www.transifex.com/accounts/profile/tareqhi/">@tareqhi</a></li>
						<li class="wp-person">German translation by <a href="https://www.transifex.com/accounts/profile/seschwarz/">@seschwarz</a>, <a href="https://www.transifex.com/accounts/profile/abdmc/">@abdmc</a>, and <a href="https://www.transifex.com/accounts/profile/deckerweb/">@deckerweb</a></li>
						<li class="wp-person">Turkish translation by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a></li>
						<li class="wp-person">Dutch translation by <a href="https://www.transifex.com/accounts/profile/leooosterloo/">@leooosterloo</a> and <a href="https://www.transifex.com/accounts/profile/Weergeven/">@Weergeven</a></li>
						<li class="wp-person">Hungarian translation by <a href="https://www.transifex.com/accounts/profile/dbalage/">@dbalage</a>!</li>
						<li class="wp-person">Italian translation by <a href="https://www.transifex.com/accounts/profile/ClaraDiGennaro/">@ClaraDiGennaro</a></li>
						<li class="wp-person">French translation by <a href="https://www.transifex.com/accounts/profile/franckt/">@franckt</a> and <a href="https://www.transifex.com/accounts/profile/Newbdev/">@Newbdev</a></li>
						<li class="wp-person">Portuguese translation by <a href="https://www.transifex.com/accounts/profile/luistinygod/">@luistinygod</a></li>
						<li class="wp-person">Portuguese translation by <a href="https://www.transifex.
						com/accounts/profile/luistinygod/">@luistinygod</a></li>
						<li class="wp-person">Romanian translation by <a href="https://www.transifex.com/accounts/profile/ArianServ/">@ArianServ</a></li>
						<li class="wp-person">Finnish translation by <a href="https://www.transifex.com/accounts/profile/harjuja/">@harjuja</a></li>
						<li class="wp-person">Spanish translation by <a href="https://www.transifex.com/accounts/profile/jorgepelaez/">@jorgepelaez</a>, <a href="https://www.transifex.com/accounts/profile/luisdiazvenero/">@luisdiazvenero</a>, and <a href="https://www.transifex.com/accounts/profile/josemv/">@josemv</a></li>
						<li class="wp-person">Code contributions by <a href="https://github.com/ryanduff">@ryanduff</a></li>

						<!-- No translation strings yet... -->
						<!-- <li class="wp-person">Greek translation by <a href="https://www.transifex.com/accounts/profile/asteri/">@asteri</a></li> -->
						<!-- <li class="wp-person">Russian translation by <a href="https://www.transifex.com/accounts/profile/badsmiley/">@badsmiley</a></li> -->

					</ul>

					<h4><?php esc_attr_e( 'Want to contribute?', 'gravity-view' ); ?></h4>
					<p><?php echo sprintf( esc_attr__( 'If you want to contribute to the code, you can %srequest access to the Github repository%s. If your contributions are accepted, you will be thanked here.', 'gravity-view'), '<a href="mailto:zack@katzwebservices.com?subject=Github%20Access">', '</a>' ); ?></p>
				</div>
			</div>

			<hr class="clear" />

			<div class="changelog">

				<h4>Thanks to the following open-source software:</h4>

				<ul>
					<li><a href="http://datatables.net/">DataTables</a> - amazing tool for table data display. Many thanks!</li>
					<li><a href="http://reduxframework.com">ReduxFramework</a> - a powerful settings library</li>
					<li><a href="https://github.com/GaryJones/Gamajo-Template-Loader">Gamajo Template Loader</a> - makes it easy to load template files with user overrides</li>
					<li><a href="https://github.com/carhartl/jquery-cookie">jQuery Cookie plugin</a> - Access and store cookie values with jQuery</li>
					<li><a href="http://katz.si/gf">Gravity Forms</a> - If Gravity Forms weren't such a great plugin, GravityView wouldn't exist!</li>
					<li>GravityView uses icons made by Freepik, Adam Whitcroft, Amit Jakhu, Zurb, Scott de Jonge, Yannick, Picol, Icomoon, TutsPlus, Dave Gandy, SimpleIcon from <a href="http://www.flaticon.com" title="Flaticon">www.flaticon.com</a></li>
					<li><a href="https://github.com/jnicol/standalone-phpenkoder">PHPEnkoder</a> script encodes the email addresses.</li>
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
