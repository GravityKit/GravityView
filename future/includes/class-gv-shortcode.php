<?php

namespace GV;

use GFCommon;
use GravityKitFoundation;
use GVCommon;
use Throwable;
use WP_Error;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The base \GV\Shortcode class.
 *
 * Contains some unitility methods, base class for all GV Shortcodes.
 */
class Shortcode {
	/**
	 * @var array All GravityView-registered and loaded shortcodes can be found here.
	 */
	private static $shortcodes;

	/**
	 * @var array The default attributes for this shortcode.
	 */
	protected static $defaults = [];

	/**
	 * @var array The parsed attributes of this shortcode.
	 */
	public $atts;

	/**
	 * @var string The parsed name of this shortcode.
	 */
	public $name;

	/**
	 * @var string The parsed content between tags of this shortcode.
	 */
	public $content;

	/**
	 * The WordPress Shortcode API callback for this shortcode.
	 *
	 * @param array  $atts    The attributes passed.
	 * @param string $content The content inside the shortcode.
	 * @param string $tag     The tag.
	 *
	 * @return string The output.
	 */
	public function callback( $atts, $content = '', $tag = '' ) {
		gravityview()->log->error(
			'[{shortcode}] shortcode {class}::callback method not implemented.',
			array(
				'shortcode' => $this->name,
				'class'     => get_class( $this ),
			)
		);

		return '';
	}

	/**
	 * Register this shortcode class with the WordPress Shortcode API.
	 *
	 * @internal
	 *
	 * @since develop
	 *
	 * @param string $name A shortcode name override. Default: self::$name.
	 *
	 * @return \GV\Shortcode|null The only internally registered instance of this shortcode, or null on error.
	 */
	public static function add( $name = null ) {
		$shortcode = new static();
		$name      = $name ? $name : $shortcode->name;
		if ( shortcode_exists( $name ) ) {
			if ( empty( self::$shortcodes[ $name ] ) ) {
				gravityview()->log->error( 'Shortcode [{shortcode}] has already been registered elsewhere.', array( 'shortcode' => $name ) );

				return null;
			}
		} else {
			add_shortcode( $name, array( $shortcode, 'callback' ) );
			self::$shortcodes[ $name ] = $shortcode;
		}

		return self::$shortcodes[ $name ];
	}

	/**
	 * Unregister this shortcode.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public static function remove() {
		$shortcode = new static();
		unset( self::$shortcodes[ $shortcode->name ] );
		remove_shortcode( $shortcode->name );
	}

	/**
	 * Parse a string of content and figure out which ones there are.
	 *
	 * Only registered shortcodes (via add_shortcode) will show up.
	 * Returned order is not guaranteed.
	 *
	 * @param string $content Some post content to search through.
	 *
	 * @internal
	 *
	 * @return \GV\Shortcode[] An array of \GV\Shortcode (and subclass) instances.
	 */
	public static function parse( $content ) {
		$shortcodes = array();

		/**
		 * The matches contains:
		 *
		 * 1 - An extra [ to allow for escaping shortcodes with double [[]]
		 * 2 - The shortcode name
		 * 3 - The shortcode argument list
		 * 4 - The self closing /
		 * 5 - The content of a shortcode when it wraps some content.
		 * 6 - An extra ] to allow for escaping shortcodes with double [[]]
		 */
		preg_match_all( '/' . get_shortcode_regex() . '/', $content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $shortcode ) {
			$shortcode_name = $shortcode[2];

			$shortcode_atts    = shortcode_parse_atts( $shortcode[3] );
			$shortcode_content = $shortcode[5];

			/** This is a registered GravityView shortcode. */
			if ( ! empty( self::$shortcodes[ $shortcode_name ] ) ) {
				$shortcode = clone self::$shortcodes[ $shortcode_name ];
			} else {
				/** This is some generic shortcode. */
				$shortcode       = new self();
				$shortcode->name = $shortcode_name;
			}

			$shortcode->atts    = $shortcode_atts;
			$shortcode->content = $shortcode_content;

			/** Merge inner shortcodes. */
			$shortcodes = array_merge( $shortcodes, array( $shortcode ), self::parse( $shortcode_content ) );
		}

		return $shortcodes;
	}

	/**
	 * Returns the View by the provided attributes.
	 *
	 * It will also handle security through the `secret` attribute.
	 *
	 * @since 2.21
	 *
	 * @param array $atts The attributes for the short code.
	 *
	 * @return View|WP_Error|null The view.
	 */
	protected function get_view_by_atts( array $atts ) {
		if ( ! isset( $atts['view_id'] ) ) {
			return null;
		}

		$view = View::by_id( $atts['view_id'] );
		if ( ! $view ) {
			return null;
		}

		$secret = rgar( $atts, 'secret', '' );

		if ( $view->validate_secret( $secret ) ) {
			return $view;
		}

		if ( GVCommon::has_cap( 'edit_gravityviews', $view->ID ) ) {
			return new WP_Error(
				'invalid_secret',
				sprintf(
					esc_html__( '%1$s: Invalid View secret provided. Update the shortcode with the secret: %2$s', 'gk-gravityview' ),
					'GravityView',
					'<code>secret="' . $view->get_validation_secret() . '"</code>'
				)
			);
		}

		// If the user can't edit the View, don't show the error message with the secret but display an admin notice.
		if ( class_exists( 'GravityKitFoundation' ) && GravityKitFoundation::notices() ) {
			$current_url = home_url( add_query_arg( null, null ) );
			$page_hash   = md5( strtok( $current_url, '?' ) );

			$shortcode     = gv_current_shortcode_tag();
			$shortcode_key = strtolower( preg_replace( '/[^a-z0-9_]+/i', '-', $shortcode ?: 'gravityview' ) );

			$title = get_the_title();

			if ( ! $title ) {
				$title = __( 'this page', 'gk-gravityview' );
			}

			$page_link = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $current_url ),
				esc_html( $title )
			);

			// Shortcode descriptor (safe fallback if missing).
			$shortcode_message = $shortcode
				? sprintf( '<strong>[%s]</strong> %s', esc_html( $shortcode ), esc_html__( 'shortcode', 'gk-gravityview' ) )
				: esc_html__( 'A GravityView shortcode', 'gk-gravityview' );

			$message_template = esc_html__(
				'[shortcode] on [page_link] is missing or has an invalid "secret" attribute.',
				'gk-gravityview'
			);

			$message = strtr( $message_template, [
				'[shortcode]' => $shortcode_message,
				'[page_link]' => $page_link,
			] );

			GravityKitFoundation::notices()->add_stored( [
				'message'      => $message,
				'severity'     => 'warning',
				'namespace'    => 'gk-gravityview',
				'capabilities' => [ 'manage_options' ],
				'context'      => 'all',
				'screens'      => [
					'dashboard',
				],
				'slug'         => sprintf( // Unique per shortcode tag + View + page.
					'gv_invalid_secret_%s_view_%d_page_%s',
					$shortcode_key,
					(int) $view->ID,
					$page_hash
				),
			] );
		}

		return new WP_Error(
			'invalid_secret',
			sprintf(
				esc_html__( '%1$s: Invalid View secret provided.', 'gk-gravityview' ),
				'GravityView'
			)
		);
	}

	/**
	 * Handles a WP_Error.
	 * @param WP_Error $error The error.
	 * @return string The result to return in case of an error.
	 */
	protected function handle_error( WP_Error $error ): string
	{
		// If the user can't edit forms, don't show the error message at all.
		if ( ! GFCommon::current_user_can_any( [ 'gravityforms_edit_forms' ] ) ) {
			return '';
		}

		return '<div><p>' . $error->get_error_message() . '</p></div>';
	}
}
