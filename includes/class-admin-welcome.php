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
		// About Page
		add_dashboard_page(
			__( 'Welcome to GravityView', 'gravity-view' ),
			__( 'Welcome to GravityView', 'gravity-view' ),
			$this->minimum_capability,
			'gv-about',
			array( $this, 'about_screen' )
		);

		// Getting Started Page
		add_dashboard_page(
			__( 'Getting started with GravityView', 'gravity-view' ),
			__( 'Getting started with GravityView', 'gravity-view' ),
			$this->minimum_capability,
			'gv-getting-started',
			array( $this, 'getting_started_screen' )
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

		return in_array($plugin_page, array('gv-about', 'gv-getting-started'));
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

		remove_submenu_page( 'index.php', 'gv-about' );
		remove_submenu_page( 'index.php', 'gv-getting-started' );

		if( !$this->is_dashboard_page() ) { return; }

		?>
		<style type="text/css" media="screen">
		/*<![CDATA[*/

		.update-nag { display: none; }

		.gv-welcome-screenshots {
			float: right;
			margin-left: 10px!important;
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
		$selected = isset( $_GET['page'] ) ? $_GET['page'] : 'gv-about';
		?>
		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo $selected == 'gv-about' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-about' ), 'index.php' ) ) ); ?>">
				<?php _e( "About", 'gravity-view' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'gv-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-getting-started' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Beta Testing', 'gravity-view' ); ?>
			</a>
		</h2>
		<?php
	}

	/**
	 * Render About Screen
	 *
	 * @group Beta
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function about_screen() {
		list( $display_version ) = explode( '-', GravityView_Plugin::version );
		?>
		<div class="wrap about-wrap">
			<h1><?php _e( 'Welcome to GravityView Beta', 'gravity-view' ); ?></h1>
			<div class="about-text"><?php printf( __( 'Thank you for installing GravityView %s. Beautifully display your Gravity Forms entries.', 'gravity-view' ), $display_version ); ?></div>

			<?php
				/*
					// For later...
					$this->tabs();
				 */
			?>

			<div class="changelog">

				<div class="feature-section col" style="margin-top:0;">

					<div>

						<h2>Getting Started</h2>

						<ol>
							<li>Go to <a href="<?php echo admin_url('post-new.php?post_type=gravityview'); ?>">Views &gt; New View</a></li>
							<li>If you want to <strong>create a new form</strong>, click the "Start Fresh" button</li>
							<li>If you want to <strong>use an existing form&rsquo;s entries</strong>, select from the dropdown.</li>
							<li>Select the type of View you would like to create. There are two core types of Views: <strong>Table</strong> and <strong>Listing</strong>.
								<ul>
									<li><strong>Table Views</strong> output entries as tables; a grid of data.</li>
									<li><strong>Listing Views</strong> display entries in a more visual layout.</li>
								</ul>
							</li>
						</ol>

						<h4>Configure Mulitple Entry &amp; Single Entry Layouts</h4>
						<p>You can configure how <strong>Multiple Entry</strong> and <strong>Single Entry</strong>. These can be configured by using the tabs under "View Configuration."</p>

						<p class="clear:right;"><img src="<?php echo plugins_url( 'images/screenshots/add-field.png', GRAVITYVIEW_FILE ); ?>" alt="Add a field dialog box" style="max-width:50%; clear:right;" /></p>

						<ul>
							<li>Click "+ Add Field" to add a field to a zone*</li>
							<li>Fields can be dragged and dropped to be re-arranged.</li>
							<li>Click the <i class="dashicons dashicons-admin-generic"></i> gear icon on each field to configure the <strong>Field Settings</strong>:
							<ul>
								<li><em>Custom Label</em>: Change how the label is shown on the website. Default: the name of the field</li>
								<li><em>Custom CSS Class</em>: Add additional CSS classes to the field container</li>
								<li><em>Use this field as a search filter</em>: Allow searching the text of a field, or narrowing visible results using the field.</li>
								<li><em>Only visible to logged in users with role</em>: Make certain fields visible only to users who are logged in.</li>
							</ul>
							</li>
						</ul>

						<h4>Embed Views in Posts &amp; Pages</h4>
						<p><img src="<?php echo plugins_url( 'images/screenshots/add-view-button.png', GRAVITYVIEW_FILE ); ?>" class="screenshot" style="max-width:50%; float:left; margin-right:1em; margin-bottom:1em;" height="44" width="103" />Unlike the Gravity Forms Directory plugin, views are stand-alone; they don&rsquo;t need to always be embedded, but you can still embed Views using the "Add View" button.</p>

					</div>
				</div>

				<hr />

				<div class="feature-section col two-col">

					<h3>Thank you for taking part in the GravityView beta</h3>

					<div>
						<h2>How to report issues</h2>

						<p><img src="<?php echo plugins_url( 'images/screenshots/report-bug.png', GRAVITYVIEW_FILE ); ?>" class="screenshot" style="max-width:50%;" height="271" width="236" />If you find an issue, at the bottom of every GravityView page is a report widget (pictured below). Please click the "question mark" button and be as descriptive as possible. Checking the "Include a screenshot..." checkbox will help us fix your issue.</p>

						<h4>Request Github access</h4>

						<p>If you want to contribute to the code, you can <a href="mailto:zack@katzwebservices.com?subject=Github%20Access">request access to the Github repository</a>.</p>
					</div>

					<div class="last-feature">
						<h2 class="clear">Thank you for your help.</h2>

						<h4 class="clear">By helping discover bugs, suggest enhancements, and provide feedback:</h4>

						<ul>
							<li><strong>50% off a GravityView license</strong> - everyone with Beta access will receive a discount</li>
							<li><strong>The top 10 promoters of GravityView during the private Beta will receive a free license.</strong></li>
							<li>You&rsquo;ll get a free license if you <strong>report an issue or contribute to the code</strong></li>
							<li><strong>If you contribute to the code</strong>, you&rsquo;ll receive a thank-you on the plugin&rsquo;s "Credits" page</li>
						</ul>
					</div>
				</div>

				<hr />

				<div class="feature-section">
					<h2>Things we&rsquo;re working on:</h2>

					<h4>We&rsquo;re working on adding this functionality:</h4>

					<ul>
						<li><strong>Front-end editing of entries</strong></li>
						<li><strong>More Views!</strong></li>
						<li><strong>Column Sorting</strong><br/>
						We&rsquo;re going to be integrating with <a href="http://datatables.net">DataTables</a> to provide some advanced sorting and search functionality. Until then, the sorting options are limited: none.</li>
						<li><strong>Map View</strong><br/>
						Display your entries on a map view.</li>
						<li><strong>Advanced output with merge tags</strong><br/>
						We&rsquo;ll be adding the ability to integrate the value of the entry field with the output.</li>
						<li>And much, MUCH more&hellip;</li>
					</ul>

					<h4>Feature Requests</h4>

					<p>You can share your ideas for feature requests on the <a href="http://gravityview.uservoice.com/forums/238941-gravity-forms-directory">Ideas Forum</a>.</p>
				</div>
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gravityview' ) ); ?>"><?php _e( 'Configure Views', 'gravity-view' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Getting Started Screen
	 *
	 * @todo  Add a tab!
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function getting_started_screen() {
		list( $display_version ) = explode( '-', GravityView_Plugin::version );
		?>
		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to GravityView %s', 'gravity-view' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( __( 'Thank you for Installing GravityView %s. Beautifully display your Gravity Forms entries.', 'gravity-view' ), $display_version ); ?></div>

			<?php $this->tabs(); ?>

			<p class="about-description"><?php _e( 'Use the tips below to get started using GravityView. You will be up and running in no time!', 'gravity-view' ); ?></p>

			<div class="changelog">

				<h3><?php _e( 'Overview', 'gravity-view' );?></h3>

				<div class="feature-section">

					<h4><?php _e( 'Example Header', 'gravity-view' );?></h4>
					<p><?php _e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'gravity-view' );?></p>

				</div>

			</div>

			<h2>Credits</h2>

			<h3>GravityView uses the following open-source libraries:</h3>

			<ul>
			<li><a href="http://reduxframework.com">ReduxFramework</a> - a powerful settings library</li>
			<li><a href="https://github.com/GaryJones/Gamajo-Template-Loader">Gamajo Template Loader</a> - makes it easy to load template files with user overrides</li>
			<li><a href="http://katz.si/gf">Gravity Forms</a> - If Gravity Forms weren't such a great plugin, GravityView wouldn't exist!</li>
			</ul>

			<div class="changelog">
				<h3><?php _e( 'Quick Terminology', 'gravity-view' );?></h3>

				<div class="feature-section col three-col">
					<div>
						<h4><?php _e( 'View', 'gravity-view' );?></h4>
						<p><?php _e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'gravity-view' );?></p>
					</div>

					<div>
						<h4><?php _e( 'Entry', 'gravity-view' );?></h4>
						<p><?php _e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'gravity-view' );?></p>

					</div>

					<div class="last-feature">
						<h4><?php _e( 'Table', 'gravity-view' );?></h4>
						<p><?php _e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'gravity-view' );?></p>
					</div>
				</div>
			</div>


			<div class="changelog">
				<h3><?php _e( 'Need Help?', 'gravity-view' );?></h3>

				<div class="feature-section">

					<h4><?php _e( 'Phenomenal Support','gravity-view' );?></h4>
					<p><?php _e( 'We do our best to provide the best support we can. If you encounter a problem or have a question, visit our <a href="https://gravityview.co/support">support</a> page to open a ticket.', 'gravity-view' );?></p>
				</div>
			</div>

		</div>
		<?php
	}


	/**
	 * Sends user to the Welcome page on first activation of GravityView as well as each
	 * time GravityView is upgraded to a new version
	 *
	 * @group Beta
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function welcome() {

		// Bail if no activation redirect
		if ( ! get_transient( '_gv_activation_redirect' ) )
			return;

		// Delete the redirect transient
		delete_transient( '_gv_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) )
			return;

		$upgrade = get_option( 'gv_version_upgraded_from' );

		wp_safe_redirect( admin_url( 'index.php?page=gv-about' ) ); exit;

		// After Beta
		if( ! $upgrade ) { // First time install
			wp_safe_redirect( admin_url( 'index.php?page=gv-getting-started' ) ); exit;
		} else { // Update
			wp_safe_redirect( admin_url( 'index.php?page=gv-about' ) ); exit;
		}
	}
}
new GravityView_Welcome;
