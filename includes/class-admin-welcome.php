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

		<h1><img class="alignleft" src="<?php echo plugins_url( 'assets/images/astronaut-200x263.png', GRAVITYVIEW_FILE ); ?>" width="100" height="132" /><?php printf( esc_html__( 'Welcome to GravityView %s', 'gravityview' ), $display_version ); ?></h1>
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

		<div class="wrap about-wrap">

			<div style="text-align:center; padding-top: 1em;">
				<h2>Read more articles on using GravityView</h2>
				<p><a class="button button-primary button-hero" href="http://docs.gravityview.co/category/24-category">Getting Started Articles</a></p>
			</div>

			<div class="changelog"><h2 class="about-headline-callout">Configuring a View</h2></div>

			<div class="feature-section col two-col" style="margin-top:1em;">

				<div>

					<h2>Create a View</h2>

					<ol class="ol-decimal">
						<li>Go to <a href="<?php echo admin_url('post-new.php?post_type=gravityview'); ?>">Views &gt; New View</a></li>
						<li>If you want to <strong>create a new form</strong>, click the "Start Fresh" button</li>
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

				<div class="last-feature">
				<h2>Embed Views in Posts &amp; Pages</h2>
					<p><img src="<?php echo plugins_url( 'assets/images/screenshots/add-view-button.png', GRAVITYVIEW_FILE ); ?>" class="gv-welcome-screenshots" height="35" width="97" />Views don&rsquo;t need to be embedded in a post or page, but you can if you want. Embed Views using the "Add View" button above your content editor.</p>
				</div>

			</div>

			<div class="feature-section clear">
				<h2>Configure Multiple Entry, Single Entry, and Edit Entry Layouts</h2>
				<p><img src="<?php echo plugins_url( 'assets/images/screenshots/add-field.png', GRAVITYVIEW_FILE ); ?>" alt="Add a field dialog box" class="gv-welcome-screenshots" />You can configure what fields are displayed in <strong>Multiple Entry</strong>, <strong>Single Entry</strong>, and <strong>Edit Entry</strong> modes. These can be configured by clicking on the tabs in "View Configuration."</p>

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

				<h2 class="subtitle" style="text-align: center;"><?php esc_html_e('What&rsquo;s New', 'gravityview' ); ?></h2>

				<div class="feature-section col two-col">

					<div class="col col-1">
						<div class="media-container"><img src="<?php echo plugins_url( 'assets/images/screenshots/format-number.png', GRAVITYVIEW_FILE ); ?>" alt="Format number"></div>
						<h4 class="higher">Number Field Formatting</h4>
						<p>Now you can choose to use thousands separators (or not), and define decimal precision!</p>
					</div>

					<div class="col col-2 last-feature">
						<div class="media-container" style="min-height:143px;"><img src="<?php echo plugins_url( 'assets/images/screenshots/toolbar.png', GRAVITYVIEW_FILE ); ?>" alt="Toolbar link to Edit View"></div>
						<h4 class="higher">Edit View in the Toolbar</h4>
						<p>Editing a View from the front of your site used to take a bunch of clicks. Now a link to edit the embedded View is just a click away in the Toolbar.</p>
					</div>
				</div>

				<hr />

				<h3>1.13.1 on August 26</h3>

				<ul>
					<li>Fixed: Potential XSS security issue. <strong>Please update.</strong></li>
					<li>Fixed: The cache was not being reset properly for entry changes, including:

						<ul>
							<li>Starring/unstarring</li>
							<li>Moving to/from the trash</li>
							<li>Changing entry owner</li>
							<li>Being marked as spam</li>
						</ul>
					</li>
					<li>Fixed: Delete entry URL not properly passing some parameters (only affecting pages with multiple <code>[gravityview]</code> shortcodes)</li>
					<li>Added: <code>gravityview/delete-entry/mode</code> filter. When returning "trash", "Delete Entry" moves entries to the trash instead of permanently deleting them.</li>
					<li>Added: <code>gravityview/admin/display_live_chat</code> filter to disable live chat widget</li>
					<li>Added: <code>gravityview/delete-entry/message</code> filter to modify the "Entry Deleted" message content</li>
					<li>Tweak: Improved license activation error handling by linking to relevant account functions</li>
					<li>Tweak: Added settings link to plugin page actions</li>
					<li>Tweak: Improved code documentation</li>
					<li>Updated Translations:

						<ul>
							<li>Bengali translation by <a href="https://www.transifex.com/accounts/profile/tareqhi/">@tareqhi</a></li>
							<li>Turkish translation by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a></li>
						</ul>
					</li>
					<li>New: Released a new <a href="http://codex.gravityview.co">GravityView Codex</a> for developers</li>
				</ul>

				<h3>1.13 on August 18</h3>

				<ul>
					<li>Fixed: Wildcard search broken for Gravity Forms 1.9.12+</li>
					<li>Fixed: Edit Entry validation messages not displaying for Gravity Forms 1.9.12+</li>
					<li>Added: Number field settings

						<ul>
							<li>Format number: Display numbers with thousands separators</li>
							<li>Decimals: Precision of the number of decimal places. Leave blank to use existing precision.</li>
						</ul>
					</li>
					<li>Added: <code>detail</code> parameter to the <code>[gravityview]</code> shortcode. <a href="http://docs.gravityview.co/article/73-using-the-shortcode#detail-parameter">Learn more</a></li>
					<li>Added: <code>context</code> parameter to the <code>[gvlogic]</code> shortcode to show/hide content based on current mode (Multiple Entries, Single Entry, Edit Entry). <a href="http://docs.gravityview.co/article/252-gvlogic-shortcode#context">Learn more</a></li>
					<li>Added: Allow to override the entry saved value by the dynamic populated value on the Edit Entry view using the <code>gravityview/edit_entry/pre_populate/override</code> filter</li>
					<li>Added: "Edit View" link in the Toolbar when on an embedded View screen</li>
					<li>Added: <code>gravityview_is_hierarchical</code> filter to enable defining a Parent View</li>
					<li>Added: <code>gravityview/merge_tags/do_replace_variables</code> filter to enable/disable replace_variables behavior</li>
					<li>Added: <code>gravityview/edit_entry/verify_nonce</code> filter to override nonce validation in Edit Entry</li>
					<li>Added: <code>gravityview_strip_whitespace()</code> function to strip new lines, tabs, and multiple spaces and replace with single spaces</li>
					<li>Added: <code>gravityview_ob_include()</code> function to get the contents of a file using combination of <code>include()</code> and <code>ob_start()</code></li>
					<li>Fixed: Edit Entry link not showing for non-admins when using the DataTables template</li>
					<li>Fixed: Cache wasn't being used for <code>get_entries()</code></li>
					<li>Fixed: Extension class wasn't properly checking requirements</li>
					<li>Fixed: Issue with some themes adding paragraphs to Javascript tags in the Edit Entry screen</li>
					<li>Fixed: Duplicated information in the debugging logs</li>
					<li>Updated: "Single Entry Title" and "Back Link Label" settings now support shortcodes, allowing for you to use <a href="http://docs.gravityview.co/article/252-gvlogic-shortcode"><code>[gvlogic]</code></a></li>
					<li>Updated: German and Portuguese translations</li>
				</ul>

				<h3>1.12 on August 5</h3>

				<ul>
					<li>Fixed: Conflicts with Advanced Filter extension when using the Recent Entries widget</li>
					<li>Fixed: Sorting icons were being added to List template fields when embedded on the same page as Table templates</li>
					<li>Fixed: Empty Product fields would show a string (", Qty: , Price:") instead of being empty. This prevented "Hide empty fields" from working</li>
					<li>Fixed: When searching on the Entry Created date, the date used GMT, not blog timezone</li>
					<li>Fixed: Issue accessing settings page on Multisite</li>
					<li>Fixed: Don't show View post types if GravityView isn't valid</li>
					<li>Fixed: Don't redirect to the List of Changes screen if you've already seen the screen for the current version</li>
					<li>Fixed: When checking license status, the plugin can now fix PHP warnings caused by other plugins that messed up the requests</li>
					<li>Fixed: In Multisite, only show notices when it makes sense to</li>
					<li>Added: <code>gravityview/common/sortable_fields</code> filter to override which fields are sortable</li>
					<li>Tweak: Extension class added ability to check for required minimum PHP versions</li>
					<li>Tweak: Made the <code>GravityView_Plugin::$theInstance</code> private and renamed it to <code>GravityView_Plugin::$instance</code>. If you're a developer using this, please use <code>GravityView_Plugin::getInstance()</code> instead.</li>
					<li>Updated: French translation</li>
				</ul>

				<h3>1.11.2 on July 22</h3>

				<ul>
					<li>Fixed: Bug when comparing empty values with <code>[gvlogic]</code></li>
					<li>Fixed: Remove extra whitespace when comparing values using <code>[gvlogic]</code></li>
					<li>Modified: Allow Avada theme Javascript in "No-Conflict Mode"</li>
					<li>Updated: French translation</li>
				</ul>


				<h3>1.11.1 on July 20</h3>

				<ul>
					<li>Added: New filter hook to customise the cancel Edit Entry link: <code>gravityview/edit_entry/cancel_link</code></li>
					<li>Fixed: Extension translations</li>
					<li>Fixed: Dropdown inputs with long field names could overflow field and widget settings</li>
					<li>Modified: Allow Genesis Framework CSS and Javascript in "No-Conflict Mode"</li>
					<li>Updated: Danish translation (thanks <a href="https://www.transifex.com/accounts/profile/jaegerbo/">@jaegerbo</a>!) and German translation</li>
				</ul>


				<h3>1.11 on July 15</h3>

				<ul>
					<li>Added: GravityView now updates WordPress user profiles when an entry is updated while using the Gravity Forms User Registration Add-on</li>
					<li>Fixed: Removed User Registration Add-on validation when updating an entry</li>
					<li>Fixed: Field custom class not showing correctly on the table header</li>
					<li>Fixed: Editing Time fields wasn't displaying saved value</li>
					<li>Fixed: Conflicts with the date range search when search inputs are empty</li>
					<li>Fixed: Conflicts with the Other Entries field when placing a search:

						<ul>
							<li>Developer note: the filter hook <code>gravityview/field/other_entries/args</code> was replaced by "gravityview/field/other_entries/criteria". If you are using this filter, please <a href="mailto:support@gravityview.co">contact support</a> before updating so we can help you transition</li>
						</ul>
					</li>
					<li>Updated: Turkish translation (thanks <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a>!) and Mexican translation (thanks <a href="https://www.transifex.com/accounts/profile/jorgepelaez/">@jorgepelaez</a>!)</li>
				</ul>


				<h3>1.10 on June 25</h3>

				<ul>
					<li>Update: Due to the new Edit Entry functionality, GravityView now requires Gravity Forms 1.9 or higher</li>
					<li>Fixed: Editing Hidden fields restored</li>
					<li>Fixed: Edit Entry and Delete Entry may not always show in embedded Views</li>
					<li>Fixed: Search Bar "Clear" button Javascript warning in Internet Explorer</li>
					<li>Fixed: Edit Entry styling issues with input sizes. Edit Entry now uses 100% Gravity Forms styles.</li>
					<li>Added: <code>[gv_edit_entry_link]</code> and <code>[gv_delete_entry_link]</code> shortcodes. <a href="http://docs.gravityview.co/article/287-edit-entry-and-delete-entry-shortcodes">Read how to use them</a></li>
				</ul>

				<h3>1.9.1 on June 24</h3>

				<ul>
					<li>Fixed: Allow "Admin Only" fields to appear in Edit Entry form</li>
				</ul>

				<h3>1.9 on June 23</h3>

				<ul>
					<li>Added: Edit Entry now takes place in the Gravity Forms form layout, not in the previous layout. This means:

						<ul>
							<li>Edit Entry now supports Conditional Logic - as expected, fields will show and hide based on the form configuration</li>
							<li>Edit Entry supports <a href="https://www.gravityhelp.com/css-ready-classes-for-gravity-forms/">Gravity Forms CSS Ready Classes</a> - the layout you have configured for your form will be used for Edit Entry, too.</li>
							<li>If you customized the CSS of your Edit Entry layout, <strong>you will need to update your stylesheet</strong>. Sorry for the inconvenience!</li>
							<li>If visiting an invalid Edit Entry link, you are now provided with a back link</li>
							<li>Product fields are now hidden by default, since they aren't editable. If you want to instead display the old message that "product fields aren't editable," you can show them using the new <code>gravityview/edit-entry/hide-product-fields</code> filter</li>
						</ul>
					</li>
					<li>Added: Define column widths for fields in each field's settings (for Table and DataTable View Types only)</li>
					<li>Added: <code>{created_by}</code> Merge Tag that displays information from the creator of the entry (<a href="http://docs.gravityview.co/article/281-the-createdby-merge-tag">learn more</a>)</li>
					<li>Added: Edit Entry field setting to open link in new tab/window</li>
					<li>Added: CSS classes to the Update/Cancel/Delete buttons (<a href="http://docs.gravityview.co/article/63-css-guide#edit-entry">learn more</a>)</li>
					<li>Fixed: Shortcodes not processing properly in DataTables Extension</li>
					<li>Tweak: Changed support widget to a Live Chat customer support and feedback form widget</li>
				</ul>

				<p style="text-align: center">
					<a href="https://gravityview.co/changelog/" class="aligncenter button button-secondary button-hero" style="margin: 0 auto; display: inline-block;">View All Changes</a>
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

			<div class="feature-section col three-col">

				<div class="col">
					<h2>Zack Katz</h2>
					<h4 style="font-weight:0; margin-top:0">Project Lead &amp; Developer</h4>
					<p></p>
					<p><img style="float:left; margin: 0 15px 10px 0;" src="<?php echo plugins_url( 'assets/images/zack.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Zack has been developing integrations with Gravity Forms since 2009. He is the President of Katz Web Services and lives with his wife (and cat) in Denver, Colorado.</p>
					<p><a href="https://katz.co">View Zack&rsquo;s website</a></p>
				</div>

				<div class="col last-feature">
					<h2>Luis Godinho</h2>
					<h4 style="font-weight:0; margin-top:0">Developer &amp; Support</h4>
					<p><img style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/luis.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Luis is a WordPress developer passionate about WordPress. He is a co-founder and partner of GOMO, a digital agency located in Lisbon, Portugal.</p>
					<p><a href="http://tinygod.pt">View Luis&rsquo;s website</a></p>
				</div>

			</div>

			<hr class="clear" />

			<div class="feature-section">
				<div>
					<h2><?php esc_attr_e( 'Contributors', 'gravityview' ); ?></h2>

					<ul class="wp-people-group">
						<li class="wp-person">Bengali translation by <a href="https://www.transifex.com/accounts/profile/tareqhi/">@tareqhi</a></li>
						<li class="wp-person">German translation by <a href="https://www.transifex.com/accounts/profile/seschwarz/">@seschwarz</a>, <a href="https://www.transifex.com/accounts/profile/abdmc/">@abdmc</a>, and <a href="https://www.transifex.com/accounts/profile/deckerweb/">@deckerweb</a></li>
						<li class="wp-person">Turkish translation by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a></li>
						<li class="wp-person">Dutch translation by <a href="https://www.transifex.com/accounts/profile/leooosterloo/">@leooosterloo</a>, <a href="https://www.transifex.com/accounts/profile/Weergeven/">@Weergeven</a>, and <a href="https://www.transifex.com/accounts/profile/erikvanbeek/">@erikvanbeek</a></li>
						<li class="wp-person">Hungarian translation by <a href="https://www.transifex.com/accounts/profile/dbalage/">@dbalage</a> and <a href="https://www.transifex.com/accounts/profile/Darqebus/">@Darqebus</a></li>
						<li class="wp-person">Italian translation by <a href="https://www.transifex.com/accounts/profile/Lurtz/">@Lurtz</a> and <a href="https://www.transifex.com/accounts/profile/ClaraDiGennaro/">@ClaraDiGennaro</a></li>
						<li class="wp-person">French translation by <a href="https://www.transifex.com/accounts/profile/franckt/">@franckt</a> and <a href="https://www.transifex.com/accounts/profile/Newbdev/">@Newbdev</a></li>
						<li class="wp-person">Portuguese translation by <a href="https://www.transifex.com/accounts/profile/luistinygod/">@luistinygod</a></li>
						<li class="wp-person">Romanian translation by <a href="https://www.transifex.com/accounts/profile/ArianServ/">@ArianServ</a></li>
						<li class="wp-person">Finnish translation by <a href="https://www.transifex.com/accounts/profile/harjuja/">@harjuja</a></li>
						<li class="wp-person">Spanish translation by <a href="https://www.transifex.com/accounts/profile/jorgepelaez/">@jorgepelaez</a>, <a href="https://www.transifex.com/accounts/profile/luisdiazvenero/">@luisdiazvenero</a>, and <a href="https://www.transifex.com/accounts/profile/josemv/">@josemv</a></li>
						<li class="wp-person">Swedish translation by <a href="https://www.transifex.com/accounts/profile/adamrehal/">@adamrehal</a></li>
						<li class="wp-person">Indonesian translation by <a href="https://www.transifex.com/accounts/profile/sariyanta/">@sariyanta</a></li>
						<li class="wp-person">Norwegian translation by <a href="https://www.transifex.com/accounts/profile/aleksanderespegard/">@aleksanderespegard</a></li>
						<li class="wp-person">Danish translation by <a href="https://www.transifex.com/accounts/profile/jaegerbo/">@jaegerbo</a></li>
						<li class="wp-person">Code contributions by <a href="https://github.com/ryanduff">@ryanduff</a> and <a href="https://github.com/dmlinn">@dmlinn</a></li>
					</ul>

					<h4><?php esc_attr_e( 'Want to contribute?', 'gravityview' ); ?></h4>
					<p><?php echo sprintf( esc_attr__( 'If you want to contribute to the code, %syou can on Github%s. If your contributions are accepted, you will be thanked here.', 'gravityview'), '<a href="https://github.com/katzwebservices/GravityView">', '</a>' ); ?></p>
				</div>
			</div>

			<hr class="clear" />

			<div class="changelog">

				<h4>Thanks to the following open-source software:</h4>

				<ul>
					<li><a href="http://datatables.net/">DataTables</a> - amazing tool for table data display. Many thanks!</li>
					<li><a href="https://github.com/GaryJones/Gamajo-Template-Loader">Gamajo Template Loader</a> - makes it easy to load template files with user overrides</li>
					<li><a href="https://github.com/carhartl/jquery-cookie">jQuery Cookie plugin</a> - Access and store cookie values with jQuery</li>
					<li><a href="http://katz.si/gf">Gravity Forms</a> - If Gravity Forms weren't such a great plugin, GravityView wouldn't exist!</li>
					<li>GravityView uses icons made by Freepik, Adam Whitcroft, Amit Jakhu, Zurb, Scott de Jonge, Yannick, Picol, Icomoon, TutsPlus, Dave Gandy, SimpleIcon from <a href="http://www.flaticon.com" title="Flaticon">www.flaticon.com</a></li>
					<li><a href="https://github.com/jnicol/standalone-phpenkoder">PHPEnkoder</a> script encodes the email addresses.</li>
					<li>The Duplicate View functionality is based on the excellent <a href="http://lopo.it/duplicate-post-plugin/">Duplicate Post plugin</a> by Enrico Battocchi</li>
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
