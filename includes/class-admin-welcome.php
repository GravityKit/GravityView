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
		<div class="about-text"><?php esc_html_e( 'Thank you for Installing GravityView. Beautifully display your Gravity Forms entries.', 'gravityview' ); ?></div>

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
					<p><img src="<?php echo plugins_url( 'assets/images/screenshots/add-view-button.png', GRAVITYVIEW_FILE ); ?>" class="gv-welcome-screenshots" height="35" width="97" />Unlike the Gravity Forms Directory plugin, views are stand-alone; they don&rsquo;t need to always be embedded, but you can still embed Views using the "Add View" button.</p>
				</div>

			</div>

			<div class="feature-section clear">
				<h2>Configure Multiple Entry &amp; Single Entry Layouts</h2>
				<p><img src="<?php echo plugins_url( 'assets/images/screenshots/add-field.png', GRAVITYVIEW_FILE ); ?>" alt="Add a field dialog box" class="gv-welcome-screenshots" />You can configure how <strong>Multiple Entry</strong> and <strong>Single Entry</strong>. These can be configured by using the tabs under "View Configuration."</p>

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

				<div class="feature-section col three-col">

					<div class="col-1">
						<img src="<?php echo plugins_url( 'assets/images/screenshots/gvlogic.png', GRAVITYVIEW_FILE ); ?>" alt="gvlogic: conditional content display">
						<h4 class="higher">Conditional Content Display</h4>
						<p>Show or hide content in Custom Content fields using the new <code>[gvlogic]</code> shortcode.</p>
						<p><a href="http://docs.gravityview.co/article/252-gvlogic-shortcode" class="button button-primary" rel="external" title="Learn how to use it">Learn how to use the shortcode</a></p>
					</div>

					<div class="col-2">
						<img src="<?php echo plugins_url( 'assets/images/screenshots/other-entries.png', GRAVITYVIEW_FILE ); ?>" alt="Configuring the Other Entries field">
						<h4 class="higher">New "Other Entries" Field</h4>
						<p>In Version 1.7.2, you can now list other entries created by the Entry creator.</p>
					</div>

					<div class="col-3 last-feature">
						<img src="<?php echo plugins_url( 'assets/images/screenshots/edit-post-content.png', GRAVITYVIEW_FILE ); ?>" alt="Edit Post Content">
						<h4 class="higher">Edit Post Content</h4>
						<p>You can now edit most Post Fields when you edit an entry.</p>
						<p><a href="http://docs.gravityview.co/article/245-editable-post-fields" class="button button-primary" rel="external" title="Learn what fields are editable">Learn what fields are editable</a></p>
					</div>

				</div>

				<div class="feature-section col three-col">

					<div class="col-1">
						<img src="<?php echo plugins_url( 'assets/images/screenshots/sort-by-column.png', GRAVITYVIEW_FILE ); ?>" alt="Column being sorted">
						<h4 class="higher">Sort Tables by Column</h4>
						<p>Users can sort View results by clicking the sort icons at the top of a table.</p>
						<p><a href="http://docs.gravityview.co/article/230-how-to-enable-the-table-column-sorting-feature" class="button button-secondary" rel="external" title="Read how to enable column sorting">Learn how to enable</a></p>
					</div>

					<div class="col-2">
						<img src="<?php echo plugins_url( 'assets/images/screenshots/search-widget.png', GRAVITYVIEW_FILE ); ?>" alt="A new WordPress search widget">
						<h4 class="higher">A WordPress Search Widget</h4>
						<p>A GravityView search widget that you can place anywhere on your site. Very powerful!</p>
						<p><a href="http://docs.gravityview.co/article/222-the-search-widget" class="button button-secondary" rel="external" title="Learn how to configure the Widget">Learn more</a></p>
					</div>

					<div class="col-3 last-feature">
						<img src="<?php echo plugins_url( 'assets/images/screenshots/recent-entries.png', GRAVITYVIEW_FILE ); ?>" alt="Recent entries widget output">
						<h4 class="higher">Recent Entries Widget</h4>
						<p>Display the most recent entries in your sidebar and customize how it's displayed.</p>
						<p><a href="http://docs.gravityview.co/article/223-the-recent-entries-widget" class="button button-secondary">Setting up recent entries</a></p>
					</div>

				</div>

				<hr />

				<h3>1.7.6.1 on May 7</h3>

				<ul>
					<li>Fixed: Pagination links not working when a search is performed</li>
					<li>Fixed: Return false instead of error if updating approved status fails</li>
					<li>Added: Hooks when an entry approval is updated, approved, or disapproved:

						<ul>
							<li><code>gravityview/approve_entries/updated</code> - Approval status changed (passes $entry_id and status)</li>
							<li><code>gravityview/approve_entries/approved</code> - Entry approved (passes $entry_id)</li>
							<li><code>gravityview/approve_entries/disapproved</code> - Entry disapproved (passes $entry_id)</li>
						</ul>
					</li>
				</ul>


				<h3>1.7.6 on May 5</h3>

				<ul>
					<li>Added WordPress Multisite settings page support

						<ul>
							<li>By default, settings aren't shown on single blogs if GravityView is Network Activated</li>
						</ul>
					</li>
					<li>Fixed: Security vulnerability caused by the usage of <code>add_query_arg</code> / <code>remove_query_arg</code>. <a href="https://blog.sucuri.net/2015/04/security-advisory-xss-vulnerability-affecting-multiple-wordpress-plugins.html">Read more about it</a></li>
					<li>Fixed: Not showing the single entry when using Advanced Filter (<code>ANY</code> mode) with complex fields types like checkboxes</li>
					<li>Fixed: Wrong width for the images in the list template (single entry view)</li>
					<li>Fixed: Conflict with the "The Events Calendar" plugin when saving View Advanced Filter configuration</li>
					<li>Fixed: When editing an entry in the frontend it gets unapproved when not using the approve form field</li>
					<li>Added: Option to convert text URI, www, FTP, and email addresses on a paragraph field in HTML links</li>
					<li>Fixed: Activate/Check License buttons weren't properly visible</li>
					<li>Added: <code>gravityview/field/other_entries/args</code> filter to modify arguments used to generate the Other Entries list. This allows showing other user entries from any View, not just the current view</li>
					<li>Added: <code>gravityview/render/hide-empty-zone</code> filter to hide empty zone. Use <code>__return_true</code> to prevent wrapper <code>&lt;div&gt;</code> from being rendered</li>
					<li>Updated Translations:

						<ul>
							<li>Bengali translation by <a href="https://www.transifex.com/accounts/profile/tareqhi/">@tareqhi</a></li>
							<li>Turkish translation by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a></li>
							<li>Hungarian translation by <a href="https://www.transifex.com/accounts/profile/Darqebus/">@Darqebus</a></li>
						</ul>
					</li>
				</ul>


				<h3>1.7.5 on April 10</h3>

				<ul>
					<li>Added: <code>[gvlogic]</code> Shortcode - allows you to show or hide content based on the value of merge tags in Custom Content fields! <a href="http://docs.gravityview.co/article/252-gvlogic-shortcode">Learn how to use the shortcode</a>.</li>
					<li>Fixed: White Screen error when license key wasn't set and settings weren't migrated (introduced in 1.7.4)</li>
					<li>Fixed: No-Conflict Mode not working (introduced in 1.7.4)</li>
					<li>Fixed: PHP notices when visiting complex URLs</li>
					<li>Fixed: Path to plugin updater file, used by Extensions</li>
					<li>Fixed: Extension global settings layout improved (yet to be implemented)</li>
					<li>Tweak: Restructure plugin file locations</li>
					<li>Updated: Dutch translation by <a href="https://www.transifex.com/accounts/profile/erikvanbeek/">@erikvanbeek</a>. Thanks!</li>
				</ul>

				<h3>Changes in 1.7.4.1</h3>
				<ul>
					<li>* Fixed: Fatal error when attempting to view entry that does not exist (introduced in 1.7.4)</li>
				</ul>

				<h3>Changes in 1.7.4</h3>

				<ul>
					<li>Modified: The List template is now responsive! Looks great on big and small screens.</li>
					<li>Fixed: When editing an entry in the frontend it gets unapproved</li>
					<li>Fixed: Conflicts between the Advanced Filter extension and the Single Entry mode (if using <code>ANY</code> mode for filters)</li>
					<li>Fixed: Sorting by full name. Now sorts by first name by default.

						<ul>
							<li>Added <code>gravityview/sorting/full-name</code> filter to sort by last name (<a href="https://gist.github.com/zackkatz/cd42bee4f361f422824e">see how</a>)</li>
						</ul>
					</li>
					<li>Fixed: Date and Time fields now properly internationalized (using <code>date_i18n</code> instead of <code>date</code>)</li>
					<li>Added: <code>gravityview_disable_change_entry_creator</code> filter to disable the Change Entry Creator functionality</li>
					<li>Modified: Migrated to use Gravity Forms settings</li>
					<li>Modified: Updated limit to 750 users (up from 300) in Change Entry Creator dropdown.</li>
					<li>Confirmed WordPress 4.2 compatibility</li>
				</ul>
				
				<h3>Changes in 1.7.3</h3>

				<ul>
					<li>Fixed: Prevent displaying a single Entry that doesn't match configured Advanced Filters</li>
					<li>Fixed: Embedding entries when not using permalinks</li>
					<li>Fixed: Issue with permalink settings needing to be re-saved after updating GravityView</li>
					<li>Fixed: Hide "Data Source" metabox links in the Screen Options tab in the Admin</li>
					<li>Added: <code>gravityview_has_archive</code> filter to enable View archive (see all Views by going to [sitename.com]/view/)</li>
					<li>Added: Third parameter to <code>GravityView_API::entry_link()</code> method:

						<ul>
							<li><code>$add_directory_args</code> <em>boolean</em> True: Add URL parameters to help return to directory; False: only include args required to get to entry</li>
						</ul>
					</li>
					<li>Tweak: Register <code>entry</code> endpoint even when not using rewrites</li>
					<li>Tweak: Clear <code>GravityView_View-&gt;_current_entry</code> after the View is displayed (fixes issue with Social Sharing Extension, coming soon!)</li>
					<li>Added: Norwegian translation (thanks, <a href="https://www.transifex.com/accounts/profile/aleksanderespegard/">@aleksanderespegard</a>!)</li>
				</ul>

				<h3>Changes in 1.7.2</h3>

				<ul>
					<li>Added: Other Entries field - Show what other entries the entry creator has in the current View</li>
					<li>Added: Ability to hide the Approve/Reject column when viewing Gravity Forms entries (<a href="http://docs.gravityview.co/article/248-how-to-hide-the-approve-reject-entry-column">Learn how</a>)</li>
					<li>Fixed: Missing Row Action links for non-View types (posts, pages)</li>
					<li>Fixed: Embedded DataTable Views with <code>search_value</code> not filtering correctly</li>
					<li>Fixed: Not possible to change View status to 'Publish'</li>
					<li>Fixed: Not able to turn off No-Conflict mode on the Settings page (oh, the irony!)</li>
					<li>Fixed: Allow for non-numeric search fields in <code>gravityview_get_entries()</code></li>
					<li>Fixed: Social icons displaying on GravityView settings page</li>
					<li>Tweak: Improved Javascript &amp; PHP speed and structure</li>
				</ul>

				<h3>Changes in 1.7.1</h3>

				<ul>
					<li>Fixed: Fatal error on the <code>list-body.php</code> template</li>
				</ul>

				<h3>Changes in 1.7</h3>

				<ul>
					<li>Added: You can now edit most Post Fields using GravityView Edit Entry

						<ul>
							<li>Post Content, Post Title, Post Excerpt, Post Tags, Post Category, and most Post Custom Field configurations</li>
						</ul>
					</li>
					<li>Added: Post ID field now available - shows the ID of the post that was created by the Gravity Forms entry</li>
					<li>Fixed: Properly reset <code>$post</code> after Live Post Data is displayed</li>
					<li>Tweak: Display spinning cursor while waiting for View configurations to load</li>
					<li>Tweak: Updated GravityView Form Editor buttons to be 1.9 compatible</li>
					<li>Added: <code>gravityview/field_output/args</code> filter to modify field output settings before rendering</li>
					<li>Added: Sorting Table columns (<a href="http://docs.gravityview.co/article/230-how-to-enable-the-table-column-sorting-feature">read how</a>)</li>
					<li>Fixed: Don't show date field value if set to Unix Epoch (1/1/1970), since this normally means that in fact, no date has been set</li>
					<li>Fixed: PHP notices when choosing "Start Fresh"</li>
					<li>Fixed: If Gravity Forms is installed using a non-standard directory name, GravityView would think it wasn't activated</li>
					<li>Fixed: Fixed single entry links when inserting views with <code>the_gravityview()</code> template tag</li>
					<li>Updated: Portuguese translation (thanks, Luis!)</li>
					<li>Added: <code>gravityview/fields/email/javascript_required</code> filter to modify message displayed when encrypting email addresses and Javascript is disabled</li>
					<li>Added: <code>GFCommon:js_encrypt()</code> method to encrypt text for Javascript email encryption</li>
					<li>Fixed: Recent Entries widget didn't allow externally added settings to save properly</li>
					<li>Tweak: Updated View Presets to have improved Search Bar configurations</li>
					<li>Fixed: `gravityview/get_all_views/params` filter restored (Modify Views returned by the `GVCommon::get_all_views()` method)</li>
					<li>GravityView will soon require Gravity Forms 1.9 or higher. If you are running Gravity Forms Version 1.8.x, please update to the latest version.</li>
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

				<div>
					<h2>Zack Katz</h2>
					<h4 style="font-weight:0; margin-top:0">Project Lead &amp; Developer</h4>
					<p></p>
					<p><img style="float:left; margin: 0 15px 10px 0;" src="<?php echo plugins_url( 'assets/images/zack.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Zack has been developing integrations with Gravity Forms since 2009. He is the President of Katz Web Services and lives with his wife (and cat) in Denver, Colorado.</p>
					<p><a href="https://katz.co">View Zack&rsquo;s website</a></p>
				</div>

				<div>
					<h2>Luis Godinho</h2>
					<h4 style="font-weight:0; margin-top:0">Developer &amp; Support</h4>
					<p><img style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/luis.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Luis is a WordPress developer passionate about WordPress. He is a co-founder and partner of GOMO, a digital agency located in Lisbon, Portugal.</p>
					<p><a href="http://tinygod.pt">View Luis&rsquo;s website</a></p>
				</div>

				<div class="last-feature">
					<h2>Gustavo Bordoni</h2>
					<h4 style="font-weight:0; margin-top:0">Developer &amp; Support</h4>
					<p><img style="margin: 0 15px 10px 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/gustavo.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Gustavo is a WordPress developer and an active WordPress community member. He is a frequent WordCamp speaker and is based in Rio de Janeiro, Brazil.</p>
					<p><a href="http://bordoni.me">View Gustavo&rsquo;s website</a></p>
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
						<li class="wp-person">Italian translation by <a href="https://www.transifex.com/accounts/profile/ClaraDiGennaro/">@ClaraDiGennaro</a></li>
						<li class="wp-person">French translation by <a href="https://www.transifex.com/accounts/profile/franckt/">@franckt</a> and <a href="https://www.transifex.com/accounts/profile/Newbdev/">@Newbdev</a></li>
						<li class="wp-person">Portuguese translation by <a href="https://www.transifex.com/accounts/profile/luistinygod/">@luistinygod</a></li>
						<li class="wp-person">Romanian translation by <a href="https://www.transifex.com/accounts/profile/ArianServ/">@ArianServ</a></li>
						<li class="wp-person">Finnish translation by <a href="https://www.transifex.com/accounts/profile/harjuja/">@harjuja</a></li>
						<li class="wp-person">Spanish translation by <a href="https://www.transifex.com/accounts/profile/jorgepelaez/">@jorgepelaez</a>, <a href="https://www.transifex.com/accounts/profile/luisdiazvenero/">@luisdiazvenero</a>, and <a href="https://www.transifex.com/accounts/profile/josemv/">@josemv</a></li>
						<li class="wp-person">Swedish translation by <a href="https://www.transifex.com/accounts/profile/adamrehal/">@adamrehal</a></li>
						<li class="wp-person">Indonesian translation by <a href="https://www.transifex.com/accounts/profile/sariyanta/">@sariyanta</a></li>
						<li class="wp-person">Norwegian translation by <a href="https://www.transifex.com/accounts/profile/aleksanderespegard/">@aleksanderespegard</a></li>
						<li class="wp-person">Code contributions by <a href="https://github.com/ryanduff">@ryanduff</a></li>
						<li class="wp-person">Code contributions by <a href="https://github.com/dmlinn">@dmlinn</a></li>
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
					<li><a href="http://reduxframework.com">ReduxFramework</a> - a powerful settings library</li>
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
