<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */
/**
 * Class Admin
 *
 * @package GravityKit\GravityView\Foundation\ThirdParty\TrustedLogin\Client
 *
 * @copyright 2021 Katz Web Services, Inc.
 */
namespace GravityKit\GravityView\Foundation\ThirdParty\TrustedLogin;

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
		add_action( 'login_form_trustedlogin', array( $this, 'maybe_print_request_screen' ), 20 );
		add_filter( 'user_row_actions', array( $this, 'user_row_action_revoke' ), 10, 2 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_add_toolbar_items' ), 100 );

		if ( $this->config->get_setting( 'menu' ) ) {
			$menu_priority = $this->config->get_setting( 'menu/priority', 100 );
			add_action( 'admin_menu', array( $this, 'admin_menu_auth_link_page' ), $menu_priority );
		}

		if ( $this->config->get_setting( 'register_assets', true ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
			add_action( 'login_enqueue_scripts', array( $this, 'register_assets' ) );
		}

		add_action( 'trustedlogin/' . $this->config->ns() . '/admin/access_revoked', array( $this, 'admin_notices' ) );
	}

	/**
	 * Filter: Update the actions on the users.php list for our support users.
	 *
	 * @since 1.0.0
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

		return array(
			'revoke' => "<a class='trustedlogin tl-revoke submitdelete' href='" . esc_url( $revoke_url ) . "'>" . esc_html__( 'Revoke Access', 'gk-gravityview' ) . '</a>',
		);
	}

	/**
	 * Register the required scripts and styles
	 *
	 * @since 1.0.0
	 */
	public function register_assets() {

		$registered = array();

		$registered['trustedlogin-js'] = wp_register_script(
			'trustedlogin-' . $this->config->ns(),
			$this->config->get_setting( 'paths/js' ),
			array( 'jquery', 'wp-a11y' ),
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

		$is_user_active = $this->support_user->is_active();

		if ( ! $is_user_active ) {
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
			'title' => $icon . esc_html__( 'Revoke TrustedLogin', 'gk-gravityview' ),
			'href'  => $this->support_user->get_revoke_url( 'all' ),
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
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function admin_menu_auth_link_page() {

		$parent_slug = $this->config->get_setting( 'menu/slug', null );

		// When false, there will be no menus added.
		if ( false === $parent_slug ) {
			return;
		}

		$ns = $this->config->ns();

		$menu_slug = apply_filters( 'trustedlogin/' . $this->config->ns() . '/admin/menu/menu_slug', 'grant-' . $ns . '-access' );

		$menu_title = $this->config->get_setting( 'menu/title', esc_html__( 'Grant Support Access', 'gk-gravityview' ) );

		// If empty (null or empty string), add top-level menu
		if ( empty( $parent_slug ) ) {

			add_menu_page(
				$menu_title,
				$menu_title,
				'create_users',
				$menu_slug,
				array( $this, 'print_auth_screen' ),
				$this->config->get_setting( 'menu/icon_url', '' ),
				$this->config->get_setting( 'menu/position', null )
			);

			return;
		}

		add_submenu_page(
			$parent_slug,
			$menu_title,
			$menu_title,
			'create_users',
			$menu_slug,
			array( $this, 'print_auth_screen' ),
			$this->config->get_setting( 'menu/position', null )
		);
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
	 * If the current request is a valid login screen override, print the TrustedLogin request screen.
	 *
	 * @return void
	 */
	public function maybe_print_request_screen() {

		if ( ! $this->is_login_screen() ) {
			return;
		}

		// Once logged-in, take user back to auth request screen.
		if ( ! is_user_logged_in() ) {
			$_REQUEST['redirect_to'] = site_url( add_query_arg( array() ) );
			return;
		}

		if ( ! current_user_can( 'create_users' ) ) {
			return;
		}

		$this->print_request_screen();
	}

	public function print_request_screen() {
		global $interim_login, $wp_version;

		// Don't output a "← Back to site" link on the login page
		$interim_login = true;

		// The login_headertitle filter was deprecated in WP 5.2.0 for login_headertext
		if( version_compare( $wp_version, '5.2.0', '<' ) ) {
			add_filter( 'login_headertitle', '__return_empty_string' );
		} else {
			add_filter( 'login_headertext', '__return_empty_string' );
		}

		add_filter( 'login_headerurl', function () {
			return $this->config->get_setting( 'vendor/website' );
		});

		login_header();

		wp_enqueue_style( 'common');

		wp_add_inline_style( 'common', $this->get_login_inline_css() );

		echo $this->get_auth_screen();

		login_footer();

		die();
	}

	/**
	 * Returns inline CSS overrides for the `common` CSS dependency
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function get_login_inline_css() {
		return '
#login {
	width: auto;
}
.login .button-primary {
	float: none;
}
.login h1 {
	margin-top: 36px;
}
.login h1 a {
	background-image: url("' . $this->config->get_setting( 'vendor/logo_url' ). '")!important;
	background-size: contain!important;
}
';
	}

	/**
	 * Outputs the TrustedLogin authorization screen
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function print_auth_screen() {
		echo $this->get_auth_screen();
	}

	public function get_auth_header_html() {
		$support_users = $this->support_user->get_all();

		if ( empty( $support_users ) ) {
			return '';
		}

		$support_user = $support_users[0];

		$_user_creator_id = get_user_option( $this->support_user->created_by_meta_key, $support_user->ID );
		$_user_creator    = $_user_creator_id ? get_user_by( 'id', $_user_creator_id ) : false;

		$unknown_user_text = sprintf( esc_html__( 'Unknown (User #%d)', 'gk-gravityview' ), $_user_creator_id );

		$auth_meta = ( $_user_creator && $_user_creator->exists() ) ? esc_html( $_user_creator->display_name ) : $unknown_user_text;

		$revoke_url = $this->support_user->get_revoke_url( $support_user );

		$template = '
		{{revoke_access_button}}
		<h3>{{display_name}}</h3>
		<span class="tl-{{ns}}-auth__meta">{{auth_meta}}</span>';

		$content = array(
			'display_name' => $support_user->display_name,
			'revoke_access_button' => sprintf( '<a href="%s" class="button button-danger alignright tl-client-revoke-button">%s</a>', $revoke_url, esc_html__( 'Revoke Access', 'gk-gravityview' ) ),
			// translators: %s is the display name of the user who granted access
			'auth_meta' => sprintf( esc_html__( 'Created %s ago by %s', 'gk-gravityview' ), human_time_diff( strtotime( $support_user->user_registered ) ), $auth_meta ),
		);

		return $this->prepare_output( $template, $content );
	}

	/**
	 * Output the contents of the Auth Link Page in wp-admin
	 *
	 * @since 1.0.0
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
			'auth_header'      => $this->get_auth_header_html(),
			'details'          => $this->get_details_html(),
			'button'           => $this->generate_button( 'size=hero&class=authlink button-primary tl-client-grant-button', false ),
			'secured_by_trustedlogin' => '<span class="trustedlogin-logo-large"></span>' . esc_html__( 'Secured by TrustedLogin', 'gk-gravityview' ),
			'footer'           => $this->get_footer_html(),
			'reference'        => $this->get_reference_html(),
		);

		$auth_screen_template = '
		<div class="tl-{{ns}}-auth tl-{{ns}}-{{has_access_class}}">
			{{header}}
			<section class="tl-{{ns}}-auth__body">
				<h2 class="tl-{{ns}}-auth__intro">{{intro}}</h2>
				<div class="tl-{{ns}}-auth__content">
					<header class="tl-{{ns}}-auth__header">
						{{auth_header}}
					</header>
					<div class="tl-{{ns}}-auth__details">
						{{details}}
					</div>
					<div class="tl-{{ns}}-auth__response" aria-live="assertive"></div>
					{{notices}}
					<div class="tl-{{ns}}-auth__actions">
						{{button}}
					</div>
				</div>
				<div class="tl-{{ns}}-auth__secured_by">{{secured_by_trustedlogin}}</div>
			</section>
			<footer class="tl-{{ns}}-auth__footer">
				{{footer}}
				{{reference}}
			</footer>
		</div>';

		/**
		 * Filter trustedlogin/{ns}/template/auth
		 *
		 * @param string $output_template The Auth form HTML
		 */
		$auth_screen_template = apply_filters( 'trustedlogin/' . $this->config->ns() . '/template/auth', $auth_screen_template );

		$output = $this->prepare_output( $auth_screen_template, $content );

		return $output;
	}

	private function get_header_html() {

		if ( $this->is_login_screen() ) {
			return '';
		}

		$header_template = '
		<header class="tl-{{ns}}-auth__header__top">
			<div class="tl-{{ns}}-auth__logo">{{logo}}</div>
		</header>';

		$variables = array(
			'ns'   => $this->config->ns(),
			'logo' => $this->get_logo_html(),
		);

		return $this->prepare_output( $header_template, $variables );
	}

	/**
	 * Shows the current site URL and, if passed as $_GET['ref'], a support reference ID
	 *
	 * @return string Empty string if there is no reference or if the `trustedlogin/{ns}/template/auth/display_reference` filter returns false.
	 */
	private function get_reference_html() {

		$reference_id = Client::get_reference_id();

		if ( null === $reference_id ) {
			return '';
		}

		/**
		 * Filter trustedlogin/{ns}/template/auth/display_reference
		 *
		 * Used to hide or show the reference ID in the auth screen template.
		 *
		 * @since 1.3
		 *
		 * @param bool $display_reference Whether to display the reference ID on the auth screen. Default: true.
		 * @param bool $is_login_screen Whether the auth form is being displayed on the login screen.
		 * @param string $ref The reference ID.
		 */
		$display_reference = apply_filters( 'trustedlogin/' . $this->config->ns() . '/template/auth/display_reference', true, $this->is_login_screen(), $reference_id );

		if ( ! $display_reference ) {
			return '';
		}

		$template =  '<div class="tl-{{ns}}-auth__ref"><p><span class="tl-{{ns}}-auth_ref__id">{{reference_text}}</span></p></div>';

		$content = array(
			// translators: %s is the reference ID
			'reference_text' => sprintf( esc_html__( 'Reference #%s', 'gk-gravityview' ), $reference_id ),
			'ns' => $this->config->ns(),
			'site_url' => esc_html( str_replace( array( 'https://', 'http://' ), '', get_site_url() ) ),
		);

		return $this->prepare_output( $template, $content );
	}

	private function get_intro() {

		$has_access = $this->support_user->get_all();

		if ( $has_access ) {
			foreach ( $has_access as $access ) {
				// translators: %1$s is replaced with the name of the software developer (e.g. "Acme Widgets"). %2$s is the amount of time remaining for access ("1 week")
				$intro = sprintf( esc_html__( '%1$s has site access that expires in %2$s.', 'gk-gravityview' ), '<a href="' . esc_url( $this->config->get_setting('vendor/website') ) . '" target="_blank" rel="noopener noreferrer">' . $this->config->get_setting( 'vendor/title' ) . '</a>', str_replace( ' ', '&nbsp;', $this->support_user->get_expiration( $access, true, false ) ) );
			}

			return $intro;
		}

		if ( $this->is_login_screen() ) {
			// translators: %1$s is replaced with the name of the software developer (e.g. "Acme Widgets")
			$intro = sprintf( esc_html__( '%1$s would like support access to this site.', 'gk-gravityview' ), '<a href="'. esc_url( $this->config->get_setting( 'vendor/website' ) ) . '">' . $this->config->get_display_name() . '</a>' );
		} else {
			// translators: %1$s is replaced with the name of the software developer (e.g. "Acme Widgets")
			$intro = sprintf( esc_html__( 'Grant %1$s access to this site.', 'gk-gravityview' ), '<a href="' . esc_url( $this->config->get_setting( 'vendor/website' ) ) . '">' . $this->config->get_display_name() . '</a>' );
		}

		return $intro;
	}

	private function get_details_html() {

		$has_access = $this->support_user->get_all();

		// Has access
		if ( $has_access ) {
			$output_template = '';
			$output_template .= '{{users_table}}';
			$content = array(
				'users_table' => $this->output_support_users( false, array( 'current_url' => true ) ),
			);

			return $this->prepare_output( $output_template, $content, false );
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

		// translators: %s is replaced with the of time that the login will be active for (e.g. "1 week")
		$expire_summary = sprintf( esc_html__( 'Access this site for %s.', 'gk-gravityview' ), '<strong>' . human_time_diff( 0, $this->config->get_setting( 'decay' ) ) . '</strong>' );

		// translators: %s is replaced by the amount of time that the login will be active for (e.g. "1 week")
		$expire_desc    = '<small>' . sprintf( esc_html__( 'Access auto-expires in %s. You may revoke access at any time.', 'gk-gravityview' ), human_time_diff( 0, $this->config->get_setting( 'decay' ) ) ) . '</small>';

		$ns          = $this->config->ns();
		$cloned_role = translate_user_role( ucfirst( $this->config->get_setting( 'role' ) ) );

		if ( $this->config->get_setting( 'caps/add' ) || $this->config->get_setting( 'caps/remove' ) ) {
			// translators: %s is replaced with the name of the role being cloned (e.g. "Administrator")
			$roles_summary = sprintf( esc_html__( 'Create a user with a role similar to %s.', 'gk-gravityview' ), '<strong>' . $cloned_role . '</strong>' );
			$roles_summary .= sprintf( '<small class="tl-' . $ns . '-toggle" data-toggle=".tl-' . $ns . '-auth__role-container">%s <span class="dashicons dashicons--small dashicons-arrow-down-alt2"></span></small>', esc_html__( 'View role capabilities', 'gk-gravityview' ) );
		} else {
			// translators: %s is replaced with the name of the role (e.g. "Administrator")
			$roles_summary = sprintf( esc_html__( 'Create a user with a role of %s.', 'gk-gravityview' ), '<strong>' . $cloned_role . '</strong>' );
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
		$caps .= $this->get_caps_section( $added, __( 'Additional capabilities:', 'gk-gravityview' ), 'dashicons-yes-alt' );
		$caps .= $this->get_caps_section( $removed, __( 'Removed capabilities:', 'gk-gravityview' ), 'dashicons-dismiss' );

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

	/**
	 * Generates HTML for notices about current server environment perhaps not being accessible.
	 * @return string
	 */
	private function get_notices_html() {

		if ( ! function_exists( 'wp_get_environment_type' ) ) {
			return '';
		}

		if ( in_array( wp_get_environment_type(), array( 'staging', 'production' ), true ) ) {
			return '';
		}

		if ( defined( 'TRUSTEDLOGIN_DISABLE_LOCAL_NOTICE' ) && TRUSTEDLOGIN_DISABLE_LOCAL_NOTICE ) {
			return '';
		}

		$notice_template = '
		<div class="inline notice notice-alt notice-warning">
			<h3>{{local_site}}</h3>
			<p>{{need_access}} <a href="{{about_live_access_url}}" target="_blank" rel="noopener noreferrer">{{learn_more}}</a></p>
		</div>';

		$content = array(
			// translators: %s is replaced with the name of the software developer (e.g. "Acme Widgets")
			'local_site' => sprintf( esc_html__( '%s support may not be able to access this site.', 'gk-gravityview' ), $this->config->get_setting( 'vendor/title' ) ),
			'need_access' => esc_html__( 'This website is running in a local development environment. To provide support, we must be able to access your site using a publicly-accessible URL.', 'gk-gravityview' ),
			'about_live_access_url' => esc_url( $this->config->get_setting( 'vendor/about_live_access_url', self::ABOUT_LIVE_ACCESS_URL ) ),
			'learn_more' => esc_html__( 'Learn more.', 'gk-gravityview' ),
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

		$support_url = $this->config->get_setting( 'vendor/support_url' );

		if ( $reference_id = Client::get_reference_id() ) {

			$support_args = array(
				'tl'  => Client::VERSION,
				'ref' => $reference_id,
				'ns'  => $this->config->ns(),
			);

			$support_url = add_query_arg( $support_args, $support_url );
		}

		$footer_links = array(
			esc_html__( 'Learn about TrustedLogin', 'gk-gravityview' )                   => self::ABOUT_TL_URL,
			// translators: %s is replaced with the name of the software developer (e.g. "Acme Widgets")
			sprintf( 'Visit %s support', $this->config->get_setting( 'vendor/title' ) ) => $support_url,
		);

		/**
		 * Filter trustedlogin/{ns}/template/auth/footer_links
		 *
		 * Used to add/remove Footer Links on grantlink page
		 *
		 * @since 1.0.0
		 *
		 * @param array $footer_links Array of links to show in auth footer (Key is anchor text; Value is URL)
		 */
		$footer_links = apply_filters( 'trustedlogin/' . $this->config->ns() . '/template/auth/footer_links', $footer_links );

		$footer_links_output = '';
		foreach ( $footer_links as $text => $link ) {
			$footer_links_output .= sprintf( '<li><a href="%1$s" target="_blank">%2$s</a></li>',
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
					'data-toggle' => array(),
					'style'       => array(),
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
				'h3'      => array( 'class' => array(), 'id' => array(), 'style'       => array(), ),
				'h4'      => array( 'class' => array(), 'id' => array() ),
				'h5'      => array( 'class' => array(), 'id' => array() ),
				'div'     => array( 'class' => array(), 'id' => array(), 'aria-live' => array(), 'style' => array(), ),
				'small'   => array( 'class' => array(), 'id' => array(), 'data-toggle' => array() ),
				'header'  => array( 'class' => array(), 'id' => array() ),
				'footer'  => array( 'class' => array(), 'id' => array() ),
				'section' => array( 'class' => array(), 'id' => array() ),
				'br'      => array(),
				'strong'  => array(),
				'em'      => array(),
				'input'  => array(
					'class'     => array(),
					'id'        => array(),
					'type'      => array( 'text' ),
					'value'     => array(),
					'size'      => array(),
					'aria-live' => array(),
					'aria-label' => array(),
					'style'     => array(),
				),
				'button' => array(
					'class'     => array(),
					'id'        => array(),
					'aria-live' => array(),
					'style'     => array(),
					'title'     => array(),
				),
			),
				$allowed_protocols
			);
		}

		return normalize_whitespace( $output_html );
	}

	/**
	 * Output the TrustedLogin Button and required scripts
	 *
	 * @since 1.0.0
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
			'reference_id' => Client::get_reference_id(),
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
			// translators: %s is replaced with the name of the software developer (e.g. "Acme Widgets")
			'text'        => sprintf( esc_html__( 'Grant %s Access', 'gk-gravityview' ), $this->config->get_display_name() ),
			// translators: %s is replaced with the name of the software developer (e.g. "Acme Widgets")
			'exists_text' => sprintf( esc_html__( 'Extend %s Access', 'gk-gravityview' ), $this->config->get_display_name(), ucwords( human_time_diff( time(), time() + $this->config->get_setting( 'decay' ) ) ) ),
			'size'        => 'hero',
			'class'       => 'button-primary',
			'tag'         => 'a', // "a", "button", "span"
			'powered_by'  => false,
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
			$text                = '<span class="dashicons dashicons-update-alt dashicons--small"></span> ' . esc_html( $atts['exists_text'] );
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
				esc_html__( 'Secured by TrustedLogin', 'gk-gravityview' )
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
	 * @since 1.0.0
	 *
	 * @return array of Translations and strings to be localized to JS variables
	 */
	public function translations() {

		$vendor_title = $this->config->get_setting( 'vendor/title' );

		/**
		 * Filter: Allow for adding into GET parameters on support_url
		 *
		 * @since 1.0.0
		 *
		 * ```
		 * $url_query_args = [
		 *   'message' => (string) What error should be sent to the support system.
		 * ];
		 * ```
		 *
		 * @param array $url_query_args {
		 * @type string $message What error should be sent to the support system.
		 * @type string|null $ref A sanitized reference ID, if passed. Otherwise, null.
		 * }
		 */
		$query_args = apply_filters( 'trustedlogin/' . $this->config->ns() . '/support_url/query_args', array(
				'message' => __( 'Could not create TrustedLogin access.', 'gk-gravityview' ),
				'ref' => Client::get_reference_id(),
			)
		);

		$error_content = sprintf( '<p>%s</p><p>%s</p>',
			sprintf(
				// translators: %s is replaced with the name of the software developer (e.g. "Acme Widgets")
				esc_html__( 'The user details could not be sent to %1$s automatically.', 'gk-gravityview' ),
				$vendor_title
			),
			sprintf(
				// translators: %1$s is the vendor support url and %2$s is the vendor title
				__( 'Please <a href="%1$s" target="_blank">click here</a> to go to the %2$s support site', 'gk-gravityview' ),
				esc_url( add_query_arg( $query_args, $this->config->get_setting( 'vendor/support_url' ) ) ),
				$vendor_title
			)
		);

		$translations = array(
			'buttons' => array(
				'confirm'    => esc_html__( 'Confirm', 'gk-gravityview' ),
				'ok'         => esc_html__( 'Ok', 'gk-gravityview' ),
				// translators: %1$s is the vendor title
				'go_to_site' => sprintf( __( 'Go to %1$s support site', 'gk-gravityview' ), $vendor_title ),
				'close'      => esc_html__( 'Close', 'gk-gravityview' ),
				'cancel'     => esc_html__( 'Cancel', 'gk-gravityview' ),
				// translators: %1$s is the vendor title
				'revoke'     => sprintf( esc_html__( 'Revoke %1$s support access', 'gk-gravityview' ), $vendor_title ),
				'copy'       => esc_html__( 'Copy', 'gk-gravityview' ),
				'copied'     => esc_html__( 'Copied!', 'gk-gravityview' ),
			),
			'a11y' => array(
				'opens_new_window' => esc_attr__( '(This link opens in a new window.)', 'gk-gravityview' ),
				'copied_text' =>  esc_html__( 'The access key has been copied to your clipboard.', 'gk-gravityview' ),
			),
			'status'  => array(
				'synced'             => array(
					'title'   => esc_html__( 'Support access granted', 'gk-gravityview' ),
					'content' => sprintf(
					// translators: %1$s is the vendor title
						__( 'A temporary support user has been created, and sent to %1$s support.', 'gk-gravityview' ),
						$vendor_title
					),
				),
				'pending'            => array(
					// translators: %1$s is the vendor title
					'content' => sprintf( __( 'Generating & encrypting secure support access for %1$s', 'gk-gravityview' ), $vendor_title ),
				),
				'extending'          => array(
					// translators: %1$s is the vendor title and %2$s is the human-readable expiration time (for example, "1 week")
					'content' => sprintf( __( 'Extending support access for %1$s by %2$s', 'gk-gravityview' ), $vendor_title, human_time_diff( time(), time() + $this->config->get_setting( 'decay' ) ) ),
				),
				'syncing'            => array(
					// translators: %1$s is the vendor title
					'content' => sprintf( __( 'Sending encrypted access to %1$s.', 'gk-gravityview' ), $vendor_title ),
				),
				'error'              => array(
					'title'   => sprintf( __( 'Error syncing support user to %1$s', 'gk-gravityview' ), $vendor_title ),
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
					'title'   => esc_html__( 'Action Cancelled', 'gk-gravityview' ),
					'content' => sprintf(
						// translators: %1$s is the vendor title
						__( 'A support account for %1$s was not created.', 'gk-gravityview' ),
						$vendor_title
					),
				),
				'failed'             => array(
					'title'   => esc_html__( 'Support Access Was Not Granted', 'gk-gravityview' ),
					'content' => esc_html__( 'There was an error granting access: ', 'gk-gravityview' ),
				),
				'failed_permissions' => array(
					'content' => esc_html__( 'Your authorized session has expired. Please refresh the page.', 'gk-gravityview' ),
				),
				'accesskey'          => array(
					'title'       => esc_html__( 'TrustedLogin Key Created', 'gk-gravityview' ),
					'content'     => sprintf(
						// translators: %1$s is the vendor title
						__( 'Share this TrustedLogin Key with %1$s to give them secure access:', 'gk-gravityview' ),
						$vendor_title
					),
					'revoke_link' => esc_url( add_query_arg( array( Endpoint::REVOKE_SUPPORT_QUERY_PARAM => $this->config->ns() ), admin_url() ) ),
				),
				'error404'           => array(
					'title'   => esc_html__( 'The TrustedLogin vendor could not be found.', 'gk-gravityview' ),
					'content' => '',
				),
				'error409'           => array(
					'title'   => sprintf(
						// translators: %1$s is the vendor title
						__( '%1$s Support user already exists', 'gk-gravityview' ),
						$vendor_title
					),
					'content' => sprintf(
						wp_kses(
							// translators: %1$s is the vendor title, %2$s is the URL to the users list page
							__( 'A support user for %1$s already exists. You may revoke this support access from your <a href="%2$s" target="_blank">Users list</a>.', 'gk-gravityview' ),
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
	 * @since 1.0.0
	 *
	 * @param bool $print Whether to print and return (true) or return (false) the results. Default: true
	 * @param array $atts Settings for the table. {
	 *   @type bool $current_url Whether to generate Revoke links based on the current URL. Default: false.
	 * }
	 *
	 * @return string HTML table of active support users for vendor. Empty string if current user can't `create_users`
	 */
	public function output_support_users( $print = true, $atts = array() ) {

		if ( ( ! is_admin() && ! $this->is_login_screen() ) || ! current_user_can( 'create_users' ) ) {
			return '';
		}

		// The `trustedlogin/{$ns}/button` action passes an empty string
		if ( '' === $print ) {
			$print = true;
		}

		$support_users = $this->support_user->get_all();

		if ( empty( $support_users ) ) {

			// translators: %s is replaced with the name of the software developer (e.g. "Acme Widgets")
			$return = '<h3>' . sprintf( esc_html__( 'No %s users exist.', 'gk-gravityview' ), $this->config->get_setting( 'vendor/title' ) ) . '</h3>';

			if ( $print ) {
				echo $return;
			}

			return $return;
		}

		$return = '';

		$access_key = $this->site_access->get_access_key();

		if ( is_wp_error( $access_key ) ) {

			$access_key_template = <<<EOD
<%3\$s class="tl-%1\$s-auth__accesskey">
	<h3>%2\$s</h3>
	<p>%4\$s <samp>%5\$s</samp></p>
</%3\$s>
EOD;
			$access_key_output   = sprintf(
				$access_key_template,
				/* %1$s */ sanitize_title( $this->config->ns() ),
				/* %2$s */ esc_html__( 'Error', 'gk-gravityview' ),
				/* %3$s */ 'div',
				/* %4$s */ esc_html__( 'There was an error returning the access key.', 'gk-gravityview' ),
				/* %5$s */ esc_html( $access_key->get_error_message() )
			);

		} else {

			$access_key_template = <<<EOD
<%6\$s class="tl-%1\$s-auth__accesskey">
	<label for="tl-%1\$s-access-key"><h3>%2\$s</h3></label>
	<p>%8\$s</p>

	<div class="tl-%1\$s-auth__accesskey_wrapper">
		<input id="tl-%1\$s-access-key" type="text" value="%4\$s" size="64" class="tl-%1\$s-auth__accesskey_field code" aria-label="%3\$s">
		<button id="tl-%1\$s-copy" class="tl-%1\$s-auth__accesskey_copy button" aria-live="off" title="%7\$s"><span class="screen-reader-text">%5\$s</span></button>
	</div>
</%6\$s>
EOD;
			$access_key_output   = sprintf(
				$access_key_template,
				/* %1$s */ sanitize_title( $this->config->ns() ),
				/* %2$s */ esc_html__( 'Site access key:', 'gk-gravityview' ),
				/* %3$s */ esc_html__( 'Access Key', 'gk-gravityview' ),
				/* %4$s */ esc_attr( $access_key ),
				/* %5$s */ esc_html__( 'Copy', 'gk-gravityview' ),
				/* %6$s */ 'div',
				/* %7$s */ esc_html__( 'Copy the access key to your clipboard', 'gk-gravityview' ),
				// translators: %s is the display name of the TrustedLogin support user.
				/* %8$s */ sprintf( esc_html__( 'The access key is not a password; only %1$s will be able to access your site using this code. You may share this access key on support forums.', 'gk-gravityview' ), $this->support_user->get_first()->display_name )
			);

		}


		$return .= $access_key_output;

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
			<h3><?php
				// translators: %s is replaced with the name of the software developer (e.g. "Acme Widgets")
				echo esc_html( sprintf( __( '%s access revoked.', 'gk-gravityview' ), $this->config->get_setting( 'vendor/title' ) ) );
				?></h3>
			<?php if ( ! current_user_can( 'delete_users' ) ) { ?>
				<p><?php echo esc_html__( 'You may safely close this window.', 'gk-gravityview' ); ?></p>
			<?php } ?>
		</div>
		<?php

		$displayed_notice = true;
	}
}
