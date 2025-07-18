<?php
/**
 * Add Gravity Forms scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-plugin-hooks-gravity-forms.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15.2
 */

/**
 * @inheritDoc
 * @since 1.15.2
 */
class GravityView_Plugin_Hooks_Gravity_Forms extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * The query arg to identify the view.
	 *
	 * @since TODO
	 *
	 * @var string
	 */
	const QUERY_ARG_VIEW_ID = 'gvid';

	/**
	 * The nonce query arg.
	 *
	 * @since TODO
	 *
	 * @var string
	 */
	const QUERY_ARG_NONCE = 'gvnonce';

	/**
	 * The nonce action.
	 *
	 * @since TODO
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'gvdownload';

	/**
	 * @inheritDoc
	 *
	 * @since TODO
	 *
	 * @var string
	 */
	public $class_name = 'GFForms';

	/**
	 * @inheritDoc
	 *
	 * @since 1.15.2
	 *
	 * @var array
	 */
	protected $style_handles = array(
		'gform_tooltip',
		'gform_font_awesome',
		'gform_admin_icons',
	);

	/**
	 * @inheritDoc
	 *
	 * @since 1.15.2
	 *
	 * @var array
	 */
	protected $script_handles = array(
		'gform_tooltip_init',
		'gform_field_filter',
		'gform_forms',
	);

	/**
	 * @inheritDoc
	 *
	 * @since TODO
	 */
	public function __construct() {
		parent::__construct();

		if ( self::is_gf_gv_download() ) {
			add_filter( 'nocache_headers', [ $this, 'remove_nocache_headers_from_gf_download' ], 1000 );
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @since TODO
	 */
	public function add_hooks() {
		parent::add_hooks();

		add_action( 'gravityview/template/before', [ $this, 'add_query_arg_to_gf_download_url' ] );
	}

	/**
	 * Checks if the current request is a Gravity Forms download coming from GravityView.
	 *
	 * @since TODO
	 *
	 * @return bool
	 */
	private static function is_gf_gv_download() {

		$is_download    = ! empty( $_GET['gf-download'] );
		$nonce          = \GV\Utils::get( $_GET, self::QUERY_ARG_NONCE, '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_nonce_valid = $nonce && wp_verify_nonce( $nonce, self::NONCE_ACTION );

		return $is_download && $is_nonce_valid;
	}

	/**
	 * Adds a query arg to the Gravity Forms download URL to identify the View.
	 *
	 * This allows us to only remove the cache headers for GravityView embedded files.
	 *
	 * @param \GV\Template_Context $context The context object.
	 *
	 * @since TODO
	 */
	public function add_query_arg_to_gf_download_url() {
		/**
		 * Adds the View ID to the Gravity Forms download URL.
		 *
		 * @param string $url The existing Gravity Forms download URL.
		 *
		 * @return string The new Gravity Forms download URL, with the View ID added.
		 */
		add_filter( 'gform_secure_file_download_url', function( $url ) {
			return wp_nonce_url( $url, self::NONCE_ACTION, self::QUERY_ARG_NONCE );
		} );
	}

	/**
	 * Remove cache headers for Gravity Forms downloads.
	 *
	 * @param array $headers
	 *
	 * @since TODO
	 *
	 * @return array
	 */
	public static function remove_nocache_headers_from_gf_download( $headers ) {
		// Sanity check (this shouldn't be called if it's not already a GF download from GV).
		if ( ! self::is_gf_gv_download() ) {
			return $headers;
		}

		// Nonces are valid for 24-48 hours.
		$max_age = DAY_IN_SECONDS * 2;

		// Add caching headers to allow caching for as long as the nonce is valid.
		$headers['Cache-Control'] = 'max-age=' . $max_age . ', public, immutable';
		$headers['Expires'] = gmdate( 'D, d M Y H:i:s', time() + $max_age ) . ' GMT';

		return $headers;
	}

}

new GravityView_Plugin_Hooks_Gravity_Forms();
