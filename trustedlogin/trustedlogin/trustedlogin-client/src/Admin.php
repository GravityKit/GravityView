<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 18-June-2021 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */
/**
 * Class Admin
 *
 * @package GravityView\TrustedLogin\Client
 *
 * @copyright 2021 Katz Web Services, Inc.
 */
namespace GravityView\TrustedLogin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \WP_User;
use \WP_Admin_Bar;

final class Admin {

	/**
	 * URL pointing to the "About TrustedLogin" page, shown below the Grant Access dialog
	 */
	const ABOUT_TL_URL = 'https://www.trustedlogin.com/about/easy-and-safe/';

	const ABOUT_LIVE_ACCESS_URL = 'https://www.trustedlogin.com/about/live-access/';

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @var SiteAccess $site_access
	 */
	private $site_access;

	/**
	 * @var SupportUser $support_user
	 */
	private $support_user;

	/**
	 * @var null|Logging $logging
	 */
	private $logging;

	/**
	 * Admin constructor.
	 *
	 * @param Config $config
	 */
	public function __construct( Config $config, Logging $logging ) {
		$this->config       = $config;
		$this->logging      = $logging;
		$this->site_access  = new SiteAccess( $config, $logging );
		$this->support_user = new SupportUser( $config, $logging );
	}


	public function init() {
		add_action( 'trustedlogin/' . $this->config->ns() . '/button', array( $this, 'generate_button' ), 10, 2 );
		add_action( 'trustedlogin/' . $this->config->ns() . '/users_table', array(
			$this,
			'output_support_users'
		), 20 );
		add_action( 'trustedlogin/' . $this->config->ns() . '/auth_screen', array( $this, 'print_auth_screen' ), 20 );
		add_filter( 'user_row_actions', array( $this, 'user_row_action_revoke' ), 10, 2 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_add_toolbar_items' ), 100 );

		if ( $this->config->get_setting( 'menu' ) ) {
			$menu_priority = $this->config->get_setting( 'menu/priority', 100 );
			add_action( 'admin_menu', array( $this, 'admin_menu_auth_link_page' ), $menu_priority );
		}

		if ( $this->config->get_setting( 'register_assets', true ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
		}

		add_action( 'trustedlogin/' . $this->config->ns() . '/admin/access_revoked', array( $this, 'admin_notices' ) );
	}

	/**
	 * Filter: Update the actions on the users.php list for our support users.
	 *
	 * @since 0.3.0
	 *
	 * @param array $actions
	 * @param WP_User $user_object
	 *
	 * @return array
	 */
	public function user_row_action_revoke( $actions, $user_object ) {

		if ( ! current_user_can( $this->support_user->role->get_name() ) && ! current_user_can( 'delete_users' ) ) {
			return $actions;
		}

		$revoke_url = $this->support_user->get_revoke_url( $user_object );

		if ( ! $revoke_url ) {
			return $actions;
		}

		$actions = array(
			'revoke' => "<a class='trustedlogin tl-revoke submitdelete' href='" . esc_url( $revoke_url ) . "'>" . esc_html__( 'Revoke Access', 'trustedlogin' ) . '</a>',
		);

		return $actions;
	}

	/**
	 * Register the required scripts and styles
	 *
	 * @since 0.2.0
	 */
	public function register_assets() {

		$registered = array();

		$registered['trustedlogin-js'] = wp_register_script(
			'trustedlogin-' . $this->config->ns(),
			$this->config->get_setting( 'paths/js' ),
			array(),
			Client::VERSION,
			true
		);

		$registered['trustedlogin-css'] = wp_register_style(
			'trustedlogin-' . $this->config->ns(),
			$this->config->get_setting( 'paths/css' ),
			array(),
			Client::VERSION,
			'all'
		);

		$registered_filtered = array_filter( $registered );

		if ( count( $registered ) !== count( $registered_filtered ) ) {
			$this->logging->log( 'Not all scripts and styles were registered: ' . print_r( $registered_filtered, true ), __METHOD__, 'error' );
		}

	}

	/**
	 * Adds a "Revoke TrustedLogin" menu item to the admin toolbar
	 *
	 * @param WP_Admin_Bar $admin_bar
	 *
	 * @return void
	 */
	public function admin_bar_add_toolbar_items( $admin_bar ) {

		if ( ! current_user_can( $this->support_user->role->get_name() ) ) {
			return;
		}

		if ( ! $admin_bar instanceof WP_Admin_Bar ) {
			return;
		}

		$icon = '<span style="
			height: 32px;
			width: 23px;
			margin: 0 1px;
			display: inline-block;
			vertical-align: top;
			background: url(\'data:image/svg+xml;base64,PHN2ZyBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAyNTAgMjUwIiB2aWV3Qm94PSIwIDAgMjUwIDI1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJtLTQ0NC42IDE0LjdjLTI2LjUgMC00OC4xIDIxLjYtNDguMSA0OC4xdjM5LjhoMjAuNnYtMzkuOGMwLTE1LjIgMTIuMy0yNy41IDI3LjUtMjcuNSAxNS4xIDAgMjcuNSAxMi4zIDI3LjUgMjcuNXYzOS44aDIwLjZ2LTM5LjhjMC0yNi42LTIxLjYtNDguMS00OC4xLTQ4LjF6IiBmaWxsPSIjMTA5OWQ2Ii8+PHBhdGggZD0ibS00NDQuNiA5MGMtMzguNSAwLTY5LjcgNC44LTY5LjcgMTAuOHY3OS44YzAgMzguNSA0Ny41IDU0LjggNjkuNyA1NC44czY5LjctMTYuMyA2OS43LTU0Ljh2LTc5LjhjLS4xLTYtMzEuMy0xMC44LTY5LjctMTAuOHoiIGZpbGw9IiMxYjJiNTkiLz48cGF0aCBkPSJtLTQ0NC42IDExMC4yYy0yMyAwLTQyLjUgMTUuMy00OC45IDM2LjJoMTQuOGM1LjgtMTMuMSAxOC45LTIyLjMgMzQuMS0yMi4zIDIwLjUgMCAzNy4yIDE2LjcgMzcuMiAzNy4ycy0xNi43IDM3LjItMzcuMiAzNy4yYy0xNS4yIDAtMjguMy05LjItMzQuMS0yMi4zaC0xNC44YzYuNCAyMC45IDI1LjkgMzYuMiA0OC45IDM2LjIgMjguMiAwIDUxLjEtMjIuOSA1MS4xLTUxLjEtLjEtMjguMi0yMy01MS4xLTUxLjEtNTEuMXoiIGZpbGw9IiNmZmYiLz48cGF0aCBkPSJtLTQyNSAxNTktMjguMy0xNi40Yy0yLjItMS4zLTQtLjItNCAyLjN2OS44aC01Ni45djEzaDU2Ljl2OS44YzAgMi41IDEuOCAzLjYgNCAyLjNsMjguMy0xNi40YzIuMi0xLjEgMi4yLTMuMSAwLTQuNHoiIGZpbGw9IiNmZmYiLz48cGF0aCBkPSJtMTI1IDIuMWMtMjkuNSAwLTUzLjYgMjQtNTMuNiA1My42djQ0LjRoMjN2LTQ0LjRjMC0xNi45IDEzLjctMzAuNiAzMC42LTMwLjZzMzAuNiAxMy43IDMwLjYgMzAuNnY0NC40aDIzdi00NC40YzAtMjkuNS0yNC4xLTUzLjYtNTMuNi01My42eiIgZmlsbD0iIzEwOTlkNiIvPjxwYXRoIGQ9Im0xMjUgODZjLTQyLjggMC03Ny42IDUuNC03Ny42IDEydjg4LjhjMCA0Mi44IDUyLjkgNjEgNzcuNiA2MXM3Ny42LTE4LjIgNzcuNi02MXYtODguOGMwLTYuNi0zNC44LTEyLTc3LjYtMTJ6IiBmaWxsPSIjMWIyYjU5Ii8+PHBhdGggZD0ibTEyNSAxMDguNWMtMjUuNiAwLTQ3LjMgMTctNTQuNCA0MC4zaDE2LjRjNi40LTE0LjYgMjEtMjQuOSAzOC0yNC45IDIyLjggMCA0MS40IDE4LjYgNDEuNCA0MS40cy0xOC42IDQxLjQtNDEuNCA0MS40Yy0xNyAwLTMxLjYtMTAuMi0zOC0yNC45aC0xNi40YzcuMSAyMy4zIDI4LjggNDAuMyA1NC40IDQwLjMgMzEuNCAwIDU2LjktMjUuNSA1Ni45LTU2LjkgMC0zMS4xLTI1LjUtNTYuNy01Ni45LTU2Ljd6IiBmaWxsPSIjZmZmIi8+PHBhdGggZD0ibTE0Ni44IDE2Mi45LTMxLjYtMTguMmMtMi40LTEuNC00LjQtLjMtNC40IDIuNnYxMWgtNjMuNHYxNC41aDYzLjR2MTFjMCAyLjggMiA0IDQuNCAyLjZsMzEuNi0xOC4yYzIuNS0xLjYgMi41LTMuOSAwLTUuM3oiIGZpbGw9IiNmZmYiLz48dGV4dCB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtNjUxLjEwMjkgMzA5Ljk2MDMpIj48dHNwYW4gZmlsbD0iIzFiMmI1OSIgZm9udC1mYW1pbHk9Ik11c2VvU2Fucy05MDAiIGZvbnQtc2l6ZT0iNTIuODQ0NyIgeD0iMCIgeT0iMCI+VHJ1c3RlPC90c3Bhbj48dHNwYW4gZmlsbD0iIzFiMmI1OSIgZm9udC1mYW1pbHk9Ik11c2VvU2Fucy05MDAiIGZvbnQtc2l6ZT0iNTIuODQ0NyIgbGV0dGVyLXNwYWNpbmc9IjMiIHg9IjIwNS43IiB5PSIwIj5kPC90c3Bhbj48dHNwYW4gZmlsbD0iIzEwOTlkNiIgZm9udC1mYW1pbHk9Ik11c2VvU2Fucy01MDAiIGZvbnQtc2l6ZT0iNTIuODQ0NyIgbGV0dGVyLXNwYWNpbmc9Ii0zIiB4PSIyNDguOCIgeT0iMCI+TDwvdHNwYW4+PHRzcGFuIGZpbGw9IiMxMDk5ZDYiIGZvbnQtZmFtaWx5PSJNdXNlb1NhbnMtNTAwIiBmb250LXNpemU9IjUyLjg0NDciIHg9IjI3My40IiB5PSIwIj5vPC90c3Bhbj48dHNwYW4gZmlsbD0iIzEwOTlkNiIgZm9udC1mYW1pbHk9Ik11c2VvU2Fucy01MDAiIGZvbnQtc2l6ZT0iNTIuODQ0NyIgeD0iMzE2LjMiIHk9IjAiPmdpbjwvdHNwYW4+PC90ZXh0PjxwYXRoIGQ9Im0tNTQwLjcgNDcyLjZjLTI2LjUgMC00OC4xIDIxLjYtNDguMSA0OC4xdjM5LjhoMjAuNnYtMzkuOGMwLTE1LjIgMTIuMy0yNy41IDI3LjUtMjcuNSAxNS4xIDAgMjcuNSAxMi4zIDI3LjUgMjcuNXYzOS44aDIwLjZ2LTM5LjhjMC0yNi41LTIxLjYtNDguMS00OC4xLTQ4LjF6IiBmaWxsPSIjMTA5OWQ2Ii8+PHBhdGggZD0ibS01NDAuNyA1NDcuOWMtMzguNSAwLTY5LjcgNC44LTY5LjcgMTAuOHY3OS44YzAgMzguNSA0Ny41IDU0LjggNjkuNyA1NC44czY5LjctMTYuMyA2OS43LTU0Ljh2LTc5LjhjLS4xLTYtMzEuMy0xMC44LTY5LjctMTAuOHoiIGZpbGw9IiMxYjJiNTkiLz48cGF0aCBkPSJtLTU0MC43IDU2OC4xYy0yMyAwLTQyLjUgMTUuMy00OC45IDM2LjJoMTQuOGM1LjgtMTMuMSAxOC45LTIyLjMgMzQuMS0yMi4zIDIwLjUgMCAzNy4yIDE2LjcgMzcuMiAzNy4ycy0xNi43IDM3LjItMzcuMiAzNy4yYy0xNS4yIDAtMjguMy05LjItMzQuMS0yMi4zaC0xNC44YzYuNCAyMC45IDI1LjkgMzYuMiA0OC45IDM2LjIgMjguMiAwIDUxLjEtMjIuOSA1MS4xLTUxLjEtLjEtMjguMi0yMy01MS4xLTUxLjEtNTEuMXoiIGZpbGw9IiNmZmYiLz48cGF0aCBkPSJtLTUyMS4xIDYxNi45LTI4LjMtMTYuNGMtMi4yLTEuMy00LS4yLTQgMi4zdjkuOGgtNTYuOXYxM2g1Ni45djkuOGMwIDIuNSAxLjggMy42IDQgMi4zbDI4LjMtMTYuNGMyLjItMS4xIDIuMi0zLjEgMC00LjR6IiBmaWxsPSIjZmZmIi8+PHRleHQgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTQyNi41OTQ1IDY0OC43NTIxKSI+PHRzcGFuIGZpbGw9IiMxYjJiNTkiIGZvbnQtZmFtaWx5PSJNdXNlb1NhbnMtOTAwIiBmb250LXNpemU9IjEyMS4xNzA5IiBsZXR0ZXItc3BhY2luZz0iMSIgeD0iMCIgeT0iMCI+VFJVU1RFPC90c3Bhbj48dHNwYW4gZmlsbD0iIzFiMmI1OSIgZm9udC1mYW1pbHk9Ik11c2VvU2Fucy05MDAiIGZvbnQtc2l6ZT0iMTIxLjE3MDkiIGxldHRlci1zcGFjaW5nPSI2IiB4PSI0NzEuNyIgeT0iMCI+RDwvdHNwYW4+PHRzcGFuIGZpbGw9IiMxMDk5ZDYiIGZvbnQtZmFtaWx5PSJNdXNlb1NhbnMtNTAwIiBmb250LXNpemU9IjEyMS4xNzA5IiBsZXR0ZXItc3BhY2luZz0iLTIiIHg9IjU2OC4yIiB5PSIwIj5MPC90c3Bhbj48dHNwYW4gZmlsbD0iIzEwOTlkNiIgZm9udC1mYW1pbHk9Ik11c2VvU2Fucy01MDAiIGZvbnQtc2l6ZT0iMTIxLjE3MDkiIGxldHRlci1zcGFjaW5nPSIxIiB4PSI2MjkuNiIgeT0iMCI+T0dJTjwvdHNwYW4+PC90ZXh0Pjwvc3ZnPg==\') left center no-repeat;
			background-size: 22px 23px;
		"></span>';

		$admin_bar->add_menu( array(
			'id'    => 'tl-' . $this->config->ns() . '-revoke',
			'title' => $icon . esc_html__( 'Revoke TrustedLogin', 'trustedlogin' ),
			'href'  => $this->support_user->get_revoke_url( 'all', true ),
			'meta'  => array(
				'class' => 'tl-destroy-session',
			),
		) );
	}

	/**
	 * Generates the auth link page
	 *
	 * This simulates the addition of an admin submenu item with null as the menu location
	 *
	 * @since 0.5.0
	 *
	 * @return void
	 */
	public function admin_menu_auth_link_page() {

		$parent_slug = $this->config->get_setting( 'menu/slug', null );

		if ( empty( $parent_slug ) ) {
			return;
		}

		$ns = $this->config->ns();

		$slug = apply_filters( 'trustedlogin/' . $this->config->ns() . '/admin/grantaccess/slug', 'grant-' . $ns . '-access', $ns );

		$menu_title = $this->config->get_setting( 'menu/title', esc_html__( 'Grant Support Access', 'trustedlogin' ) );

		add_submenu_page(
			$parent_slug,
			$menu_title,
			$menu_title,
			'create_users',
			$slug,
			array( $this, 'print_auth_screen' ),
			$this->config->get_setting( 'menu/position', null )
		);
	}

	/**
	 * Outputs the TrustedLogin authorization screen
	 *
	 * @since 0.8.0
	 *
	 * @return void
	 */
	public function print_auth_screen() {
		echo $this->get_auth_screen();
	}

	/**
	 * Is this a login screen and should TrustedLogin override the login screen for the current namespace?
	 *
	 * @return bool
	 */
	private function is_login_screen() {
		return did_action( 'login_init' ) && isset( $_GET['ns'] ) && $_GET['ns'] === $this->config->ns();
	}

	/**
	 * Output the contents of the Auth Link Page in wp-admin
	 *
	 * @since 0.5.0
	 *
	 * @return string HTML of the Auth screen
	 */
	public function get_auth_screen() {

		wp_enqueue_style( 'trustedlogin-' . $this->config->ns() );

		$content = array(
			'ns'               => $this->config->ns(),
			'has_access_class' => $this->support_user->get_all() ? 'has-access' : 'grant-access',
			'notices'          => $this->get_notices_html(),
			'header'           => $this->get_header_html(),
			'intro'            => $this->get_intro(),
			'ref'			   => $this->get_reference_html(),
			'details'          => $this->get_details_html(),
			'button'           => $this->generate_button( 'size=hero&class=authlink button-primary', false ),
			'footer'           => $this->get_footer_html(),
		);

		$auth_form_template = $this->get_auth_form_template();

		$output = $this->prepare_output( $auth_form_template, $content );

		return $output . $this->get_script();
	}

	private function get_header_html() {

		if ( $this->is_login_screen() ) {
			return '';
		}

		$header_template = '
		<header class="tl-{{ns}}-auth__header">
			<div class="tl-{{ns}}-auth__logo">{{logo}}</div>
		</header>';

		$variables = array(
			'ns'   => $this->config->ns(),
			'logo' => $this->get_logo_html(),
		);

		return $this->prepare_output( $header_template, $variables );
	}

	private function get_reference_html() {
		$template =  '<div class="tl-{{ns}}-auth__ref"><p>{{site_url}}{{reference}}</p></div>';

		$content = array(
			'reference' => isset( $_GET['ref'] ) ? '<span class="tl-{{ns}}-auth__ref__middot">&middot;</span> Reference #' . $_GET['ref'] : '',
			'ns' => $this->config->ns(),
			'site_url' => esc_html( str_replace( array( 'https://', 'http://' ), '', get_site_url() ) ),
		);

		return $this->prepare_output( $template, $content );
	}

	/**
	 * @return mixed|void
	 */
	private function get_auth_form_template() {

		$auth_form_template = '
<div class="tl-{{ns}}-auth tl-{{ns}}-{{has_access_class}}">
	{{header}}
	<section class="tl-{{ns}}-auth__body">
		<h2 class="tl-{{ns}}-auth__intro">{{intro}}</h2>
		{{ref}}

		<div class="tl-{{ns}}-auth__details">
			{{details}}
		</div>
		<div class="tl-{{ns}}-auth__response" aria-live="assertive">
		</div>
		{{notices}}
		<div class="tl-{{ns}}-auth__actions">
			{{button}}
		</div>
	</section>
	<footer class="tl-{{ns}}-auth__footer">
		{{footer}}
	</footer>
</div>';

		/**
		 * Filter trustedlogin/template/auth
		 **
		 *
		 * @param string $output_template The Auth form HTML
		 * @param string $ns The namespace of the plugin initializing TrustedLogin.
		 **/
		$auth_form_template = apply_filters( 'trustedlogin/' . $this->config->ns() . '/template/auth', $auth_form_template, $this->config->ns() );

		return $auth_form_template;
	}

	private function get_intro() {

		$has_access = $this->support_user->get_all();

		if ( $has_access ) {
			foreach ( $has_access as $access ) {
				// translators: %1$s is replaced with the name of the software developer (e.g. "Acme Widgets"). %2$s is the amount of time remaining for access ("1 week")
				$intro = sprintf( esc_html__( '%1$s has site access that expires in %2$s.', 'trustedlogin' ), $this->config->get_display_name(), $this->support_user->get_expiration( $access, true ) );
			}
			return $intro;
		}

		if ( $this->is_login_screen() ) {
			// translators: %1$s is replaced with the name of the software developer (e.g. "Acme Widgets")
			$intro = sprintf( esc_html__( '%1$s would like support access to this site.', 'trustedlogin' ), '<a href="'. esc_url( $this->config->get_setting( 'vendor/website' ) ) . '">' . $this->config->get_display_name() . '</a>' );
		} else {
			// translators: %1$s is replaced with the name of the software developer (e.g. "Acme Widgets")
			$intro = sprintf( esc_html__( 'Grant %1$s access to this site.', 'trustedlogin' ), '<a href="' . esc_url( $this->config->get_setting( 'vendor/website' ) ) . '">' . $this->config->get_display_name() . '</a>' );
		}

		return $intro;
	}

	private function get_details_html() {

		$has_access = $this->support_user->get_all();

		// Has access
		if ( $has_access ) {

			$output_template = '{{users_table}}';

			$content = array(
				'users_table' => $this->output_support_users( false, array( 'current_url' => true ) ),
			);

			return $this->prepare_output( $output_template, $content );
		}

		$output_template = '
			<p><span class="dashicons dashicons-info-outline dashicons--small"></span> This will allow <strong>{{name}}</strong> to:</p>
			<div class="tl-{{ns}}-auth__roles">
				<h2><span class="dashicons dashicons-admin-users dashicons--large"></span>{{roles_summary}}</h2>
				{{caps}}
			</div>
			<div class="tl-{{ns}}-auth__expire">
				<h2><span class="dashicons dashicons-clock dashicons--large"></span>{{expire_summary}}{{expire_desc}}</h2>
			</div>
		';

		// translators: %1$s and %3$s are replaced with HTML tags. %2$s is the amount of time that the login will be active for (e.g. "1 week")
		$expire_summary = sprintf( esc_html__( 'Access this site for %s.', 'trustedlogin' ), '<strong>' . human_time_diff( 0, $this->config->get_setting( 'decay' ) ) . '</strong>' );
		$expire_desc    = '<small>' . sprintf( esc_html__( 'Access auto-expires in %s. You may revoke access at any time.', 'trustedlogin' ), human_time_diff( 0, $this->config->get_setting( 'decay' ) ) ) . '</small>';

		$ns          = $this->config->ns();
		$cloned_role = translate_user_role( ucfirst( $this->config->get_setting( 'role' ) ) );

		if ( array_filter( $this->config->get_setting( 'caps' ), array( $this->config, 'is_not_null' ) ) ) {
			$roles_summary = sprintf( esc_html__( 'Create a user with a role similar to %s.', 'trustedlogin' ), '<strong>' . $cloned_role . '</strong>' );
			$roles_summary .= sprintf( '<small class="tl-' . $ns . '-toggle" data-toggle=".tl-' . $ns . '-auth__role-container">%s <span class="dashicons dashicons--small dashicons-arrow-down-alt2"></span></small>', esc_html__( 'View role capabilities', 'trustedlogin' ) );
		} else {
			$roles_summary = sprintf( esc_html__( 'Create a user with a role of %s.', 'trustedlogin' ), '<strong>' . $cloned_role . '</strong>' );
		}

		$content = array(
			'ns'             => $ns,
			'name'           => $this->config->get_display_name(),
			'expire_summary' => $expire_summary,
			'expire_desc'    => $expire_desc,
			'roles_summary'  => $roles_summary,
			'caps'           => $this->get_caps_html(),
		);

		return $this->prepare_output( $output_template, $content );
	}

	/**
	 * Get role capabilities HTML the Auth form
	 *
	 * @return string Empty string if there are no caps defined. Otherwise, HTML of caps in lists.
	 */
	private function get_caps_html() {

		$added   = $this->config->get_setting( 'caps/add' );
		$removed = $this->config->get_setting( 'caps/remove' );

		$caps = '';
		$caps .= $this->get_caps_section( $added, __( 'Additional capabilities:', 'trustedlogin' ), 'dashicons-yes-alt' );
		$caps .= $this->get_caps_section( $removed, __( 'Removed capabilities:', 'trustedlogin' ), 'dashicons-dismiss' );

		if ( empty( $caps ) ) {
			return $caps;
		}

		return '<div class="tl-' . $this->config->ns() . '-auth__role-container hidden">' . $caps . '</div>';
	}

	/**
	 * Generate additional/removed capabilities sections
	 *
	 * @param array $caps_array Associative array of cap => reason why cap is set
	 * @param string $heading Text to show for the heading of the caps section
	 * @param string $dashicon CSS class for the specific dashicon
	 *
	 * @return string
	 */
	private function get_caps_section( $caps_array, $heading = '', $dashicon = '' ) {

		$caps_array = array_filter( (array) $caps_array, array( $this->config, 'is_not_null' ) );

		if ( empty( $caps_array ) ) {
			return '';
		}

		$output = '';
		$output .= '<div>';
		$output .= '<h3>' . esc_html( $heading ) . '</h3>';
		$output .= '<ul>';

		foreach ( (array) $caps_array as $cap => $reason ) {
			$dashicon = '<span class="dashicons ' . esc_attr( $dashicon ) . ' dashicons--small"></span>';
			$reason   = empty( $reason ) ? '' : '<small>' . esc_html( $reason ) . '</small>';
			$output   .= sprintf( '<li>%s<span class="code">%s</span>%s</li>', $dashicon, esc_html( $cap ), $reason );
		}

		$output .= '</ul>';
		$output .= '</div>';

		return $output;
	}

	private function get_script() {
		ob_start();
		?>
		<script>
			jQuery( document ).ready( function ( $ ) {
				$( '.tl-{{ns}}-toggle' ).on( 'click', function () {
					$( this ).find( '.dashicons' ).toggleClass( 'dashicons-arrow-down-alt2' ).toggleClass( 'dashicons-arrow-up-alt2' );
					$( $( this ).data( 'toggle' ) ).toggleClass( 'hidden' );
				} );
			} );
		</script>
		<?php
		$output = ob_get_clean();

		$content = array(
			'ns' => $this->config->ns(),
		);

		return $this->prepare_output( $output, $content, false );
	}

	private function get_notices_html() {

		if ( ! function_exists( 'wp_get_environment_type' ) ) {
			return '';
		}

		if ( in_array( wp_get_environment_type(), array( 'staging', 'production' ), true ) ) {
			return '';
		}

		$notice_template = '
		<div class="inline notice notice-alt notice-warning">
			<h3>{{local_site}}</h3>
			<p>{{need_access}} <a href="{{about_live_access_url}}" target="_blank" rel="noopener noreferrer">{{learn_more}}</a></p>
		</div>';

		$content = array(
			'local_site' => sprintf( esc_html__( '%s support may not be able to access this site.', 'trustedlogin' ), $this->config->get_setting( 'vendor/title' ) ),
			'need_access' => esc_html__( 'This website is running in a local development environment. To provide support, we must be able to access your site using a publicly-accessible URL.', 'trustedlogin' ),
			'about_live_access_url' => esc_url( $this->config->get_setting( 'vendor/about_live_access_url', self::ABOUT_LIVE_ACCESS_URL ) ),
			'opens_in_new_window' => esc_attr__( 'This link opens in a new window.', 'trustedlogin' ),
			'learn_more' => esc_html__( 'Learn more.', 'trustedlogin' ),
		);

		return $this->prepare_output( $notice_template, $content );
	}

	/**
	 * @return string
	 */
	private function get_logo_html() {

		$logo_url = $this->config->get_setting( 'vendor/logo_url' );

		$logo_output = '';

		if ( ! empty( $logo_url ) ) {
			$logo_output = sprintf(
				'<a href="%1$s" title="%2$s" target="_blank" rel="noreferrer noopener"><img src="%3$s" alt="%4$s" /></a>',
				esc_url( $this->config->get_setting( 'vendor/website' ) ),
				// translators: %s is replaced with the name of the software developer (e.g. "Acme Widgets")
				sprintf( 'Visit the %s website (opens in a new tab)', $this->config->get_setting( 'vendor/title' ) ),
				esc_attr( $this->config->get_setting( 'vendor/logo_url' ) ),
				esc_attr( $this->config->get_setting( 'vendor/title' ) )
			);
		}

		return $logo_output;
	}

	/**
	 * Returns the HTML for the footer in the Auth form
	 *
	 * @return string
	 */
	private function get_footer_html() {

		$footer_links = array(
			esc_html__( 'Learn about TrustedLogin', 'trustedlogin' )                    => self::ABOUT_TL_URL,
			sprintf( 'Visit %s support', $this->config->get_setting( 'vendor/title' ) ) => $this->config->get_setting( 'vendor/support_url' ),
		);

		/**
		 * Filter trustedlogin/template/auth/footer_links
		 *
		 * Used to add/remove Footer Links on grantlink page
		 *
		 * @since 0.5.0
		 *
		 * @param array Array of links to show in auth footer (Key is anchor text; Value is URL)
		 * @param string $ns Namespace of the plugin initializing TrustedLogin
		 **/
		$footer_links = apply_filters( 'trustedlogin/' . $this->config->ns() . '/template/auth/footer_links', $footer_links, $this->config->ns() );

		$footer_links_output = '';
		foreach ( $footer_links as $text => $link ) {
			$footer_links_output .= sprintf( '<li><a href="%1$s">%2$s</a></li>',
				esc_url( $link ),
				esc_html( $text )
			);
		}

		$footer_output = '';
		if ( ! empty( $footer_links_output ) ) {
			$footer_output = sprintf( '<ul>%1$s</ul>', $footer_links_output );
		}

		return $footer_output;
	}

	private function prepare_output( $template, $content, $wp_kses = true ) {

		$output_html = $template;

		foreach ( $content as $key => $value ) {
			$output_html = str_replace( '{{' . $key . '}}', $value, $output_html );
		}

		if ( $wp_kses ) {

			// Allow SVGs for logos
			$allowed_protocols   = wp_allowed_protocols();
			$allowed_protocols[] = 'data';

			$output_html = wp_kses( $output_html, array(
				'a'       => array(
					'class'       => array(),
					'id'          => array(),
					'href'        => array(),
					'title'       => array(),
					'rel'         => array(),
					'target'      => array(),
					'data-toggle' => array(),
					'data-access' => array(),
				),
				'img'     => array(
					'class' => array(),
					'id'    => array(),
					'src'   => array(),
					'href'  => array(),
					'alt'   => array(),
					'title' => array(),
				),
				'span'    => array(
					'class'       => array(),
					'id'          => array(),
					'title'       => array(),
					'data-toggle' => array()
				),
				'label'   => array( 'class' => array(), 'id' => array(), 'for' => array() ),
				'code'    => array( 'class' => array(), 'id' => array() ),
				'tt'      => array( 'class' => array(), 'id' => array() ),
				'pre'     => array( 'class' => array(), 'id' => array() ),
				'table'   => array( 'class' => array(), 'id' => array() ),
				'thead'   => array(),
				'tfoot'   => array(),
				'td'      => array( 'class' => array(), 'id' => array(), 'colspan' => array() ),
				'th'      => array( 'class' => array(), 'id' => array(), 'colspan' => array(), 'scope' => array() ),
				'ul'      => array( 'class' => array(), 'id' => array() ),
				'li'      => array( 'class' => array(), 'id' => array() ),
				'p'       => array( 'class' => array(), 'id' => array() ),
				'h1'      => array( 'class' => array(), 'id' => array() ),
				'h2'      => array( 'class' => array(), 'id' => array() ),
				'h3'      => array( 'class' => array(), 'id' => array() ),
				'h4'      => array( 'class' => array(), 'id' => array() ),
				'h5'      => array( 'class' => array(), 'id' => array() ),
				'div'     => array( 'class' => array(), 'id' => array(), 'aria-live' => array() ),
				'small'   => array( 'class' => array(), 'id' => array(), 'data-toggle' => array() ),
				'header'  => array( 'class' => array(), 'id' => array() ),
				'footer'  => array( 'class' => array(), 'id' => array() ),
				'section' => array( 'class' => array(), 'id' => array() ),
				'br'      => array(),
				'strong'  => array(),
				'em'      => array(),
				'input'   => array(
					'class'     => array(),
					'id'        => array(),
					'type'      => array( 'text' ),
					'value'     => array(),
					'size'      => array(),
					'aria-live' => array(),
				),
				'button'  => array( 'class' => array(), 'id' => array(), 'aria-live' => array() ),
			),
				$allowed_protocols
			);
		}

		return normalize_whitespace( $output_html );
	}

	/**
	 * Output the TrustedLogin Button and required scripts
	 *
	 * @since 0.2.0
	 *
	 * @param array $atts {@see get_button()} for configuration array
	 * @param bool $print Should results be printed and returned (true) or only returned (false)
	 *
	 * @return string the HTML output
	 */
	public function generate_button( $atts = array(), $print = true ) {

		if ( ! current_user_can( 'create_users' ) ) {
			return '';
		}

		if ( ! wp_script_is( 'trustedlogin-' . $this->config->ns(), 'registered' ) ) {
			$this->logging->log( 'JavaScript is not registered. Make sure `trustedlogin` handle is added to "no-conflict" plugin settings.', __METHOD__, 'error' );
		}

		if ( ! wp_style_is( 'trustedlogin-' . $this->config->ns(), 'registered' ) ) {
			$this->logging->log( 'Style is not registered. Make sure `trustedlogin` handle is added to "no-conflict" plugin settings.', __METHOD__, 'error' );
		}

		wp_enqueue_style( 'trustedlogin-' . $this->config->ns() );

		$button_settings = array(
			'vendor'       => $this->config->get_setting( 'vendor' ),
			'ajaxurl'      => admin_url( 'admin-ajax.php' ),
			'_nonce'       => wp_create_nonce( 'tl_nonce-' . get_current_user_id() ),
			'lang'         => $this->translations(),
			'debug'        => $this->logging->is_enabled(),
			'selector'     => '.button-trustedlogin-' . $this->config->ns(),
			'reference_id' => self::get_reference_id(),
			'query_string' => esc_url( remove_query_arg( array(
				Endpoint::REVOKE_SUPPORT_QUERY_PARAM,
				'_wpnonce'
			) ) ),
		);

		// TODO: Add data to tl_obj when detecting that it's already been localized by another vendor
		wp_localize_script( 'trustedlogin-' . $this->config->ns(), 'tl_obj', $button_settings );

		wp_enqueue_script( 'trustedlogin-' . $this->config->ns() );

		$return = $this->get_button( $atts );

		if ( $print ) {
			echo $return;
		}

		return $return;
	}

	/**
	 * Generates HTML for a TrustedLogin Grant Access button
	 *
	 * @param array $atts {
	 *   @type string $text Button text to grant access. Sanitized using esc_html(). Default: "Grant %s Access"
	 *                      (%s replaced with vendor/title setting)
	 *   @type string $exists_text Button text when vendor already has a support account. Sanitized using esc_html().
	 *                      Default: "Extend %s Access" (%s replaced with vendor/title setting)
	 *   @type string $size WordPress CSS button size. Options: 'small', 'normal', 'large', 'hero'. Default: "hero"
	 *   @type string $class CSS class added to the button. Default: "button-primary"
	 *   @type string $tag Tag used to display the button. Options: 'a', 'button', 'span'. Default: "a"
	 *   @type bool   $powered_by Whether to display the TrustedLogin badge on the button. Default: true
	 *   @type string $support_url The URL to use as a backup if JavaScript fails or isn't available. Sanitized using
	 *                      esc_url(). Default: `vendor/support_url` configuration setting URL.
	 * }
	 *
	 * @return string
	 */
	public function get_button( $atts = array() ) {

		$defaults = array(
			'text'        => sprintf( esc_html__( 'Grant %s Access', 'trustedlogin' ), $this->config->get_display_name() ),
			'exists_text' => sprintf( esc_html__( 'Extend %s Access', 'trustedlogin' ), $this->config->get_display_name(), ucwords( human_time_diff( time(), time() + $this->config->get_setting( 'decay' ) ) ) ),
			'size'        => 'hero',
			'class'       => 'button-primary',
			'tag'         => 'a', // "a", "button", "span"
			'powered_by'  => true,
			'support_url' => $this->config->get_setting( 'vendor/support_url' ),
		);

		$sizes = array( 'small', 'normal', 'large', 'hero' );

		$atts = wp_parse_args( $atts, $defaults );

		switch ( $atts['size'] ) {
			case '':
				$css_class = '';
				break;
			case 'normal':
				$css_class = 'button';
				break;
			default:
				if ( ! in_array( $atts['size'], $sizes ) ) {
					$atts['size'] = 'hero';
				}

				$css_class = 'button button-' . $atts['size'];
		}

		$_valid_tags = array( 'a', 'button', 'span' );

		if ( ! empty( $atts['tag'] ) && in_array( strtolower( $atts['tag'] ), $_valid_tags, true ) ) {
			$tag = $atts['tag'];
		} else {
			$tag = 'a';
		}

		$data_atts = array();

		if ( $this->support_user->get_all() ) {
			$text                = '<span class="dashicons dashicons-update-alt"></span>' . esc_html( $atts['exists_text'] );
			$href                = admin_url( 'users.php?role=' . $this->support_user->role->get_name() );
			$data_atts['access'] = 'extend';
		} else {
			$text                = esc_html( $atts['text'] );
			$href                = $atts['support_url'];
			$data_atts['access'] = 'grant';
		}

		$css_class = implode( ' ', array( $css_class, $atts['class'] ) );
		$css_class = trim( $css_class );

		$data_string = '';
		foreach ( $data_atts as $key => $value ) {
			$data_string .= sprintf( ' data-%s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		$powered_by = '';
		if ( $atts['powered_by'] ) {
			$powered_by = sprintf( '<small><span class="trustedlogin-logo"></span>%s</small>',
				esc_html__( 'Secured by TrustedLogin', 'trustedlogin' )
			);
		}

		$anchor_html = $text . $powered_by;

		return sprintf(
			'<%1$s href="%2$s" class="%3$s button-trustedlogin-%4$s" aria-role="button" %5$s>%6$s</%1$s>',
			/* %1$s */ $tag,
			/* %2$s */ esc_url( $href ),
			/* %3$s */ esc_attr( $css_class ),
			/* %4$s */ $this->config->ns(),
			/* %5$s */ $data_string,
			/* %6$s */ $anchor_html
		);
	}

	/**
	 * Helper function: Build translate-able strings for alert messages
	 *
	 * @since 0.4.3
	 *
	 * @return array of Translations and strings to be localized to JS variables
	 */
	public function translations() {

		$vendor_title = $this->config->get_setting( 'vendor/title' );

		/**
		 * Filter: Allow for adding into GET parameters on support_url
		 *
		 * @since 0.4.3
		 *
		 * ```
		 * $url_query_args = [
		 *   'message' => (string) What error should be sent to the support system.
		 * ];
		 * ```
		 *
		 * @param array $url_query_args {
		 *
		 * @type string $message What error should be sent to the support system.
		 * }
		 */
		$query_args = apply_filters( 'trustedlogin/' . $this->config->ns() . '/support_url/query_args', array(
				'message' => __( 'Could not create TrustedLogin access.', 'trustedlogin' )
			)
		);

		$error_content = sprintf( '<p>%s</p><p>%s</p>',
			sprintf(
				esc_html__( 'The user details could not be sent to %1$s automatically.', 'trustedlogin' ),
				$vendor_title
			),
			sprintf(
				__( 'Please <a href="%1$s" target="_blank">click here</a> to go to the %2$s support site', 'trustedlogin' ),
				esc_url( add_query_arg( $query_args, $this->config->get_setting( 'vendor/support_url' ) ) ),
				$vendor_title
			)
		);

		$translations = array(
			'buttons' => array(
				'confirm'    => esc_html__( 'Confirm', 'trustedlogin' ),
				'ok'         => esc_html__( 'Ok', 'trustedlogin' ),
				'go_to_site' => sprintf( __( 'Go to %1$s support site', 'trustedlogin' ), $vendor_title ),
				'close'      => esc_html__( 'Close', 'trustedlogin' ),
				'cancel'     => esc_html__( 'Cancel', 'trustedlogin' ),
				'revoke'     => sprintf( __( 'Revoke %1$s support access', 'trustedlogin' ), $vendor_title ),
				'copy'       => __( 'Copy', 'trustedlogin' ),
				'copied'     => __( 'Copied!', 'trustedlogin' ),
			),
			'status'  => array(
				'synced'             => array(
					'title'   => esc_html__( 'Support access granted', 'trustedlogin' ),
					'content' => sprintf(
						__( 'A temporary support user has been created, and sent to %1$s support.', 'trustedlogin' ),
						$vendor_title
					),
				),
				'pending'            => array(
					'content' => sprintf( __( 'Generating & encrypting secure support access for %1$s', 'trustedlogin' ), $vendor_title ),
				),
				'extending'          => array(
					'content' => sprintf( __( 'Extending support access for %1$s by %2$s', 'trustedlogin' ), $vendor_title, human_time_diff( time(), time() + $this->config->get_setting( 'decay' ) ) ),
				),
				'syncing'            => array(
					'content' => sprintf( __( 'Sending encrypted access to %1$s.', 'trustedlogin' ), $vendor_title ),
				),
				'error'              => array(
					'title'   => sprintf( __( 'Error syncing support user to %1$s', 'trustedlogin' ), $vendor_title ),
					'content' => wp_kses( $error_content, array(
						'a' => array(
							'href'   => array(),
							'rel'    => array(),
							'target' => array()
						),
						'p' => array()
					) ),
				),
				'cancel'             => array(
					'title'   => esc_html__( 'Action Cancelled', 'trustedlogin' ),
					'content' => sprintf(
						__( 'A support account for %1$s was not created.', 'trustedlogin' ),
						$vendor_title
					),
				),
				'failed'             => array(
					'title'   => esc_html__( 'Support Access Was Not Granted', 'trustedlogin' ),
					'content' => esc_html__( 'There was an error granting access: ', 'trustedlogin' ),
				),
				'failed_permissions' => array(
					'content' => esc_html__( 'Your authorized session has expired. Please refresh the page.', 'trustedlogin' ),
				),
				'accesskey'          => array(
					'title'       => esc_html__( 'TrustedLogin Key Created', 'trustedlogin' ),
					'content'     => sprintf(
						__( 'Share this TrustedLogin Key with %1$s to give them secure access:', 'trustedlogin' ),
						$vendor_title
					),
					'revoke_link' => esc_url( add_query_arg( array( Endpoint::REVOKE_SUPPORT_QUERY_PARAM => $this->config->ns() ), admin_url() ) ),
				),
				'error404'           => array(
					'title'   => esc_html__( 'The TrustedLogin vendor could not be found.', 'trustedlogin' ),
					'content' => '',
				),
				'error409'           => array(
					'title'   => sprintf(
						__( '%1$s Support user already exists', 'trustedlogin' ),
						$vendor_title
					),
					'content' => sprintf(
						wp_kses(
							__( 'A support user for %1$s already exists. You can revoke this support access from your <a href="%2$s" target="_blank">Users list</a>.', 'trustedlogin' ),
							array( 'a' => array( 'href' => array(), 'target' => array() ) )
						),
						$vendor_title,
						esc_url( admin_url( 'users.php?role=' . $this->support_user->role->get_name() ) )
					),
				),
			),
		);

		return $translations;
	}

	/**
	 * Outputs table of created support users
	 *
	 * @since 0.2.1
	 *
	 * @param bool $print Whether to print and return (true) or return (false) the results. Default: true
	 * @param array $atts Settings for the table. {
	 *   @type bool $current_url Whether to generate Revoke links based on the current URL. Default: false.
	 * }
	 *
	 * @return string HTML table of active support users for vendor. Empty string if current user can't `create_users`
	 */
	public function output_support_users( $print = true, $atts = array() ) {

		if ( ! is_admin() || ! current_user_can( 'create_users' ) ) {
			return '';
		}

		// The `trustedlogin/{$ns}/button` action passes an empty string
		if ( '' === $print ) {
			$print = true;
		}

		$support_users = $this->support_user->get_all();

		if ( empty( $support_users ) ) {

			$return = '<h3>' . sprintf( esc_html__( 'No %s users exist.', 'trustedlogin' ), $this->config->get_setting( 'vendor/title' ) ) . '</h3>';

			if ( $print ) {
				echo $return;
			}

			return $return;
		}

		$default_atts = array(
			'current_url' => false,
		);

		$atts = wp_parse_args( $atts, $default_atts );

		$return = '';

		$access_key_output = sprintf(
			'<%6$s class="tl-%1$s-auth__accesskey">
				<label>
					<h2>%2$s</h2>
					<input type="text" value="%4$s" size="33" class="tl-%1$s-auth__accesskey_field code" aria-label="%3$s">
				</label>
				<button id="tl-%1$s-copy" class="tl-%1$s-auth__accesskey_copy button button button-outline" aria-live="polite">%5$s</button>
			</%6$s>',
			/* %1$s */ sanitize_title( $this->config->ns() ),
			/* %2$s */ esc_html__( 'Site access key:', 'trustedlogin' ),
			/* %3$s */ esc_html__( 'Access Key', 'trustedlogin' ),
			/* %4$s */ esc_attr( $this->site_access->get_access_key() ),
			/* %5$s */ esc_html__( 'Copy', 'trustedlogin' ),
			/* %6$s */ 'div'
		);

		$return .= $access_key_output;

		$return .= '<h2>' . sprintf( esc_html__( '%s users:', 'trustedlogin' ), $this->config->get_setting( 'vendor/title' ) ) . '</h2>';
		$return .= '<table class="wp-list-table widefat plugins">';

		$table_header =
			sprintf( '
                <thead>
                    <tr>
                        <th scope="col">%1$s</th>
                        <th scope="col">%2$s</th>
                        <th scope="col">%3$s</th>
                        <th scope="col">%4$s</td>
                        <th scope="col">%5$s</th>
                    </tr>
                </thead>',
				esc_html__( 'User', 'trustedlogin' ),
				esc_html__( 'Created', 'trustedlogin' ),
				esc_html__( 'Expires', 'trustedlogin' ),
				esc_html__( 'Created By', 'trustedlogin' ),
				esc_html__( 'Revoke Access', 'trustedlogin' )
			);

		$return .= $table_header;

		$return .= '<tbody>';

		foreach ( $support_users as $support_user ) {

			$_user_creator_id = get_user_option( $this->support_user->created_by_meta_key, $support_user->ID );
			$_user_creator    = $_user_creator_id ? get_user_by( 'id', $_user_creator_id ) : false;

			$return .= '<tr>';
			$return .= '<th scope="row">';
			$return .= '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $support_user->ID ) ) . '">';
			$return .= sprintf( '%s (#%d)', esc_html( $support_user->display_name ), $support_user->ID );
			$return .= '</a>';
			$return .= '</th>';

			$return .= '<td>' . sprintf( esc_html__( '%s ago', 'trustedlogin' ), human_time_diff( strtotime( $support_user->user_registered ) ) ) . '</td>';
			$return .= '<td>' . sprintf( esc_html__( 'In %s', 'trustedlogin' ), human_time_diff( get_user_option( $this->support_user->expires_meta_key, $support_user->ID ) ) ) . '</td>';

			if ( $_user_creator && $_user_creator->exists() ) {
				$return .= '<td>' . ( $_user_creator->exists() ? esc_html( $_user_creator->display_name ) : sprintf( esc_html__( 'Unknown (User #%d)', 'trustedlogin' ), $_user_creator_id ) ) . '</td>';
			} else {
				$return .= '<td>' . esc_html__( 'Unknown', 'trustedlogin' ) . '</td>';
			}

			if ( $revoke_url = $this->support_user->get_revoke_url( $support_user, $atts['current_url'] ) ) {
				$return .= '<td><a class="trustedlogin tl-revoke submitdelete" href="' . esc_url( $revoke_url ) . '">' . esc_html__( 'Revoke Access', 'trustedlogin' ) . '</a></td>';
			} else {
				$return .= '<td><a href="' . esc_url( admin_url( 'users.php?role=' . $this->support_user->role->get_name() ) ) . '">' . esc_html__( 'Manage from Users list', 'trustedlogin' ) . '</a></td>';
			}
			$return .= '</tr>';

		}

		$return .= '</tbody></table>';

		if ( $print ) {
			echo $return;
		}


		return $return;
	}

	/**
	 * Add admin_notices hooks
	 *
	 * @return void
	 */
	public function admin_notices() {
		add_action( 'admin_notices', array( $this, 'admin_notice_revoked' ) );
	}

	/**
	 * Notice: Shown when a support user is manually revoked by admin;
	 *
	 * @return void
	 */
	public function admin_notice_revoked() {

		static $displayed_notice;

		// Only show notice once
		if ( $displayed_notice ) {
			return;
		}

		?>
		<div class="notice notice-success is-dismissible">
			<h3><?php echo esc_html( sprintf( __( '%s access revoked.', 'trustedlogin' ), $this->config->get_setting( 'vendor/title' ) ) ); ?></h3>
			<?php if ( ! current_user_can( 'delete_users' ) ) { ?>
				<p><?php echo esc_html__( 'You may safely close this window.', 'trustedlogin' ); ?></p>
			<?php } ?>
		</div>
		<?php

		$displayed_notice = true;
	}

	/**
	 * @return string|null
	 */
	public static function get_reference_id() {

		if ( ! isset( $_GET['ref'] ) ) {
			return null;
		}

		return esc_html( $_GET['ref'] );
	}
}
