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
	 * @group Beta
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function admin_menus() {

		// Add help page to GravityView menu
		add_submenu_page(
			'edit.php?post_type=gravityview',
			__('GravityView Beta: Getting Started', 'gravity-view'),
			__('Getting Started', 'gravity-view'),
			$this->minimum_capability,
			'gv-getting-started',
			array( $this, 'getting_started_screen' )
		);

		// About Page
		add_submenu_page(
			'edit.php?post_type=gravityview',
			__( 'Welcome to GravityView', 'gravity-view' ),
			__( 'Welcome to GravityView', 'gravity-view' ),
			$this->minimum_capability,
			'gv-beta-testing',
			array( $this, 'beta_testing_screen' )
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

		return in_array($plugin_page, array('gv-about', 'gv-beta-testing', 'gv-credits', 'gv-getting-started'));
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

		remove_submenu_page( 'edit.php?post_type=gravityview', 'gv-beta-testing' );
		remove_submenu_page( 'edit.php?post_type=gravityview', 'gv-credits' );

		if( !$this->is_dashboard_page() ) { return; }

		?>
		<style type="text/css" media="screen">
		/*<![CDATA[*/

		.update-nag { display: none; }

		.gv-welcome-screenshots {
			float: right;
			max-width:50%;
			margin: 0 10px 10px 0!important;
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

		list( $display_version ) = explode( '-', GravityView_Plugin::version );

		$selected = !empty( $plugin_page ) ? $plugin_page : 'gv-getting-started';
		?>

		<h1><img class="alignleft" src="<?php echo plugins_url( 'images/astronaut-200x263.png', GRAVITYVIEW_FILE ); ?>" width="100" height="132" /><?php printf( __( 'Welcome to GravityView %s', 'gravity-view' ), $display_version ); ?></h1>
		<div class="about-text"><?php _e( 'Thank you for Installing GravityView. Beautifully display your Gravity Forms entries.', 'gravity-view' ); ?></div>

		<h2 class="nav-tab-wrapper clear">
			<a class="nav-tab <?php echo $selected == 'gv-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-getting-started', 'post_type' => 'gravityview'), 'edit.php' ) ) ); ?>">
				<?php _e( "Getting Started", 'gravity-view' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'gv-beta-testing' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-beta-testing', 'post_type' => 'gravityview'), 'edit.php' ) ) ); ?>">
				<?php _e( 'Beta Testing', 'gravity-view' ); ?>
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
	 * @group Beta
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function getting_started_screen() {
		list( $display_version ) = explode( '-', GravityView_Plugin::version );
		?>
		<div class="wrap about-wrap">

			<?php $this->tabs(); ?>



			<div class="feature-section col two-col" style="margin-top:0;">

				<div>

					<h2>Create a View</h2>

					<ol class="ol-decimal">
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
					<li>Fields can be dragged and dropped to be re-arranged.</li>
					<li>Click the <i class="dashicons dashicons-admin-generic"></i> gear icon on each field to configure the <strong>Field Settings</strong>:
					<ul class="ul-square">
						<li><em>Custom Label</em>: Change how the label is shown on the website. Default: the name of the field</li>
						<li><em>Custom CSS Class</em>: Add additional CSS classes to the field container</li>
						<li><em>Use this field as a search filter</em>: Allow searching the text of a field, or narrowing visible results using the field.</li>
						<li><em>Only visible to logged in users with role</em>: Make certain fields visible only to users who are logged in.</li>
					</ul>
					</li>
				</ul>

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

			<p class="about-description"><?php _e( 'GravityView is brought to you because of the people and packages below.', 'gravity-view' ); ?></p>

			<div class="feature-section col two-col">

				<div>
					<h2>Zack Katz</h2>
					<h4 style="font-weight:0; margin-top:0">Project Lead &amp; Developer</h4>
					<p></p>
					<p><img style="float:left; margin: 0 15px 0 0;" src="http://gravatar.com/avatar/f0f175f8545912adbdab86f0b586f4c3?d=mm&amp;s=150&amp;r=G" width="94" height="94" />Zack has been developing integrations with Gravity Forms since 2009. He lives with his wife and cat in Denver, Colorado.</p>
				</div>

				<div class="last-feature">
					<h2>Luis Godinho</h2>
					<h4 style="font-weight:0; margin-top:0">Lead Developer</h4>
					<p><img class="alignleft avatar" src="http://gravatar.com/avatar/5cf96bd9e526ede404680fabd299ffef?d=mm&amp;s=150&amp;r=G" width="94" height="94" />Luis is a WordPress developer passionate about WordPress, and co-founder and partner of GOMO, a digital agency located in Lisbon, Portugal.</p>
					<p><a href="http://tinygod.pt">View Luis&rsquo;s website</a></p>
				</div>

			</div>

			<hr class="clear" />

			<div class="feature-section">
				<div>
					<h2>Contributors (your name here)</h2>
					<h4>Want to contribute?</h4>
					<p>If you want to contribute to the code, you can <a href="mailto:zack@katzwebservices.com?subject=Github%20Access">request access to the Github repository</a>. If your contributions are accepted, you will be thanked here.</p>
				</div>
			</div>

			<hr class="clear" />

			<div class="changelog">

				<h4>GravityView uses the following open-source software:</h4>

				<ul>
					<li><a href="http://reduxframework.com">ReduxFramework</a> - a powerful settings library</li>
					<li><a href="https://github.com/GaryJones/Gamajo-Template-Loader">Gamajo Template Loader</a> - makes it easy to load template files with user overrides</li>
					<li><a href="https://github.com/carhartl/jquery-cookie">jQuery Cookie plugin</a> - Access and store cookie values with jQuery</li>
					<li><a href="http://katz.si/gf">Gravity Forms</a> - If Gravity Forms weren't such a great plugin, GravityView wouldn't exist!</li>
				</ul>

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
	public function beta_testing_screen() {
		list( $display_version ) = explode( '-', GravityView_Plugin::version );
		?>
		<div class="wrap about-wrap">

			<?php $this->tabs(); ?>

			<div class="feature-section col two-col">

				<h3>Thank you for taking part in the GravityView beta</h3>

				<div>
					<h2>How to report issues</h2>
					<h4 style="font-weight:normal;">If you find a bug, it is most helpful if you <a href="https://gravityview.co/report-an-issue/">submit a report on the website</a>.</h4>
					<p><img src="<?php echo plugins_url( 'images/screenshots/report-bug.png', GRAVITYVIEW_FILE ); ?>" class="screenshot" style="max-width:50%;" height="271" width="231" alt="Reporting bugs" />If you find an issue, you can <a href="https://gravityview.co/report-an-issue/">report it on the website</a>, or at the bottom of every GravityView page is a report widget (pictured).</p>
					<p>Click the "question mark" button and be as <strong>descriptive as possible</strong>.</p>
					<p><strong>Check the "Include a screenshot..." checkbox</strong> - this will help us fix your issue.</p>

					<h4>Request Github access</h4>

					<p>If you want to contribute to the code, you can <a href="mailto:zack@katzwebservices.com?subject=Github%20Access">request access to the Github repository</a>.</p>
				</div>

				<div class="last-feature">
					<h2 class="clear">Thank you for your help.</h2>

					<h4 style="font-weight:normal;" class="clear">By helping discover bugs, suggest enhancements, and provide feedback:</h4>

					<ul>
						<li><strong>50% off a GravityView license</strong> - everyone with Beta access will receive a discount</li>
						<li><strong>The top 10 promoters of GravityView during the private Beta will receive a free license.</strong></li>
						<li>You&rsquo;ll get a free license if you <strong>report an issue or contribute to the code</strong></li>
						<li><strong>If you contribute to the code</strong>, you&rsquo;ll receive a thank-you on the plugin&rsquo;s "Credits" page</li>
					</ul>
				</div>
			</div>

			<hr class="clear" />

			<div class="feature-section">
				<h2>Feature Requests</h2>

				<p>You can share your ideas for feature requests on the <a href="http://gravityview.uservoice.com/forums/238941-gravity-forms-directory">Ideas Forum</a>.</p>
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
		global $plugin_page;

		// Bail if we're just editing the plugin
		if( $plugin_page === 'plugin-editor.php' ) { return; }

		// Bail if no activation redirect
		if ( ! get_transient( '_gv_activation_redirect' ) ) { return; }

		// Delete the redirect transient
		delete_transient( '_gv_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) { return; }

		$upgrade = get_option( 'gv_version_upgraded_from' );

		// After Beta
		if( ! $upgrade ) { // First time install
			wp_safe_redirect( admin_url( 'edit.php?post_type=gravityview&page=gv-beta-testing' ) ); exit;
		} else { // Update
			wp_safe_redirect( admin_url( 'edit.php?post_type=gravityview&page=gv-getting-started' ) ); exit;
		}
	}
}
new GravityView_Welcome;
