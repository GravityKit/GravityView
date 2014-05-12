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
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function about_screen() {
		list( $display_version ) = explode( '-', GravityView_Plugin::version );
		?>
		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to GravityView %s', 'gravity-view' ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( __( 'Thank you for Installing GravityView %s. Beautifully display your Gravity Forms entries.', 'gravity-view' ), $display_version ); ?></div>

			<?php $this->tabs(); ?>

			<div class="changelog">
				<h3>Thank you for taking part in the GravityView beta</h3>

				<div class="feature-section">

					<div id="message" class="updated inline">
						<h3>By helping discover bugs, suggest enhancements, and provide feedback:</h3>

						<ul>
						<li><strong>50% off a GravityView license</strong> - everyone with Beta access will receive a discount</li>
						<li><strong>The top 10 promoters of GravityView during the private Beta will receive a free license.</strong></li>
						<li>You'll get a free license if you <strong>report an issue or contribute to the code</strong></li>
						<li><strong>If you contribute to the code</strong>, you'll receive a thank-you on the plugin's "Credits" page</li>
						</ul>
					</div>

					<h2>How to report issues</h2>

					<div class="alignright"><img src="<?php echo plugins_url( 'images/screenshots/report-bug.png', GRAVITYVIEW_FILE ); ?>" /></div>


					<p>If you find an issue, at the bottom of every GravityView page is a report widget (pictured at right). Please click the "question mark" button and be as descriptive as possible. Checking the "Include a screenshot..." checkbox will help us fix your issue.</p>

					<h3>Request Github access</h3>

					<p>If you want to contribute to the code, you can <a href="mailto:zack@katzwebservices.com?subject=Github%20Access">request access to the Github repository</a>.</p>

				</div>
			</div>

			<div class="changelog">
				<h3><?php _e( 'Additional Updates', 'gravity-view' );?></h3>

				<div class="feature-section col three-col">
					<div>
						<h4><?php _e( 'Update One', 'gravity-view' );?></h4>
					<p><?php _e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'gravity-view' );?></p>

						<h4><?php _e( 'Update Two', 'gravity-view' );?></h4>
					<p><?php _e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'gravity-view' );?></p>
					</div>

					<div>
						<h4><?php _e( 'Update Three', 'gravity-view' );?></h4>
					<p><?php _e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'gravity-view' );?></p>

						<h4><?php _e( 'Update Four', 'gravity-view' );?></h4>
						<p><?php _e( 'A new API has been introduced for easily adding new template tags to purchase receipts and admin sale notifications.', 'gravity-view' );?></p>
					</div>

					<div class="last-feature">
						<h4><?php _e( 'Update Five', 'gravity-view' );?></h4>
					<p><?php _e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'gravity-view' );?></p>

						<h4><?php _e( 'Update Six','gravity-view' );?></h4>
					<p><?php _e( 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'gravity-view' );?></p>
					</div>
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

		if( ! $upgrade ) { // First time install
			wp_safe_redirect( admin_url( 'index.php?page=gv-getting-started' ) ); exit;
		} else { // Update
			wp_safe_redirect( admin_url( 'index.php?page=gv-about' ) ); exit;
		}
	}
}
new GravityView_Welcome;