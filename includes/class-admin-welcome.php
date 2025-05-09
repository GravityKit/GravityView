<?php
/**
 * Welcome Page Class
 *
 * @package   GravityView
 * @author    Zack Katz <zack@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use GravityKit\GravityView\Foundation\Helpers\Arr;

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
		add_action( 'admin_init', array( $this, 'welcome' ) );
		add_filter( 'gravityview_is_admin_page', array( $this, 'is_dashboard_page' ), 10, 2 );
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
		$admin_menu::add_submenu_item(
			array(
				'id'         => 'gv-changelog',
				'page_title' => esc_html__( 'Changelog', 'gk-gravityview' ),
				'menu_title' => esc_html__( 'Changelog', 'gk-gravityview' ),
				'capability' => $this->minimum_capability,
				'callback'   => array( $this, 'changelog_screen' ),
				'order'      => 40,
				'hide'       => true,
			),
			'center'
		);

		// Changelog Page
		$admin_menu::add_submenu_item(
			array(
				'id'         => 'gv-credits',
				'page_title' => esc_html__( 'Credits', 'gk-gravityview' ),
				'menu_title' => esc_html__( 'Credits', 'gk-gravityview' ),
				'capability' => $this->minimum_capability,
				'callback'   => array( $this, 'credits_screen' ),
				'order'      => 50,
				'hide'       => true,
			),
			'center'
		);

		// Add Getting Started page to GravityView menu
		$admin_menu::add_submenu_item(
			array(
				'id'                                 => 'gv-getting-started',
				'page_title'                         => esc_html__( 'GravityView: Getting Started', 'gk-gravityview' ),
				'menu_title'                         => esc_html__( 'Getting Started', 'gk-gravityview' ),
				'capability'                         => $this->minimum_capability,
				'callback'                           => array( $this, 'getting_started_screen' ),
				'order'                              => 60, // Make it the last so that the border divider remains
				'exclude_from_top_level_menu_action' => true,
			),
			'center'
		);
	}

	/**
	 * Is this page a GV dashboard page?
	 *
	 * @return boolean  $is_page   True: yep; false: nope
	 */
	public function is_dashboard_page( $is_page = false, $hook = null ) {
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
		if ( ! $this->is_dashboard_page() ) {
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

		$selected = ! empty( $plugin_page ) ? $plugin_page : 'gv-getting-started';

		echo gravityview_get_floaty( 132 );
		?>

		<h1><?php
			// translators: %s is the plugin version number
			printf( esc_html__( 'Welcome to GravityView %s', 'gk-gravityview' ), $display_version );
		?></h1>
		<div class="about-text"><?php esc_html_e( 'Thank you for installing GravityView. Beautifully display your Gravity Forms entries.', 'gk-gravityview' ); ?></div>

		<h2 class="nav-tab-wrapper clear">
			<a class="nav-tab <?php echo 'gv-getting-started' == $selected ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-getting-started' ), 'admin.php' ) ) ); ?>">
				<?php esc_html_e( 'Getting Started', 'gk-gravityview' ); ?>
			</a>
			<a class="nav-tab <?php echo 'gv-changelog' == $selected ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-changelog' ), 'admin.php' ) ) ); ?>">
				<?php esc_html_e( 'List of Changes', 'gk-gravityview' ); ?>
			</a>
			<a class="nav-tab <?php echo 'gv-credits' == $selected ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'gv-credits' ), 'admin.php' ) ) ); ?>">
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

			<h2 class="about-headline-callout"><?php esc_html_e( 'Configuring a View', 'gk-gravityview' ); ?></h2>

			<div class="feature-video"  style="text-align:center;">
				<iframe width='560' height='315'
						src='https://www.youtube-nocookie.com/embed/videoseries?list=PLuSpaefk_eAP_OXQVWQVtX0fQ17J8cn09'
						frameborder='0'
						allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share'
						allowfullscreen></iframe>

				<p style="text-align:center; padding-top: 1em;"><a class="button button-primary button-hero" href="https://docs.gravitykit.com/article/380-how-to-setup-your-first-view" rel="noopener noreferrer external" target="_blank"><?php esc_html_e( 'Read more: Setting Up Your First View', 'gk-gravityview' ); ?><span class='screen-reader-text'> <?php esc_attr_e( 'This link opens in a new window.', 'gk-gravityview' ); ?></span></a></p>
			</div>

			<div class="feature-section two-col has-2-columns is-fullwidth">
				<div class="col column">
					<h3><?php esc_html_e( 'Create a View', 'gk-gravityview' ); ?></h3>

					<ol class="ol-decimal">
						<li><?php
							// translators: %s is a link to the New View page
							printf( esc_html__( 'Go to the GravityKit menu and click on %s', 'gk-gravityview' ), '<a href="' . admin_url( 'post-new.php?post_type=gravityview' ) . '">' . esc_html__( 'New View', 'gk-gravityview' ) . '</a>' );
						?></li>
						<li><?php
							// translators: [strong]...[/strong] represents text that should be bold
							echo strtr(
								esc_html__( 'If you want to [strong]create a new form[/strong], click the "Use a Form Preset" button', 'gk-gravityview' ),
								array(
									'[strong]' => '<strong>',
									'[/strong]' => '</strong>'
								)
							);
						?></li>
						<li><?php
							// translators: [strong]...[/strong] represents text that should be bold
							echo strtr(
								esc_html__( 'If you want to [strong]use an existing form&rsquo;s entries[/strong], select from the dropdown.', 'gk-gravityview' ),
								array(
									'[strong]' => '<strong>',
									'[/strong]' => '</strong>'
								)
							);
						?></li>
						<li><?php
							// translators: [strong]Table[/strong] and [strong]Listing[/strong] represent text that should be bold
							echo strtr(
								esc_html__( 'Select the type of View you would like to create. There are two core types of Views: [strong]Table[/strong] and [strong]Listing[/strong].', 'gk-gravityview' ),
								array(
									'[strong]' => '<strong>',
									'[/strong]' => '</strong>'
								)
							);
						?>
							<ul class="ul-square">
								<li><?php
									// translators: [strong]...[/strong] represents text that should be bold
									echo strtr(
										esc_html__( '[strong]Table Views[/strong] output entries as tables; a grid of data.', 'gk-gravityview' ),
										array(
											'[strong]' => '<strong>',
											'[/strong]' => '</strong>'
										)
									);
								?></li>
								<li><?php
									// translators: [strong]...[/strong] represents text that should be bold
									echo strtr(
										esc_html__( '[strong]Listing Views[/strong] display entries in a more visual layout.', 'gk-gravityview' ),
										array(
											'[strong]' => '<strong>',
											'[/strong]' => '</strong>'
										)
									);
								?></li>
							</ul>
						</li>
						<li><?php esc_html_e( 'On the View Configuration metabox, click on the "+Add Field" button to add form fields to the active areas of your View. These are the fields that will be displayed in the frontend.', 'gk-gravityview' ); ?></li>
					</ol>
				</div>
				<div class="col column">
					<h4><?php esc_html_e( 'What is a View?', 'gk-gravityview' ); ?></h4>
					<p><?php esc_html_e( 'When a form is submitted in Gravity Forms, an entry is created. Without GravityView, Gravity Forms entries are visible only in the WordPress dashboard, and only to users with permission.', 'gk-gravityview' ); ?></p>
					<p><?php esc_html_e( 'GravityView allows you to display entries on the front of your site. In GravityView, when you arrange the fields you want displayed and save the configuration, it\'s called a "View".', 'gk-gravityview' ); ?></p>
				</div>
			</div>

			<hr />

			<div class="feature-section two-col has-2-columns is-fullwidth">
				<div class="col column">
					<h3><?php esc_html_e( 'Embed GravityView Blocks in Your Content', 'gk-gravityview' ); ?></h3>
					<p><?php esc_html_e( 'GravityView provides several powerful blocks that allow you to easily embed Views, Entries, and more into your content.', 'gk-gravityview' ); ?></p>
					<style>
						.gravityview-block-container .gravityview-block {
							flex: 1;
							min-width: 300px;
							border: 1px solid #e2e4e7;
							border-radius: 5px;
							padding: 20px;
							background-color: white;
							margin-bottom: 1em;
						}
						.gravityview-block-container .gravityview-block-svg-container {
							display: flex;
							align-items: center;
							margin-bottom: 15px;
						}
						.gravityview-block-container .gravityview-block-svg-container h3 {
							margin: 0;
							font-size: 1.2em;
						}
						.gravityview-block-container .gravityview-block svg {
							margin-right: 10px;
						}
						.gravityview-block-container .gravityview-block p:last-child {
							margin-bottom: 0;
						}
					</style>
					<div class="gravityview-block-container">
						<div class="gravityview-block">
							<div class="gravityview-block-svg-container">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none"
									xmlns="http://www.w3.org/2000/svg" style="margin-right: 10px;">
									<path fillRule="evenodd" clipRule="evenodd" d="M3 0C1.34315 0 0 1.34314 0 3V21C0 22.6569 1.34314 24 3 24H21C22.6569 24 24 22.6569 24 21V3C24 1.34315 22.6569 0 21 0H3ZM2 3C2 2.44772 2.44771 2 3 2H21C21.5523 2 22 2.44771 22 3V21C22 21.5523 21.5523 22 21 22H3C2.44772 22 2 21.5523 2 21V3ZM5 4C4.44772 4 4 4.44772 4 5C4 5.55228 4.44772 6 5 6H8C8.55228 6 9 5.55228 9 5C9 4.44772 8.55228 4 8 4H5ZM4 9C4 8.44771 4.44772 8 5 8H19C19.5523 8 20 8.44771 20 9C20 9.55228 19.5523 10 19 10H5C4.44772 10 4 9.55228 4 9ZM12 4C11.4477 4 11 4.44772 11 5C11 5.55228 11.4477 6 12 6H19C19.5523 6 20 5.55228 20 5C20 4.44772 19.5523 4 19 4H12Z" fill="#2B292B" />
									<path fillRule="evenodd" clipRule="evenodd" d="M5 12C4.44772 12 4 12.4477 4 13V19C4 19.5523 4.44772 20 5 20H19C19.5523 20 20 19.5523 20 19V13C20 12.4477 19.5523 12 19 12H5ZM9 15C8.44771 15 8 15.4477 8 16C8 16.5523 8.44771 17 9 17H15C15.5523 17 16 16.5523 16 16C16 15.4477 15.5523 15 15 15H9Z" fill="#2B292B" />
								</svg>
								<h3><?php esc_html_e( 'GravityView View', 'gk-gravityview' ); ?></h3>
							</div>
							<p>
								<?php echo strtr(
									// translators: Do not translate [strong] and [/strong]; these are replaced with HTML tags.
									esc_html__( '[strong]Display a complete GravityView View[/strong] on any page or post.', 'gk-gravityview' ),
									[
										'[strong]' => '<strong>',
										'[/strong]' => '</strong>',
									] ); ?>
							</p>
							<p><?php esc_html_e( 'Embed fully-functional Views with all your configured layouts and settings.', 'gk-gravityview' ); ?></p>
						</div>
						<div class="gravityview-block">
							<div class="gravityview-block-svg-container">
								<svg width="24" height="22" viewBox="0 0 24 22" fill="none"
									xmlns="http://www.w3.org/2000/svg" style="margin-right: 10px;">
									<path fillRule="evenodd" clipRule="evenodd" d="M3 0C1.34315 0 0 1.34315 0 3V19C0 20.6569 1.34315 22 3 22H17C18.6569 22 20 20.6569 20 19V18H18V19C18 19.5523 17.5523 20 17 20H3C2.44772 20 2 19.5523 2 19V3C2 2.44772 2.44772 2 3 2H17C17.5523 2 18 2.44772 18 3V4H20V3C20 1.34315 18.6569 0 17 0H3Z" fill="#2B292B" />
									<path fillRule="evenodd" clipRule="evenodd" d="M11 4H4V6H11V4ZM7 8H4V10H7V8ZM4 12H7V14H4V12ZM11 16H4V18H11V16ZM16 17C20.707 17 23.744 11.716 23.871 11.492C24.042 11.188 24.043 10.816 23.872 10.512C23.746 10.287 20.731 5 16 5C11.245 5 8.25101 10.289 8.12601 10.514C7.95701 10.817 7.95801 11.186 8.12801 11.489C8.25401 11.713 11.269 17 16 17ZM16 7C18.839 7 21.036 9.835 21.818 11C21.034 12.166 18.837 15 16 15C13.159 15 10.962 12.162 10.181 10.999C10.958 9.835 13.146 7 16 7ZM18 11C18 12.1046 17.1046 13 16 13C14.8954 13 14 12.1046 14 11C14 9.89543 14.8954 9 16 9C17.1046 9 18 9.89543 18 11Z" fill="#2B292B" />
								</svg>
								<h3><?php esc_html_e( 'GravityView View Details', 'gk-gravityview' ); ?></h3>
							</div>
							<p>
								<?php echo strtr(
									// translators: Do not translate [strong] and [/strong]; these are replaced with HTML tags.
									esc_html__( '[strong]Display specific information about a View[/strong], such as the total number of entries.', 'gk-gravityview' ),
									[
										'[strong]' => '<strong>',
										'[/strong]' => '</strong>'
									]
								); ?>
							</p>
							<p><?php esc_html_e( 'Great for showing statistics and summaries from your form data.', 'gk-gravityview' ); ?></p>
						</div>
					</div>
					<div class="gravityview-block-container">
						<div class="gravityview-block">
							<div class="gravityview-block-svg-container">
								<svg width="20" height="22" viewBox="0 0 20 22" fill="none"
									xmlns="http://www.w3.org/2000/svg" style="margin-right: 10px;">
									<path fillRule="evenodd" clipRule="evenodd" d="M0 3C0 1.34315 1.34315 0 3 0H17C18.6569 0 20 1.34315 20 3V19C20 20.6569 18.6569 22 17 22H3C1.34315 22 0 20.6569 0 19V3ZM3 2C2.44772 2 2 2.44772 2 3V19C2 19.5523 2.44772 20 3 20H17C17.5523 20 18 19.5523 18 19V3C18 2.44772 17.5523 2 17 2H3ZM4 4H16V6H4V4ZM13 8H4V10H13V8ZM4 12H8V14H4V12ZM16 12H9V14H16V12ZM12 16V18H4V16H12ZM16 16H13V18H16V16Z" fill="#2B292B" />
								</svg>
								<h3><?php esc_html_e( 'GravityView Entry', 'gk-gravityview' ); ?></h3>
							</div>
							<p>
								<?php echo strtr(
									// translators: Do not translate [strong] and [/strong]; these are replaced with HTML tags.
									esc_html__( '[strong]Display a complete entry[/strong] from your form submissions.', 'gk-gravityview' ),
									[
										'[strong]' => '<strong>',
										'[/strong]' => '</strong>'
									]
								); ?>
							</p>
							<p><?php esc_html_e( 'Show all fields from a specific entry using your Single Entry layout.', 'gk-gravityview' ); ?></p>
						</div>
						<div class="gravityview-block">
							<div class="gravityview-block-svg-container">
								<svg width="24" height="20" viewBox="0 0 24 20" fill="none"
									xmlns="http://www.w3.org/2000/svg" style="margin-right: 10px;">
									<path fillRule="evenodd" clipRule="evenodd" d="M0 0H8V2H0V0ZM0 4H1H23H24V5V19V20H23H1H0V19V5V4ZM2 6V18H22V6H2ZM18 11H5V13H18V11Z" fill="#2B292B" />
								</svg>
								<h3><?php esc_html_e( 'GravityView Entry Field', 'gk-gravityview' ); ?></h3>
							</div>
							<p>
								<?php echo strtr(
									// translators: Do not translate [strong] and [/strong]; these are replaced with HTML tags.
									esc_html__( '[strong]Display a single field from an entry[/strong] anywhere on your site.', 'gk-gravityview' ),
									[
										'[strong]' => '<strong>',
										'[/strong]' => '</strong>'
									]
								); ?>
							</p>
							<p><?php esc_html_e( 'Perfect for highlighting specific information from your entries.', 'gk-gravityview' ); ?></p>
						</div>
					</div>
					<div class="gravityview-block-container">
						<div class="gravityview-block">
							<div class="gravityview-block-svg-container">
								<svg width="24" height="14" viewBox="0 0 24 14" fill="none"
									xmlns="http://www.w3.org/2000/svg" style="margin-right: 10px;">
									<path fillRule="evenodd" clipRule="evenodd" d="M20 2H22V12H20V14H22H24V12V2V0H22H20V2ZM4 2V0H2H0V2V12V14H2H4V12H2V2H4Z" fill="#2B292B" />
									<path
								fillRule="evenodd" clipRule="evenodd" d="M8.53498 6.307C8.62599 6.39801 8.69819 6.50605 8.74744 6.62495C8.7967 6.74386 8.82205 6.8713 8.82205 7C8.82205 7.12871 8.7967 7.25615 8.74744 7.37506C8.69819 7.49396 8.62599 7.602 8.53498 7.693C8.17603 8.04983 7.96902 8.53167 7.9573 9.03767C7.94558 9.54366 8.13005 10.0346 8.4721 10.4076C8.81415 10.7807 9.28725 11.007 9.79236 11.0391C10.2975 11.0712 10.7954 10.9067 11.182 10.58L11.392 10.39C11.588 10.2339 11.8365 10.1589 12.0862 10.1803C12.3358 10.2018 12.5678 10.318 12.7344 10.5053C12.901 10.6925 12.9896 10.9364 12.9819 11.1869C12.9742 11.4374 12.8708 11.6754 12.693 11.852C12.3289 12.2161 11.8967 12.5049 11.4209 12.702C10.9452 12.899 10.4354 13.0004 9.92048 13.0004C9.40558 13.0004 8.89572 12.899 8.42001 12.702C7.94431 12.5049 7.51207 12.2161 7.14798 11.852C6.78389 11.4879 6.49508 11.0557 6.29803 10.58C6.10099 10.1043 5.99957 9.59441 5.99957 9.0795C5.99957 8.5646 6.10099 8.05475 6.29803 7.57904C6.49508 7.10333 6.78389 6.67109 7.14798 6.307C7.239 6.21584 7.3471 6.14352 7.4661 6.09418C7.5851 6.04483 7.71266 6.01943 7.84148 6.01943C7.9703 6.01943 8.09786 6.04483 8.21686 6.09418C8.33586 6.14352 8.44396 6.21584 8.53498 6.307ZM13.386 5.614C13.477 5.70501 13.5492 5.81305 13.5984 5.93195C13.6477 6.05086 13.6731 6.1783 13.6731 6.307C13.6731 6.43571 13.6477 6.56315 13.5984 6.68206C13.5492 6.80096 13.477 6.909 13.386 7L12 8.386C11.8162 8.5698 11.5669 8.67305 11.307 8.67305C11.0471 8.67305 10.7978 8.5698 10.614 8.386C10.4302 8.20221 10.3269 7.95293 10.3269 7.693C10.3269 7.43308 10.4302 7.1838 10.614 7L12 5.614C12.091 5.52299 12.199 5.4508 12.3179 5.40154C12.4368 5.35229 12.5643 5.32693 12.693 5.32693C12.8217 5.32693 12.9491 5.35229 13.068 5.40154C13.1869 5.4508 13.295 5.52299 13.386 5.614ZM16.852 2.148C17.2162 2.51203 17.5051 2.94425 17.7022 3.41997C17.8993 3.89568 18.0008 4.40557 18.0008 4.9205C18.0008 5.43544 17.8993 5.94533 17.7022 6.42104C17.5051 6.89676 17.2162 7.32898 16.852 7.693C16.761 7.78408 16.6529 7.85633 16.534 7.90564C16.4151 7.95496 16.2876 7.98036 16.1588 7.98041C16.0301 7.98045 15.9026 7.95514 15.7836 7.90592C15.6647 7.85669 15.5566 7.78451 15.4655 7.6935C15.3744 7.6025 15.3022 7.49445 15.2528 7.37552C15.2035 7.25659 15.1781 7.12911 15.1781 7.00036C15.178 6.87161 15.2033 6.74411 15.2526 6.62515C15.3018 6.50618 15.374 6.39808 15.465 6.307C15.8239 5.95018 16.0309 5.46834 16.0427 4.96234C16.0544 4.45635 15.8699 3.96544 15.5279 3.59238C15.1858 3.21932 14.7127 2.99304 14.2076 2.96091C13.7025 2.92878 13.2045 3.09329 12.818 3.42L12.608 3.61C12.4119 3.76608 12.1635 3.84112 11.9138 3.81968C11.6641 3.79825 11.4321 3.68197 11.2655 3.49476C11.0989 3.30754 11.0104 3.06362 11.0181 2.81313C11.0258 2.56264 11.1292 2.32462 11.307 2.148C11.671 1.78381 12.1032 1.4949 12.5789 1.29779C13.0547 1.10068 13.5645 0.999222 14.0795 0.999222C14.5944 0.999222 15.1043 1.10068 15.58 1.29779C16.0557 1.4949 16.488 1.78381 16.852 2.148Z" fill="#2B292B"
								/>
								</svg>
								<h3><?php esc_html_e( 'GravityView Entry Link', 'gk-gravityview' ); ?></h3>
							</div>
							<p>
								<?php echo strtr(
									// translators: Do not translate [strong] and [/strong]; these are replaced with HTML tags.
									esc_html__( '[strong]Create links to specific entries[/strong] or entry actions.', 'gk-gravityview' ),
									[
										'[strong]' => '<strong>',
										'[/strong]' => '</strong>'
									]
								); ?>
							</p>
							<p><?php esc_html_e( 'Generate links to view, edit, or delete entries with customizable text.', 'gk-gravityview' ); ?></p>
						</div>
					</div>
				</div>
				<div class="col column">
					<video width="485" height="540" controls loop autoplay muted aria-label="<?php esc_attr_e( 'Demonstration of embedding GravityView blocks using the block editor by typing /GravityView to see the block autocompletions.', 'gk-gravityview' ); ?>">
						<source src="<?php echo plugins_url( 'assets/videos/embed-block.mp4', GRAVITYVIEW_FILE ); ?>" type="video/mp4">
					</video>
				</div>
			</div>

			<hr />

			<div class="feature-section two-col has-2-columns is-fullwidth">
				<div class="col column">
					<h3><?php esc_html_e( 'Embed Views in Classic Editor', 'gk-gravityview' ); ?></h3>
					<p><?php esc_html_e( 'Views don&rsquo;t need to be embedded in a post or page, but you can if you want. Embed Views using the "Add View" button above your content editor.', 'gk-gravityview' ); ?></p>
					<p><?php echo strtr(
						// translators: Do not translate [strong] and [/strong]; these are replaced with HTML tags.
						esc_html__( 'GravityView has full support for shortcodes to embed Views, entries, and fields. [link]See a full list of shortcodes[/link].', 'gk-gravityview' ),
						[
							'[link]' => '<a href="https://docs.gravitykit.com/category/322-shortcodes" target="_blank" rel="external noopener noreferrer">',
							'[/link]' => '<span class="screen-reader-text">(' . esc_attr__( 'This link opens in a new window.', 'gk-gravityview' ) . ')</span></a>',
						] ); ?></p>
				</div>
				<div class="col column">
					<img src="<?php echo plugins_url( 'assets/images/screenshots/add-view-button.png', GRAVITYVIEW_FILE ); ?>" alt="<?php esc_attr_e( 'Screenshot of Add View button', 'gk-gravityview' ); ?>" />
				</div>
			</div>

			<hr />

			<div class="feature-section two-col has-2-columns is-fullwidth">
				<div class="col column">
					<h3><?php esc_html_e( 'Configure Multiple Entry, Single Entry, and Edit Entry Layouts', 'gk-gravityview' ); ?></h3>

					<p><?php
						// translators: [strong]Multiple Entry[/strong], [strong]Single Entry[/strong], and [strong]Edit Entry[/strong] represent text that should be bold
						echo strtr(
							esc_html__( 'You can configure what fields are displayed in [strong]Multiple Entry[/strong], [strong]Single Entry[/strong], and [strong]Edit Entry[/strong] modes. These can be configured by clicking on the three associated tabs when editing a View.', 'gk-gravityview' ),
							array(
								'[strong]' => '<strong>',
								'[/strong]' => '</strong>'
							)
						);
					?></p>

					<ul class="ul-disc">
						<li><?php esc_html_e( 'Click "+ Add Field" to add a field to a zone', 'gk-gravityview' ); ?></li>
						<li><?php esc_html_e( 'Click the name of the field you want to display', 'gk-gravityview' ); ?></li>
						<li><?php esc_html_e( 'Once added, fields can be dragged and dropped to be re-arranged. Hover over the field until you see a cursor with four arrows, then drag the field.', 'gk-gravityview' ); ?></li>
						<li><?php
							// translators: %1$s is a gear icon, %2$sField Settings%3$s is bold text
							printf(
								esc_html__( 'Click the %1$s gear icon on each field to configure the %2$sField Settings%3$s', 'gk-gravityview' ),
								'<i class="dashicons dashicons-admin-generic"></i>',
								'<strong>',
								'</strong>'
							);
						?></li>
					</ul>
				</div>
				<div class="col column">
					<img src="<?php echo plugins_url( 'assets/images/screenshots/add-field.png', GRAVITYVIEW_FILE ); ?>" alt="<?php esc_attr_e( 'Add a field dialog box', 'gk-gravityview' ); ?>" />
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
				<?php

				$changelog_html = '';
				if ( class_exists( 'GravityKitFoundation' ) && is_callable( [ 'GravityKitFoundation', 'licenses' ] ) ) {
					$product_manager = GravityKitFoundation::licenses()->product_manager();
					try {
						$products_data = $product_manager->get_products_data( array( 'key_by' => 'id' ) );
					} catch ( Exception $e ) {
						$products_data = [];
					}

					$changelog = Arr::get( $products_data, '17.sections.changelog' );

					if( $changelog ) {
						$changelog_html = wp_kses_post( $changelog );
						$changelog_html = str_replace( [ '<h4>', '</h4>', '<ul>' ], [ '<h3>', '</h3>', '<ul class="ul-disc">' ], $changelog_html );
					}
				}

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $changelog_html;

				/**
				 * Keep the original link to the full changelog page as a fallback
				 * or if the truncated changelog itself doesn't include one (though it should).
				 */
				?>
				<p style="text-align: center; margin-top: 2em;">
					<a href="https://www.gravitykit.com/products/gravityview/#changelog" class="aligncenter button button-primary button-hero" style="margin: 0 auto; display: inline-block; text-transform: capitalize"><?php esc_html_e( 'View Full Change History on GravityKit.com', 'gk-gravityview' ); ?></a>
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
	public function credits_screen() {

		?>
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

			<h2><?php esc_html_e( 'GravityView is brought to you by:', 'gk-gravityview' ); ?></h2>

			<div class="cols">

				<div class="col">
					<h3><?php esc_html_e( 'Zack Katz', 'gk-gravityview' ); ?> <a href="https://x.com/zackkatz"><span class="dashicons dashicons-twitter" title="<?php esc_attr_e( 'Follow Zack on X', 'gk-gravityview' ); ?>"></span></a> <a href="https://katz.co" title="<?php esc_attr_e( 'View Zack&rsquo;s website', 'gk-gravityview' ); ?>"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4><?php esc_html_e( 'Project Lead &amp; Developer', 'gk-gravityview' ); ?></h4>
					<p><img alt="<?php esc_attr_e( 'Zack Katz', 'gk-gravityview' ); ?>" src="<?php echo plugins_url( 'assets/images/team/Zack.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" /><?php
						// translators: %1$s is ATP podcast link, %2$s is The Flop House podcast link
						printf(
							esc_html__( 'Zack has been developing WordPress plugins since 2008 and has been a huge Gravity Forms fan from the start. Zack is co-owner of GravityKit and he lives with his wife in Leverett, Massachusetts. He can&rsquo;t wait for the next episode of %1$s or %2$s podcasts.', 'gk-gravityview' ),
							'<a href="https://atp.fm">ATP</a>',
							'<a href="https://www.flophousepodcast.com">The Flop House</a>'
						);
					?></p>
				</div>

				<div class="col">
					<h3><?php esc_html_e( 'Rafael Ehlers', 'gk-gravityview' ); ?> <a href="https://twitter.com/rafaehlers" title="<?php esc_attr_e( 'Follow Rafael on Twitter', 'gk-gravityview' ); ?>"><span class="dashicons dashicons-twitter"></span></a> <a href="https://heropress.com/essays/journey-resilience/" title="<?php esc_attr_e( 'View Rafael&rsquo;s WordPress Journey', 'gk-gravityview' ); ?>"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4><?php esc_html_e( 'Project Manager, Support Lead &amp; Customer&nbsp;Advocate', 'gk-gravityview' ); ?></h4>
					<p><img alt="<?php esc_attr_e( 'Rafael Ehlers', 'gk-gravityview' ); ?>"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/team/Ehlers.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" /><?php
						// translators: %s is a link to Porto Alegre, Brazil
						printf(
							esc_html__( 'Rafael helps guide GravityKit development priorities and keep us on track. He&rsquo;s the face of our customer support and helps customers get the most out of the product. Rafael hails from %s.', 'gk-gravityview' ),
							'<a href="https://wikipedia.org/wiki/Porto_Alegre">Porto Alegre, Brazil</a>'
						);
					?></p>
				</div>

				<div class="col">
					<h3><?php esc_html_e( 'Vlad K.', 'gk-gravityview' ); ?></h3>
					<h4><?php esc_html_e( 'Head of Development', 'gk-gravityview' ); ?></h4>
					<p><img alt="<?php esc_attr_e( 'Vlad K.', 'gk-gravityview' ); ?>"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/team/Vlad.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" /><?php esc_html_e( 'Vlad is GravityKit&rsquo;s lead developer. Known for his versatility, Vlad handles both front-end and back-end programming, as well as testing and DevOps. He lives in Ottawa, Canada, and frequently travels the world in pursuit of unique experiences that fuel his creativity and broaden his worldview.', 'gk-gravityview' ); ?></p>
				</div>

				<div class="col">
					<h3><?php esc_html_e( 'Rafael Bennemann', 'gk-gravityview' ); ?> <a href="https://x.com/rafaelbe" title="<?php esc_attr_e( 'Follow Rafael on X', 'gk-gravityview' ); ?>"><span class="dashicons dashicons-twitter"></span></a></h3>
					<h4><?php esc_html_e( 'Support Specialist', 'gk-gravityview' ); ?></h4>
					<p><img alt="<?php esc_attr_e( 'Rafael Bennemann', 'gk-gravityview' ); ?>"  class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/team/Bennemann.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94" /><?php
						// translators: %s is a link to Spritz Veneziano
						printf(
							esc_html__( 'Rafael dedicated most of his adult life to helping people and companies take their ideas to the web, first as a developer and now as a Customer Advocate at GravityKit. He will do his best to help you too, all the while sipping a %s in Northern Italy, where he currently lives with his family.', 'gk-gravityview' ),
							'<a href="https://en.wikipedia.org/wiki/Spritz_Veneziano">Spritz Veneziano</a>'
						);
					?></p>
				</div>

				<div class='col'>
					<h3><?php esc_html_e( 'Casey Burridge', 'gk-gravityview' ); ?></h3>
					<h4 style='font-weight:0; margin-top:0'><?php esc_html_e( 'Content Creator', 'gk-gravityview' ); ?></h4>
					<p><img alt="<?php esc_attr_e( 'Casey Burridge', 'gk-gravityview' ); ?>" class="alignleft avatar" src="<?php echo plugins_url( 'assets/images/team/Casey.jpg', GRAVITYVIEW_FILE ); ?>" width="94" height="94"/><?php esc_html_e( 'Casey is GravityKit&rsquo;s resident content creator. He&rsquo;s been a WordPress lover ever since launching his first blog more than 6 years ago. Casey has lived and worked in London and Beijing, but feels most at home in Cape Town, South Africa, where he&rsquo;s originally from.', 'gk-gravityview' ); ?></p>
				</div>

				<div class='col'>
					<h3><?php esc_html_e( 'Doeke Norg', 'gk-gravityview' ); ?> <a href="https://x.com/doekenorg" title="<?php esc_attr_e( 'Follow Doeke on X', 'gk-gravityview' ); ?>"><span class="dashicons dashicons-twitter"></span></a> <a href="https://doeken.org/" title="<?php esc_attr_e( 'View Doeke&rsquo;s website', 'gk-gravityview' ); ?>"><span class="dashicons dashicons-admin-site"></span></a></h3>
					<h4 style='font-weight:0; margin-top:0'><?php esc_html_e( 'Senior Developer', 'gk-gravityview' ); ?></h4>
					<p><?php esc_html_e( 'With almost 20 years of experience in PHP, there are few things Doeke doesn&rsquo;t know about our favourite programming language. He lives with his family in The Netherlands, and spends his time designing elaborate, but maintainable code. He also writes a blog about software design in PHP.', 'gk-gravityview' ); ?></p>
				</div>
			</div>

			<hr class="clear" />

			<div class="feature-section">
				<h2><?php esc_html_e( 'Contributors', 'gk-gravityview' ); ?></h2>

				<h4><?php esc_html_e( 'Development', 'gk-gravityview' ); ?></h4>
				<ul class="ul-disc">
					<li>Core &amp; Add-On development by <a href='https://mrcasual.com' class='block'>Vlad K.</a>, <a href='https://malayladu.com' class='block'>Malay Ladu</a>, <a href='https://katz.co' class='block'>Zack Katz</a>, <a href="https://codeseekah.com" class="block">Gennady Kovshenin</a>, <a href='https://tinygod.pt' class='block'>Luis Godinho</a></li>
					<li>Code contributions by <a href="https://github.com/ryanduff">@ryanduff</a>, <a href="https://github.com/dmlinn">@dmlinn</a>, <a href="https://github.com/mgratch">@mgratch</a>, <a href="https://github.com/ViewFromTheBox">@ViewFromTheBox</a>, <a href="https://github.com/stevehenty">@stevehenty</a>, <a href="https://github.com/naomicbush">@naomicbush</a>, <a href='https://github.com/mrcasual'>@mrcasual</a> and <a href="https://github.com/rafaehlers">@rafaehlers</a></li>
					<li>Accessibility contributions by <a href="https://github.com/RianRietveld">@RianRietveld</a></li>
				</ul>

				<h4><?php esc_html_e( 'Translations', 'gk-gravityview' ); ?></h4>
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

				<h3><?php esc_html_e( 'Want to contribute?', 'gk-gravityview' ); ?></h3>
				<p><?php
					// translators: %1$s and %2$s are the opening and closing tags for the GitHub link
					printf(
						esc_html__( 'If you want to contribute to the code, %1$syou can on Github%2$s. If your contributions are accepted, you will be thanked here.', 'gk-gravityview' ),
						'<a href="https://github.com/gravityview/GravityView">',
						'</a>'
					);
				?></p>
			</div>

			<hr class="clear" />

			<div class="changelog">

				<h3><?php esc_html_e( 'Thanks to the following open-source software:', 'gk-gravityview' ); ?></h3>

				<ul class="ul-disc">
					<li><?php
						// translators: %1$s is a link to DataTables
						printf(
							esc_html__( '%1$s - amazing tool for table data display. Many thanks!', 'gk-gravityview' ),
							'<a href="https://datatables.net/">DataTables</a>'
						);
					?></li>
					<li><?php
						// translators: %1$s is a link to Flexibility
						printf(
							esc_html__( '%1$s - Adds support for CSS flexbox to Internet Explorer 8 &amp; 9', 'gk-gravityview' ),
							'<a href="https://github.com/10up/flexibility">Flexibility</a>'
						);
					?></li>
					<li><?php
						// translators: %1$s is a link to Gamajo Template Loader
						printf(
							esc_html__( '%1$s - makes it easy to load template files with user overrides', 'gk-gravityview' ),
							'<a href="https://github.com/GaryJones/Gamajo-Template-Loader">Gamajo Template Loader</a>'
						);
					?></li>
					<li><?php
						// translators: %1$s is a link to jQuery Cookie plugin
						printf(
							esc_html__( '%1$s - Access and store cookie values with jQuery', 'gk-gravityview' ),
							'<a href="https://github.com/carhartl/jquery-cookie">jQuery Cookie plugin</a>'
						);
					?></li>
					<li><?php
						// translators: %1$s is a link to Gravity Forms
						printf(
							esc_html__( '%1$s - If Gravity Forms weren\'t such a great plugin, GravityView wouldn\'t exist!', 'gk-gravityview' ),
							'<a href="https://www.gravitykit.com/gravityforms">Gravity Forms</a>'
						);
					?></li>
					<li><?php esc_html_e( 'GravityView uses icons made by Freepik, Adam Whitcroft, Amit Jakhu, Zurb, Scott de Jonge, Yannick, Picol, Icomoon, TutsPlus, Dave Gandy, SimpleIcon from www.flaticon.com', 'gk-gravityview' ); ?></li>
					<li><?php
						// translators: %s is a link to vecteezy.com
						printf(
							esc_html__( 'GravityView uses free vector art by %s', 'gk-gravityview' ),
							'<a href="https://www.vecteezy.com">vecteezy.com</a>'
						);
					?></li>
					<li><?php
						// translators: %1$s is a link to PHPEnkoder
						printf(
							esc_html__( '%1$s script encodes the email addresses.', 'gk-gravityview' ),
							'<a href="https://github.com/jnicol/standalone-phpenkoder">PHPEnkoder</a>'
						);
					?></li>
					<li><?php esc_html_e( 'The Duplicate View functionality is based on the excellent Duplicate Post plugin by Enrico Battocchi', 'gk-gravityview' ); ?></li>
					<li><?php
						// translators: %s is a link to BrowserStack
						printf(
							esc_html__( 'Browser testing by %s', 'gk-gravityview' ),
							'<a href="https://www.browserstack.com">BrowserStack</a>'
						);
					?></li>
					<li><?php
						// translators: %s is a link to Easy Digital Downloads
						printf(
							esc_html__( '%s makes auto-upgrades possible', 'gk-gravityview' ),
							'<a href="https://easydigitaldownloads.com/downloads/software-licensing/">Easy Digital Downloads</a>'
						);
					?></li>
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
		if ( 'plugin-editor.php' === $plugin_page ) {
			return; }

		// Bail if no activation redirect
		if ( ! get_transient( '_gv_activation_redirect' ) ) {
			return;
		}

		if ( ( $_GET['page'] ?? '' ) === GravityKit\GravityView\Foundation\Licenses\Framework::ID ) {
			return;
		}

		// Delete the redirect transient
		delete_transient( '_gv_activation_redirect' );

		$upgrade = get_option( 'gv_version_upgraded_from' );

		// Don't do anything if they've already seen the new version info
		if ( GV_PLUGIN_VERSION === $upgrade ) {
			return;
		}

		// Add "Upgraded From" Option
		update_option( 'gv_version_upgraded_from', GV_PLUGIN_VERSION );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return; }

		// First time install
		if ( ! $upgrade ) {
			wp_safe_redirect( admin_url( 'admin.php?page=gv-getting-started' ) );
			exit;
		}
		// Update
		else {
			wp_safe_redirect( admin_url( 'admin.php?page=gv-changelog' ) );
			exit;
		}
	}
}
new GravityView_Welcome();
