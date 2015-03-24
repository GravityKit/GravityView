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

				<div class="feature-section col two-col">

					<div class="col-1">
						<img src="<?php echo plugins_url( 'assets/images/screenshots/other-entries.png', GRAVITYVIEW_FILE ); ?>" alt="Configuring the Other Entries field">
						<h4 class="higher">New "Other Entries" Field</h4>
						<p>In Version 1.7.2, you can now list other entries created by the Entry creator.</p>
					</div>

					<div class="col-2 last-feature">
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


				<h3>Changes in 1.6.2</h3>
				<ul>
					<li>Added: Two new hooks in the Custom Content field to enable conditional logic or enable <code>the_content</code> WordPress filter which will trigger the Video embed (<a href="http://docs.gravityview.co/article/227-how-can-i-transform-a-video-link-into-a-player-using-the-custom-content-field">read how</a>)</li>
					<li>Fixed: Issue when embedding multiple DataTables views in the same page</li>
					<li>Tweak: A more robust "Save View" procedure to prevent losing field configuration on certain browsers</li>
				</ul>

				<h3>Changes in 1.6.1</h3>

				<ul>
					<li>Added: Allow Recent Entries to have an Embed Page ID</li>
					<li>Fixed: # of Recent Entries not saving</li>
					<li>Fixed: Link to Embed Entries how-to on the Welcome page</li>
					<li>Fixed: Don't show "Please select View to search" message until Search Widget is saved</li>
					<li>Fixed: Minor Javascript errors for new Search widget</li>
					<li>Fixed: Custom templates loading from the theme directory.</li>
					<li>Fixed: Adding new search fields to the View search bar widget</li>
					<li>Fixed: Entry creators can edit their own entries in Gravity Forms 1.9+</li>
					<li>Fixed: Recent Entries widget will be hidden in the Customizer preview until View ID is configured</li>
					<li>Tweak: Added Floaty icon to Customizer widget selectors</li>
					<li>Updated: Hungarian, Norwegian, Portuguese, Swedish, Turkish, and Spanish translations (thanks to all the translators!)</li>
				</ul>

				<h3>Changes in 1.6</h3>

				<ul>
					<li>Our support site has moved to <a href="http://docs.gravityview.co">docs.gravityview.co</a>. We hope you enjoy the improved experience!</li>
					<li>Added: GravityView Search Widget - Configure a WordPress widget that searches any of your Views. <a href="http://docs.gravityview.co/article/222-the-search-widget">Read how to set it up</a></li>
					<li>Added: Duplicate View functionality allows you to clone a View from the All Views screen. <a href="http://docs.gravityview.co/article/105-how-to-duplicate-or-copy-a-view">Learn more</a></li>
					<li>Added: Recent Entries WordPress Widget - show the latest entries for your View. <a href="http://docs.gravityview.co/article/223-the-recent-entries-widget">Learn more</a></li>
					<li>Added: Embed Single Entries - You can now embed entries in a post or page! <a href="http://docs.gravityview.co/article/105-how-to-duplicate-or-copy-a-view">See how</a></li>
					<li>Fixed: Respect Custom Input Labels added in Gravity Forms 1.9</li>
					<li>Fixed: Edit Entry Admin Bar link</li>
					<li>Fixed: Single Entry links didn't work when previewing a draft View</li>
					<li>Fixed: Edit entry validation hooks not running when form has multiple pages</li>
					<li>Fixed: Annoying bug where you would have to click Add Field / Add Widget buttons twice to open the window</li>
					<li>Added: <code>gravityview_get_link()</code> function to standardize generating HTML anchors</li>
					<li>Added: <code>GravityView_API::entry_link_html()</code> method to generate entry link HTML</li>
					<li>Added: <code>gravityview_field_entry_value_{$field_type}</code> filter to modify the value of a field (in <code>includes/class-api.php</code>)</li>
					<li>Added: <code>field_type</code> key has been added to the field data in the global <code>$gravityview_view-&gt;field_data</code> array</li>
					<li>Added: <code>GravityView_View_Data::maybe_get_view_id()</code> method to determine whether an ID, post content, or object passed to it is a View or contains a View shortcode.</li>
					<li>Added: Hook to customise the text message "You have attempted to view an entry that is not visible or may not exist." - <code>gravityview/render/entry/not_visible</code></li>
					<li>Added: Included in hook <code>gravityview_widget_search_filters</code> the labels for search all, entry date and entry id.</li>
					<li>Tweak: Allow <a href="http://wordpress.org/plugins/wordpress-seo/" rel="external">WordPress SEO</a> scripts and styles when in "No Conflict Mode"</li>
					<li>Fixed: For Post Dynamic Data, make sure Post ID is set</li>
					<li>Fixed: Make sure search field choices are available before displaying field</li>
				</ul>

				<h3>Changes in 1.5.4</h3>

				<ul>
					<li>Added: "Hide View data until search is performed" setting - only show the Search Bar until a search is entered</li>
					<li>Added: "Clear" button to your GravityView Search Bar - allows easy way to remove all searches &amp; filters</li>
					<li>Added: You can now add Custom Content GravityView Widgets (not just fields) - add custom text or HTMLin the header or footer of a View</li>
					<li>Added: <code>gravityview/comments_open</code> filter to modify whether comments are open or closed for GravityView posts (previously always false)</li>
					<li>Added: Hook to filter the success Edit Entry message and link <code>gravityview/edit_entry/success</code></li>
					<li>Added: Possibility to add custom CSS classes to multiple view widget wrapper (<a href="https://gravityview.co/support/documentation/204144575/">Read how</a>)</li>
					<li>Added: Field option to enable Live Post Data for Post Image field</li>
					<li>Fixed: Loading translation files for Extensions</li>
					<li>Fixed: Edit entry when embedding multiple views for the same form in the same page</li>
					<li>Fixed: Conflicts with Advanced Filter extension when embedding multiple views for the same form in the same page</li>
					<li>Fixed: Go Back link on embedded single entry view was linking to direct view url instead of page permalink</li>
					<li>Fixed: Searches with quotes now work properly</li>
					<li>Tweak: Moved <code>includes/css/</code>, <code>includes/js/</code> and <code>/images/</code> folders into <code>/assets/</code></li>
					<li>Tweak: Improved the display of the changelog (yes, "this is <em>so</em> meta!")</li>
					<li>Updated: Swedish translation - thanks, <a href="https://www.transifex.com/accounts/profile/adamrehal/">@adamrehal</a></li>
					<li>Updated: Hungarian translation - thanks, <a href="https://www.transifex.com/accounts/profile/Darqebus/">@Darqebus</a> (a new translator!) and <a href="https://www.transifex.com/accounts/profile/dbalage/">@dbalage</a></li>
				</ul>


				<h3>Changes in 1.5.3</h3>

				<ul>
					<li>Fixed: When adding more than 100 fields to the View some fields weren't saved.</li>
					<li>Fixed: Do not set class tickbox for non-images files</li>
					<li>Fixed: Display label "Is Fulfilled" on the search bar</li>
					<li>Tested with Gravity Forms 1.9beta5 and WordPress 4.1</li>
					<li>Fixed: PHP Notice with Gravity Forms 1.9 and PHP 5.4+</li>
					<li>Updated: Turkish translation by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a> and Hungarian translation by <a href="https://www.transifex.com/accounts/profile/dbalage/">@dbalage</a>. Thanks!</li>
				</ul>


				<h3>Changes in 1.5.2</h3>

				<ul>
					<li>Added: Possibility to show the label of Dropdown field types instead of the value (<a href="https://gravityview.co/support/documentation/202889199/" title="How to display the text label (not the value) of a dropdown field?">learn more</a>)</li>
					<li>Fixed: Sorting numeric columns (field type number)</li>
					<li>Fixed: View entries filter for Featured Entries extension</li>
					<li>Fixed: Field options showing delete entry label</li>
					<li>Fixed: PHP date formatting now keeps backslashes from being stripped</li>
					<li>Modified: Allow license to be defined in <code>wp-config.php</code> (<a href="https://gravityview.co/support/documentation/202870789/">Read how here</a>)</li>
					<li>Modified: Added <code>$post_id</code> parameter as the second argument for the <code>gv_entry_link()</code> function. This is used to define the entry's parent post ID.</li>
					<li>Modified: Moved <code>GravityView_API::get_entry_id_from_slug()</code> to <code>GVCommon::get_entry_id_from_slug()</code></li>
					<li>Modified: Added second parameter to <code>gravityview_get_entry()</code>, which forces the ability to fetch an entry by ID, even if custom slugs are enabled and <code>gravityview_custom_entry_slug_allow_id</code> is false.</li>
					<li>Updated Translations:
						<ul>
							<li>Bengali translation by <a href="https://www.transifex.com/accounts/profile/tareqhi/">@tareqhi</a></li>
							<li>Romanian translation by <a href="https://www.transifex.com/accounts/profile/ArianServ/">@ArianServ</a></li>
							<li>Mexican Spanish translation by <a href="https://www.transifex.com/accounts/profile/jorgepelaez/">@jorgepelaez</a></li>
						</ul>
					</li>
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

			<div class="feature-section col two-col">

				<div>
					<h2>Zack Katz</h2>
					<h4 style="font-weight:0; margin-top:0">Project Lead &amp; Developer</h4>
					<p></p>
					<p><img style="float:left; margin: 0 15px 0 0;" src="<?php echo plugins_url( 'assets/images/zack.png', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Zack has been developing integrations with Gravity Forms since 2009. He is the President of Katz Web Services and lives with his wife and cat in Denver, Colorado.</p>
					<p><a href="https://katz.co">View Zack&rsquo;s website</a></p>
				</div>

				<div class="last-feature">
					<h2>Luis Godinho</h2>
					<h4 style="font-weight:0; margin-top:0">Developer &amp; Support</h4>
					<p><img style="margin: 0 15px 0 0;"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/luis.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Luis is a WordPress developer passionate about WordPress. He is a co-founder and partner of GOMO, a digital agency located in Lisbon, Portugal.</p>
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

						<!-- No translation strings yet... -->
						<!-- <li class="wp-person">Greek translation by <a href="https://www.transifex.com/accounts/profile/asteri/">@asteri</a></li> -->
						<!-- <li class="wp-person">Russian translation by <a href="https://www.transifex.com/accounts/profile/badsmiley/">@badsmiley</a></li> -->

					</ul>

					<h4><?php esc_attr_e( 'Want to contribute?', 'gravityview' ); ?></h4>
					<p><?php echo sprintf( esc_attr__( 'If you want to contribute to the code, you can %srequest access to the Github repository%s. If your contributions are accepted, you will be thanked here.', 'gravityview'), '<a href="mailto:zack@katzwebservices.com?subject=Github%20Access">', '</a>' ); ?></p>
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
