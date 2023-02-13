<?php
/**
 * Welcome Page Class
 *
 * @package   GravityView
 * @author    Zack Katz <zack@gravityview.co>
 * @link      https://www.gravitykit.com
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
		add_action( 'gk/foundation/initialized', array( $this, 'admin_menus' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome'    ) );
		add_filter( 'gravityview_is_admin_page', array( $this, 'is_dashboard_page'), 10, 2 );
	}

	/**
	 * Register the Dashboard Pages which are later hidden but these pages
	 * are used to render the Welcome pages.
	 *
	 * @since 1.0
	 *
	 * @return void
	 *
	 * @param \GravityKit\GravityView\Foundation\Core|GravityKitFoundation $foundation
	 */
	public function admin_menus( $foundation ) {
		if ( $foundation::helpers()->core->is_network_admin() ) {
			return;
		}

		/** @var \GravityKit\GravityView\Foundation\WP\AdminMenu $admin_menu */
		$admin_menu = $foundation::admin_menu();

		// Changelog Page
		$admin_menu::add_submenu_item( [
			'id'         => 'gv-changelog',
			'page_title' => __( 'Changelog', 'gk-gravityview' ),
			'menu_title' => __( 'Changelog', 'gk-gravityview' ),
			'capability' => $this->minimum_capability,
			'callback'   => array( $this, 'changelog_screen' ),
			'order'      => 40,
		], 'center' );

		// Changelog Page
		$admin_menu::add_submenu_item( [
			'id'         => 'gv-credits',
			'page_title' => __( 'Credits', 'gk-gravityview' ),
			'menu_title' => __( 'Credits', 'gk-gravityview' ),
			'capability' => $this->minimum_capability,
			'callback'   => array( $this, 'credits_screen' ),
			'order'      => 50,
		], 'center' );

		// Add Getting Started page to GravityView menu
		$admin_menu::add_submenu_item( [
			'id'         => 'gv-getting-started',
			'page_title' => __( 'GravityView: Getting Started', 'gk-gravityview' ),
			'menu_title' => __( 'Getting Started', 'gk-gravityview' ),
			'capability' => $this->minimum_capability,
			'callback'   => array( $this, 'getting_started_screen' ),
			'order'      => 60, // Make it the last so that the border divider remains
		], 'center' );
	}

	/**
	 * Is this page a GV dashboard page?
	 *
	 * @return boolean  $is_page   True: yep; false: nope
	 */
	public function is_dashboard_page( $is_page = false, $hook = NULL ) {
		global $pagenow;

		if ( empty( $_GET['page'] ) ) {
			return $is_page;
		}

		if ( ! $pagenow ) {
			return $is_page;
		}

		return 'admin.php' === $pagenow && in_array( $_GET['page'], array( 'gv-changelog', 'gv-credits', 'gv-getting-started' ), true );
	}

	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @since 1.0
	 * @return void
	 */
	public function admin_head() {

		/** @var \GravityKit\GravityView\Foundation\WP\AdminMenu $admin_menu */
		$admin_menu = GravityKitFoundation::admin_menu();

		$admin_menu::remove_submenu_item( 'gv-credits' );
		$admin_menu::remove_submenu_item( 'gv-changelog' );

		if( ! $this->is_dashboard_page() ) {
			return;
		}

		?>
		<style>
		.update-nag { display: none; }
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
		list( $display_version ) = explode( '-', GV_PLUGIN_VERSION );

		$selected = !empty( $plugin_page ) ? $plugin_page : 'gv-getting-started';

		echo gravityview_get_floaty( 132 );
		?>

		<h1><?php printf( esc_html__( 'Welcome to GravityView %s', 'gk-gravityview' ), $display_version ); ?></h1>
		<div class="about-text"><?php esc_html_e( 'Thank you for installing GravityView. Beautifully display your Gravity Forms entries.', 'gk-gravityview' ); ?></div>

		<h2 class="nav-tab-wrapper clear">
			<a class="nav-tab <?php echo $selected == 'gv-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-getting-started' ), 'admin.php' ) ) ); ?>">
				<?php esc_html_e( "Getting Started", 'gk-gravityview' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'gv-changelog' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-changelog' ), 'admin.php' ) ) ); ?>">
				<?php esc_html_e( "List of Changes", 'gk-gravityview' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'gv-credits' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-credits' ), 'admin.php' ) ) ); ?>">
				<?php esc_html_e( 'Credits', 'gk-gravityview' ); ?>
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

				<p style="text-align:center; padding-top: 1em;"><a class="button button-primary button-hero" href="https://docs.gravityview.co/category/24-category" rel="noopener noreferrer external" target="_blank">Read more: Setting Up Your First View<span class='screen-reader-text'> <?php esc_attr_e( 'This link opens in a new window.', 'gk-gravityview' ); ?></span></a></p>
			</div>

			<div class="feature-section two-col has-2-columns is-fullwidth">
				<div class="col column">
					<h3>Create a View</h3>

					<ol class="ol-decimal">
						<li>Go to the GravityKit menu and click on <a href="<?php echo admin_url('post-new.php?post_type=gravityview'); ?>">New View</a></li>
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
					<h3>Embed Views in the Block Editor</h3>
					<p>Embed Views using the "Add Shortcode" button above your content editor. <a href="https://docs.gravityview.co/article/73-using-the-shortcode">Learn how to use the <code>[gravityview]</code> shortcode.</a></p>
				</div>
				<div class="col column">
					<img src="<?php echo plugins_url( 'assets/images/screenshots/shortcode-block.png', GRAVITYVIEW_FILE ); ?>" alt="Screenshot of the Shortcode block" />
				</div>
			</div>

			<div class="feature-section two-col has-2-columns is-fullwidth">
				<div class="col column">
					<h3>Embed Views in Classic Editor</h3>
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

					<p>You can configure what fields are displayed in <strong>Multiple Entry</strong>, <strong>Single Entry</strong>, and <strong>Edit Entry</strong> modes. These can be configured by clicking on the three associated tabs when editing a View.</p>

					<ul class="ul-disc">
						<li>Click "+ Add Field" to add a field to a zone</li>
						<li>Click the name of the field you want to display</li>
						<li>Once added, fields can be dragged and dropped to be re-arranged. Hover over the field until you see a cursor with four arrows, then drag the field.</li>
						<li>Click the <i class="dashicons dashicons-admin-generic"></i> gear icon on each field to configure the <strong>Field Settings</strong></li>
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

			<div class="changelog point-releases" style="margin-top: 3em; border-bottom: 0">

				<div class="headline-feature" style="max-width: 100%">
					<h2 style="border-bottom: 1px solid #ccc; padding-bottom: 1em; margin-bottom: 0; margin-top: 0"><?php esc_html_e( 'What&rsquo;s New', 'gk-gravityview' ); ?></h2>
				</div>

				<p>2.17 on February 13, 2023</p>

				<p><strong>Note: GravityView now requires PHP 7.2 or newer</strong></p>

				<ul>
					<li>It's faster than ever to create a new View! (Table and DataTables View types only)
						<ul>
							<li>Fields configured in the <a href="https://docs.gravityforms.com/entries/#h-entry-columns">Gravity Forms Entry Columns</a> are added to the Multiple Entries layout</li>
							<li>The first field in the Multiple Entries layout is linked to the Single Entry layout</li>
							<li>All form fields are added to the Single Entry layout</li>
							<li>An Edit Entry Link field is added to the bottom of the Single Entry layout</li>
						</ul></li>
					<li>Added: New "No Entries Behavior" setting: when a View has no entries visible to the current user, you can now choose to display a message, show a Gravity Forms form, or redirect to a URL</li>
					<li>Modified: The field picker now uses Gravity Forms field icons</li>
					<li>Fixed: <a href="https://docs.gravitykit.com/article/701-show-choices-that-exist">"Pre-filter choices"</a> Search Bar setting not working for Address fields</li>
					<li>Fixed: <code>[gventry]</code> shortcode not working the Entry ID is set to "first" or "last"</li>
					<li>Fixed: Fatal error when using the Gravity Forms Survey Add-On</li>
					<li>Tweak: The field picker in the View editor now uses Gravity Forms field icons</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Modified: If you use the <code>gravityview/template/text/no_entries</code> or <code>gravitview_no_entries_text</code> filters, the output is now passed through the <code>wpautop()</code> function prior to applying the filters, not after
						<ul>
							<li>Added <code>$unformatted_output</code> parameter to the <code>gravityview/template/text/no_entries</code> filter to return the original value before being passed through <code>wpautop()</code></li>
						</ul></li>
					<li>Modified: Container classes for no results output change based on the "No Entries Behavior" setting:
						<ul>
							<li><code>.gv-no-results.gv-no-results-text</code> when set to "Show a Message"</li>
							<li><code>.gv-no-results.gv-no-results-form</code> when set to "Display a Form"</li>
							<li>Updated <code>templates/views/list/list-body.php</code>, <code>templates/views/table/table-body.php</code></li>
						</ul></li>
					<li>Added: <code>$form_id</code> parameter to <code>gravityview_get_directory_fields()</code> function and <code>GVCommon::get_directory_fields()</code> method</li>
				</ul>

				<p>2.16.6 on January 12, 2023</p>

				<ul>
					<li>Fixed: Fatal error due to an uncaught PHP exception</li>
					<li>Fixed: It was not possible to select any content inside the field settings window in the View editor</li>
				</ul>

				<p>2.16.5 on January 5, 2023</p>

				<ul>
					<li>Updated: <a href="https://www.gravitykit.com/foundation/">Foundation</a> to version 1.0.8</li>
					<li>Improved: Internal changes to allow using Custom Content fields on the Edit Screen with the <a href="https://www.gravitykit.com/extensions/diy-layout/">DIY Layout</a></li>
				</ul>

				<h3>2.16.4 on December 23, 2022</h3>

				<ul>
					<li>Fixed: Prevent possible conflict in the View editor with themes/plugins that use Bootstrap's tooltip library</li>
				</ul>

				<h3>2.16.3 on December 21, 2022</h3>

				<ul>
					<li>Fixed: Caching wouldn't always clear when an entry was added or modified</li>
					<li>Fixed: Fatal error on some hosts due to a conflict with one of the plugin dependencies (psr/log)</li>
					<li>Fixed: PHP 8.1 notices</li>
					<li>Fixed: View scripts and styles not loading for some logged-in users</li>
				</ul>

				<h3>2.16.2 on December 14, 2022</h3>

				<ul>
					<li>Fixed: Views would take an abnormally long time to load</li>
					<li>Fixed: Fatal error on some hosts that use weak security keys and salts</li>
				</ul>

				<h3>2.16.1 on December 7, 2022</h3>

				<ul>
					<li>Fixed: Date picker and other JavaScript not working on the Edit Entry screen</li>
					<li>Fixed: JavaScript error preventing the Search Bar widget properties from opening when creating a new View</li>
					<li>Fixed: CodeMirror editor initializing multiple times when opening the custom content field properties in the View</li>
					<li>Fixed: Secure download link for the file upload field was not showing the file name as the link text</li>
					<li>Fixed: The saved View would not recognize fields added from a joined form when using the <a href="https://www.gravitykit.com/extensions/multiple-forms/">Multiple Forms</a> extension</li>
				</ul>

				<h3>2.16.0.4 on December 2, 2022</h3>

				<ul>
					<li>Fixed: Incompatibility with some plugins/themes that could result in a blank WordPress Dashboard</li>
				</ul>

				<h3>2.16.0.3 on December 2, 2022</h3>

				<ul>
					<li>Fixed: Fatal error when downloading plugin translations</li>
				</ul>

				<h3>2.16.0.2 on December 1, 2022</h3>

				<ul>
					<li>Fixed: Fatal error when Maps isn't installed</li>
				</ul>

				<h3>2.16.0.1 on December 1, 2022</h3>

				<ul>
					<li>Fixed: Admin menu not expanded when on a GravityView page</li>
				</ul>

				<h3>2.16 on December 1, 2022</h3>

				<ul>
					<li>Added: New WordPress admin menu where you can now centrally manage all your GravityKit product
						licenses and settings (<a href='https://www.gravitykit.com/foundation/'>learn more about the new
							GravityKit menu</a>)
						<ul>
							<li>Go to the WordPress sidebar and check out the GravityKit menu!</li>
							<li>We have automatically migrated your existing licenses and settings, which were
								previously entered in the Views→Settings page
							</li>
							<li>Request support using the 'Grant Support Access' menu item</li>
						</ul>
					</li>
					<li>Added: Support for defining <code>alt</code> text in File Upload fields</li>
					<li>Added: 'Pre-Filter Choices' Search Bar setting will only display choices that exist in submitted
						entries (<a href='https://docs.gravitykit.com/article/701-s'>learn more about Pre-Filter
							Choices</a>)
					</li>
					<li>Improved: When creating a new View, it is now possible to install a View type (if included in
						the license) straight from the View editor
					</li>
					<li>Improved: Reduce the number of queries when displaying a View</li>
					<li>Improved: The Edit View screen loads faster</li>
					<li>Fixed: Merge Tags were not processed inside Custom Content fields when using the <a
								href='https://docs.gravitykit.com/article/463-gventry-shortcode'><code>[gventry]</code>
							edit mode</a></li>
					<li>Fixed: Gravity Forms poll results was not being refreshed after editing a Poll field in
						GravityView Edit Entry
					</li>
					<li>Fixed: Survey field 'Rating' stars were not displaying properly in the frontend</li>
					<li>Fixed: JavaScript error when creating a new View</li>
					<li>Fixed: JavaScript error when opening field settings in a new View</li>
					<li>Fixed: Merge Tag picker not initializing when changing View type for an existing View</li>
					<li>Fixed: 'Field connected to XYZ field was deleted from the form' notice when adding a new field
						to a View created from a form preset
					</li>
					<li>Fixed: Edit Entry may partially save changes if form fields have conditional logic; thanks,
						Jurriaan!
					</li>
					<li>Fixed: View presets not working</li>
					<li>Fixed: 'This View is configured using the View type, which is disabled' notice when creating a
						new View after activating or installing a View type (e.g., Maps, DIY, DataTables)
					</li>
					<li>Fixed: Incorrect search mode is set when one of the View search widget fields uses a 'date
						range' input type
					</li>
					<li>Fixed: Multiple files upload error (e.g., when editing an entry using GravityEdit)</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added: <code>gravityview/template/field/survey/rating/before</code> filter that fires before the
						Survey field rating stars markup
					</li>
					<li>Added: <code>$return_view</code> parameter to <code>\GV\Request::is_view()</code> method,
						reducing the need to build a \GV\View object when simply checking if a request is a View
					</li>
					<li>Added: <code>$expiration</code> parameter to <code>GravityView_Cache::set()</code> method to
						allow for different cache lifetimes
					</li>
					<li>Fixed: <code>GravityView_Cache</code> was not used when the <code>WP_DEBUG</code> constant was
						set to <code>true</code>. This resulted in the cache being effectively disabled on many sites.
						<ul>
							<li>Improved: Only run <code>GravityView_Cache::use_cache()</code> once per request</li>
							<li>Added: <code>GRAVITYVIEW_DISABLE_CACHE</code> constant to disable the cache. Note:
								<code>gravityview_use_cache</code> filter will still be run.
							</li>
						</ul>
					</li>
				</ul>


				<h3>2.15 on September 21, 2022</h3>

				<ul>
					<li>Added: Entire View contents are wrapped in a container, allowing for better styling (<a href='https://docs.gravitykit.com/article/867-modifying-the-view-container-div'>learn about, and how to modify, the container</a>)</li>
					<li>Added: When submitting a search form, the page will scroll to the search form</li>
					<li>Modified: Select and Multiselect search inputs will now use the connected field's "Placeholder" values, if defined in Gravity Forms (<a href="https://docs.gravitykit.com/article/866-search-bar-placeholder">read about Search Bar placeholders</a>)</li>
					<li>Improved: Date comparisons when using <code>[gvlogic]</code> with <code>greater_than</code> or <code>less_than</code> comparisons</li>
					<li>Fixed: Reduced the number of database queries to render a View, especially when using Custom Content, Entry Link, Edit Link, and Delete Link fields</li>
					<li>Fixed: Removed the Gravity Forms Partial Entries Add-On privacy notice when using Edit Entry because auto-saving in Edit Entry is not supported</li>
					<li>Fixed: The "entry approval is changed" notification, if configured, was being sent for new form submissions</li>
					<li>Fixed: Views would not render in PHP 8.1</li>
					<li>Fixed: Multiple PHP 8 and PHP 8.1 warnings</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added: <code>gravityview/widget/search/append_view_id_anchor</code> filter to control appending the unique View anchor ID to the search URL (enabled by default)</li>
					<li>Added: <code>gravityview/view/wrapper_container</code> filter to wrap to optionally wrap the View in a container (enabled by default) — <a href="https://docs.gravitykit.com/article/867-modifying-the-view-container-div">see examples of modifying the container</a></li>
					<li>Added: <code>gravityview/view/anchor_id</code> filter to control the unique View anchor ID</li>
					<li>Modified the following template files:
						<ul>
							<li><code>includes/widgets/search-widget/templates/search-field-multiselect.php</code></li>
							<li><code>includes/widgets/search-widget/templates/search-field-select.php</code></li>
							<li><code>templates/views/list.php</code></li>
							<li><code>templates/views/table.php</code></li>
							<li><code>templates/fields/field-custom.php</code></li>
							<li><code>templates/fields/field-duplicate_link-html.php</code></li>
							<li><code>templates/fields/field-delete_link-html.php</code></li>
							<li><code>templates/fields/field-edit_link-html.php</code></li>
							<li><code>templates/fields/field-entry_link-html.php</code></li>
							<li><code>templates/fields/field-website-html.php</code></li>
							<li><code>templates/deprecated/fields/custom.php</code></li>
							<li><code>templates/deprecated/fields/website.php</code></li>
						</ul>
					</li>
				</ul>

				<h3>2.14.7 on July 31, 2022</h3>

				<ul>
					<li>Fixed: GravityView plugin updates were not shown in the plugin update screen since version 2.14.4 (April 27, 2022)</li>
				</ul>

				<h3>2.14.6 on May 27, 2022</h3>

				<ul>
					<li><a href='https://www.gravitykit.com/rebrand/'>GravityView (the company) is now GravityKit!</a>
					</li>
					<li>Fixed: Embedding Edit Entry context directly in a page/post using the <code>[gventry
							edit='1']</code> shortcode (<a
								href='https://docs.gravitykit.com/article/463-gventry-shortcode'>learn more</a>)
					</li>
					<li>Fixed: Edit Entry link wasn't working in the Single Entry context of an embedded View</li>
					<li>Fixed: Search Bar GravityView widget was not saving the chosen fields</li>
					<li>Fixed: Gravity PDF shortcodes would not be processed when bulk-approving entries using
						GravityView. Thanks, Jake!
					</li>
					<li>Fixed: Sometimes embedding a GravityView shortcode in the block editor could cause a fatal
						error
					</li>
					<li>Fixed: Multiple PHP 8 warnings</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added: <code>redirect_url</code> parameter to the <code>gravityview/edit_entry/success</code>
						filter
					</li>
					<li>Added <code>redirect_url</code> and <code>back_link</code> parameters to the <code>gravityview/shortcodes/gventry/edit/success</code>
						filter
					</li>
				</ul>

				<h3>2.14.5 on May 4, 2022</h3>

				<ul>
					<li>Added: A link that allows administrators to disable the "Show only approved entries" View setting from the front-end</li>
					<li>Fixed: Configuring new Search Bar WordPress widgets wasn't working in WordPress 5.8+</li>
					<li>Fixed: Styling of form settings dropdowns on the Gravity Forms "Forms" page</li>
				</ul>

				<h3>2.14.4 on April 27, 2022</h3>

				<ul>
					<li>Added: Search Bar support for the <a
								href='https://www.gravityforms.com/add-ons/chained-selects/'>Chained Selects</a> field
						type
					</li>
					<li>Improved: Plugin updater script now supports auto-updates and better supports multisite
						installations
					</li>
					<li>Improved: If a View does not support joined forms, log as a notice, not an error</li>
					<li>Fixed: Merge Tag picker behavior when using Gravity Forms 2.6</li>
					<li>Fixed: Deleting a file when editing an entry as a non-administrator user on Gravity Forms 2.6.1
						results in a server error
					</li>
					<li>Fixed: When The Events Calendar Pro plugin is active, Views became un-editable</li>
					<li>Tweak: Additional translation strings related to View editing</li>
				</ul>

				<p>Note: We will be requiring Gravity Forms 2.5 and WordPress 5.3 in the near future; please upgrade!</p>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added: Search URLs now support <code>input_{field ID}</code> formats as well as <code>filter_{field
							ID}</code>; the following will both be treated the same:
						<ul>
							<li><code>/view/example/?filter_3=SEARCH</code></li>
							<li><code>/view/example/?input_3=SEARCH</code></li>
						</ul>
					</li>
					<li>Added: In the admin, CSS classes are now added to the <code>body</code> tag based on Gravity
						Forms version. See <code>GravityView_Admin_Views::add_gf_version_css_class()</code></li>
					<li>Modified: Allow non-admin users with 'edit entry' permissions to delete uploaded files</li>
					<li>Updated: EDD<em>SL</em>Plugin_Updater script to version 1.9.1</li>
				</ul>

				<h3>2.14.3 on March 24, 2022</h3>

				<ul>
					<li>Added: Support for displaying WebP images</li>
					<li>Improved: Internal logging of notices and errors</li>
					<li>Fixed: Images hosted on Dropbox sometimes would not display properly on the Safari browser. Thanks, Kevin M. Dean!</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Added: <code>GravityView_Image::get_image_extensions()</code> static method to fetch full list of extension types interpreted as images by GravityView.</li>
					<li>Added: <code>webp</code> as a valid image extension</li>
				</ul>

				<h3>2.14.2.1 on March 11, 2022</h3>

				<ul>
					<li>Fixed: Empty values in search widget fields may return incorrect results</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>
						Added: <code>gravityview/search/ignore-empty-values</code> filter to control strict matching of empty field values
					</li>

				</ul>

				<h3>2.14.2 on March 10, 2022</h3>

				<ul>
					<li>Fixed: Potential fatal error on PHP 8 when exporting View entries in CSV and TSV formats</li>
					<li>Fixed: Search widget would cause a fatal error when the Number field is used with the "is" operator</li>
					<li>Fixed: Search widget returning incorrect results when a field value is blank and the operator is set to "is"</li>
					<li>Fixed: Gravity Forms widget icon not showing</li>
					<li>Fixed: Gravity Forms widget not displaying available forms when the View is saved</li>
				</ul>

				<h3>2.14.1 on January 25, 2022</h3>

				<ul>
					<li>Tested with WordPress 5.9</li>
					<li>Improved: The <a href='https://wordpress.org/plugins/members/'>Members plugin</a> now works with
						No-Conflict Mode enabled
					</li>
					<li>Improved: Performance when saving Views with many fields</li>
					<li>Improved: Performance when loading the Edit View screen when a View has many fields</li>
					<li>Fixed: Gravity Forms widget used in the View editor would initialize on all admin pages</li>
					<li>Fixed: PHP notice when editing an entry in Gravity Forms that was created by user that no longer
						exists
					</li>
					<li>Fixed: Error activating on sites that use the Danish language</li>
					<li>Fixed: Entry approval scripts not loading properly when using Full Site Editing themes in
						WordPress 5.9
					</li>
					<li>Updated: TrustedLogin client to Version 1.2, which now supports logins for WordPress Multisite
						installations
					</li>
					<li>Updated: Polish translation. Thanks, Dariusz!</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<ul>
					<li>Modified: Refactored drag &amp; drop in the View editor to improve performance: we only
						initialize drag &amp; drop on the active tab instead of globally.
						<ul>
							<li>Added: <code>gravityview/tab-ready</code> jQuery trigger to <code>body</code> when each
								GravityView tab is ready (drag &amp; drop initialized). <a
										href='https://gist.github.com/zackkatz/a2844e9f6b68879e79ba7d6f66ba0850'>See
									example of binding to this event</a>.
							</li>
						</ul>
					</li>
				</ul>

				<h3>2.14 on December 21, 2021</h3>

				<p>This would be a minor version update (2.13.5), except that we renamed many functions. See 'Developer
					Updates' for this release below.</p>

				<ul>
					<li>Added: <code>{is_starred}</code> Merge Tag. <a
								href='https://docs.gravityview.co/article/820-the-isstarred-merge-tag'>Learn more about
							using <code>{is_starred}</code></a></li>
					<li>Fixed: Media files uploaded to Dropbox were not properly embedded</li>
					<li>Fixed: JavaScript error when trying to edit entry's creator</li>
					<li>Fixed: Recent Entries widget would cause a fatal error on WP 5.8 or newer</li>
					<li>Fixed: When using Multiple Forms, editing an entry in a joined form now works properly if the
						"Edit Entry" tab has not been configured
					</li>
					<li>Fixed: View settings not hiding automatically on page load</li>
				</ul>

				<p><strong>Developer Updates:</strong></p>

				<p>We renamed all instances of <code>blacklist</code> to <code>blocklist</code> and
					<code>whitelist</code> to <code>allowlist</code>. All methods and filters have been deprecated using
					<code>apply_filters_deprecated()</code> and <code>_deprecated_function()</code>. <a
							href="https://docs.gravityview.co/article/816-renamed-filters-methods-in-2-14">See a
						complete list of modified methods and filters</a>.</p>

				<p style="text-align: center;">
					<a href="https://www.gravitykit.com/changelog/" class="aligncenter button button-primary button-hero" style="margin: 0 auto; display: inline-block; text-transform: capitalize"><?php esc_html_e( 'View change history', 'gk-gravityview' ); ?></a>
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
				.col h4 {
					font-weight: 400;
					margin-top: 0;
				}
				.cols .col p img {
					float: left;
					margin: 0 15px 10px 0;
					max-width: 200px;
					border-radius: 20px;
				}
			</style>

			<h2><?php _e( 'GravityView is brought to you by:', 'gk-gravityview' ); ?></h2>

			<div class="cols">

				<div class="col">
					<h3>Zack Katz <a href="https://twitter.com/zackkatz"><span class="dashicons dashicons-twitter" title="Follow Zack on Twitter"></span></a> <a href="https://katz.co" title="View Zack&rsquo;s website"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4>Project Lead &amp; Developer</h4>
					<p><img alt="Zack Katz" src="<?php echo plugins_url( 'assets/images/team/Zack.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Zack has been developing WordPress plugins since 2008 and has been a huge Gravity Forms fan from the start. Zack is co-owner of GravityKit and he lives with his wife in Leverett, Massachusetts. He can&rsquo;t wait for the next episode of <a href="https://atp.fm">ATP</a> or <a href="https://www.flophousepodcast.com">The Flop House</a> podcasts.</p>
				</div>

				<div class="col">
					<h3>Rafael Ehlers <a href="https://twitter.com/rafaehlers" title="Follow Rafael on Twitter"><span class="dashicons dashicons-twitter"></span></a> <a href="https://heropress.com/essays/journey-resilience/" title="View Rafael&rsquo;s WordPress Journey"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4>Project Manager, Support Lead &amp; Customer&nbsp;Advocate</h4>
					<p><img alt="Rafael Ehlers"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/team/Ehlers.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Rafael helps guide GravityKit development priorities and keep us on track. He&rsquo;s the face of our customer support and helps customers get the most out of the product. Rafael hails from <a href="https://wikipedia.org/wiki/Porto_Alegre">Porto Alegre, Brazil</a>.</p>
				</div>

				<div class="col">
					<h3>Vlad K.</h3>
					<h4>Core Developer</h4>
					<p><img alt="Vlad K."  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/team/Vlad.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Vlad is GravityKit&rsquo;s lead developer. He focuses on GravityKit&rsquo;s user-facing code in the Dashboard and front end. Vlad comes from Russia and lives in Canada.</p>
				</div>

				<div class="col">
					<h3>Rafael Bennemann <a href="https://twitter.com/rafaelbe" title="Follow Rafael on Twitter"><span class="dashicons dashicons-twitter"></span></a></h3>
					<h4>Support Specialist</h4>
					<p><img alt="Rafael Bennemann"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/team/Bennemann.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" />Rafael dedicated most of his adult life to helping people and companies take their ideas to the web, first as a developer and now as a Customer Advocate at GravityKit. He will do his best to help you too, all the while sipping a <a href="https://en.wikipedia.org/wiki/Spritz_Veneziano">Spritz Veneziano</a> in Northern Italy, where he currently lives with his family.</p>
				</div>

				<div class='col'>
					<h3>Casey Burridge</h3>
					<h4 style='font-weight:0; margin-top:0'>Content Creator</h4>
					<p><img alt="Casey Burridge" class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/team/Casey.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94"/>Casey is GravityKit&rsquo;s resident content creator. He&rsquo;s been a WordPress lover ever since launching his first blog more than 6 years ago. Casey has lived and worked in London and Beijing, but feels most at home in Cape Town, South Africa, where he&rsquo;s originally from.</p>
				</div>
			</div>

			<hr class="clear" />

			<div class="feature-section">
				<h2><?php esc_attr_e( 'Contributors', 'gk-gravityview' ); ?></h2>

				<h4>Development</h4>
				<ul class="ul-disc">
					<li>Core &amp; Add-On development by <a href='https://mrcasual.com' class='block'>Vlad K.</a>, <a href='https://katz.co' class='block'>Zack Katz</a>, <a href="https://codeseekah.com" class="block">Gennady Kovshenin</a>, <a href='https://tinygod.pt' class='block'>Luis Godinho</a></li>
					<li>Code contributions by <a href="https://github.com/ryanduff">@ryanduff</a>, <a href="https://github.com/dmlinn">@dmlinn</a>, <a href="https://github.com/mgratch">@mgratch</a>, <a href="https://github.com/ViewFromTheBox">@ViewFromTheBox</a>, <a href="https://github.com/stevehenty">@stevehenty</a>, <a href="https://github.com/naomicbush">@naomicbush</a>, <a href='https://github.com/mrcasual'>@mrcasual</a> and <a href="https://github.com/rafaehlers">@rafaehlers</a></li>
					<li>Accessibility contributions by <a href="https://github.com/RianRietveld">@RianRietveld</a></li>
				</ul>

				<h4>Translations</h4>
				<ul class="ul-disc">
					<li>Bengali translation by <a href="https://www.transifex.com/accounts/profile/tareqhi/">@tareqhi</a></li>
					<li>German translation by <a href="https://www.transifex.com/user/profile/hubert123456/">@hubert123456</a>, <a href="https://www.transifex.com/accounts/profile/seschwarz/">@seschwarz</a>, <a href="https://www.transifex.com/accounts/profile/abdmc/">@abdmc</a>, <a href="https://www.transifex.com/accounts/profile/deckerweb/">@deckerweb</a></li>
					<li>Turkish translation by <a href="https://www.transifex.com/accounts/profile/suhakaralar/">@suhakaralar</a></li>
					<li>Dutch translation by <a href="https://www.transifex.com/accounts/profile/leooosterloo/">@leooosterloo</a>, <a href="https://www.transifex.com/accounts/profile/Weergeven/">@Weergeven</a>, and <a href="https://www.transifex.com/accounts/profile/erikvanbeek/">@erikvanbeek</a>, and <a href="https://www.transifex.com/user/profile/SilverXp/">Thom (@SilverXp)</a></li>
					<li>Hungarian translation by <a href="https://www.transifex.com/accounts/profile/dbalage/">@dbalage</a> and <a href="https://www.transifex.com/accounts/profile/Darqebus/">@Darqebus</a></li>
					<li>Italian translation by <a href="https://www.transifex.com/accounts/profile/Lurtz/">@Lurtz</a> and <a href="https://www.transifex.com/accounts/profile/ClaraDiGennaro/">@ClaraDiGennaro</a></li>
					<li>French translation by <a href="https://www.transifex.com/accounts/profile/franckt/">@franckt</a> and <a href="https://www.transifex.com/accounts/profile/Newbdev/">@Newbdev</a></li>
					<li>Portuguese translation by <a href="https://www.transifex.com/accounts/profile/luistinygod/">@luistinygod</a>, <a href="https://www.transifex.com/accounts/profile/marlosvinicius.info/">@marlosvinicius</a>, and <a href="https://www.transifex.com/user/profile/rafaehlers/">@rafaehlers</a></li>
					<li>Romanian translation by <a href="https://www.transifex.com/accounts/profile/ArianServ/">@ArianServ</a></li>
					<li>Finnish translation by <a href="https://www.transifex.com/accounts/profile/harjuja/">@harjuja</a></li>
					<li>Spanish translation by <a href="https://www.transifex.com/accounts/profile/jorgepelaez/">@jorgepelaez</a>, <a href="https://www.transifex.com/accounts/profile/luisdiazvenero/">@luisdiazvenero</a>, <a href="https://www.transifex.com/accounts/profile/josemv/">@josemv</a>, <a href="https://www.transifex.com/accounts/profile/janolima/">@janolima</a> and <a href="https://www.transifex.com/accounts/profile/matrixmercury/">@matrixmercury</a>, <a href="https://www.transifex.com/user/profile/jplobaton/">@jplobaton</a></li>
					<li>Swedish translation by <a href="https://www.transifex.com/accounts/profile/adamrehal/">@adamrehal</a></li>
					<li>Indonesian translation by <a href="https://www.transifex.com/accounts/profile/sariyanta/">@sariyanta</a></li>
					<li>Norwegian translation by <a href="https://www.transifex.com/accounts/profile/aleksanderespegard/">@aleksanderespegard</a></li>
					<li>Danish translation by <a href="https://www.transifex.com/accounts/profile/jaegerbo/">@jaegerbo</a></li>
					<li>Chinese translation by <a href="https://www.transifex.com/user/profile/michaeledi/">@michaeledi</a></li>
					<li>Persian translation by <a href="https://www.transifex.com/user/profile/azadmojtaba/">@azadmojtaba</a>, <a href="https://www.transifex.com/user/profile/amirbe/">@amirbe</a>, <a href="https://www.transifex.com/user/profile/Moein.Rm/">@Moein.Rm</a></li>
					<li>Russian translation by <a href="https://www.transifex.com/user/profile/gkovaleff/">@gkovaleff</a>, <a href="https://www.transifex.com/user/profile/awsswa59/">@awsswa59</a></li>
					<li>Polish translation by <a href="https://www.transifex.com/user/profile/dariusz.zielonka/">@dariusz.zielonka</a></li>
				</ul>

				<h3><?php esc_attr_e( 'Want to contribute?', 'gk-gravityview' ); ?></h3>
				<p><?php echo sprintf( esc_attr__( 'If you want to contribute to the code, %syou can on Github%s. If your contributions are accepted, you will be thanked here.', 'gk-gravityview'), '<a href="https://github.com/gravityview/GravityView">', '</a>' ); ?></p>
			</div>

			<hr class="clear" />

			<div class="changelog">

				<h3>Thanks to the following open-source software:</h3>

				<ul class="ul-disc">
					<li><a href="https://datatables.net/">DataTables</a> - amazing tool for table data display. Many thanks!</li>
					<li><a href="https://github.com/10up/flexibility">Flexibility</a> - Adds support for CSS flexbox to Internet Explorer 8 &amp; 9</li>
					<li><a href="https://github.com/GaryJones/Gamajo-Template-Loader">Gamajo Template Loader</a> - makes it easy to load template files with user overrides</li>
					<li><a href="https://github.com/carhartl/jquery-cookie">jQuery Cookie plugin</a> - Access and store cookie values with jQuery</li>
					<li><a href="https://www.gravitykit.com/gravityforms">Gravity Forms</a> - If Gravity Forms weren't such a great plugin, GravityView wouldn't exist!</li>
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
		if( $upgrade === GV_PLUGIN_VERSION ) {
			return;
		}

		// Add "Upgraded From" Option
		update_option( 'gv_version_upgraded_from', GV_PLUGIN_VERSION );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) { return; }

		// First time install
		if( ! $upgrade ) {
			wp_safe_redirect( admin_url( 'admin.php?page=gv-getting-started' ) ); exit;
		}
		// Update
		else {
			wp_safe_redirect( admin_url( 'admin.php?page=gv-changelog' ) ); exit;
		}
	}
}
new GravityView_Welcome;
