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
 * Contains some utility methods, base class for all GV Shortcodes.
 */
class Shortcode {
	/**
	 * Cache of all added shortcodes.
	 *
	 * @since TBD
	 *
	 * @var array
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
	 * Pre-processes content to encode problematic attribute values before WordPress parses them.
	 *
	 * This runs before WordPress's do_shortcode() to base64-encode all attribute values
	 * that contain characters which break WP shortcode parsing (like <).
	 *
	 * @internal
	 *
	 * @since TBD
	 *
	 * @param string $content The content to preprocess.
	 *
	 * @return string The content with encoded attribute values.
	 */
	public static function preprocess_shortcode_attributes( $content ) {
		if ( empty( $content ) || false === strpos( $content, '[' ) ) {
			return $content;
		}

		// Build pattern to match only our registered shortcodes.
		$shortcodes = array_keys( self::$shortcodes );

		if ( empty( $shortcodes ) ) {
			return $content;
		}

		// Create pattern that matches only our shortcodes.
		$shortcode_pattern = implode( '|', array_map( 'preg_quote', $shortcodes ) );

		// Match any attribute that contains problematic characters.
		// We check for <, >, [, ], or & which can break WordPress parsing.
		$pattern = '/\[(' . $shortcode_pattern . ')(\s+[^\]]*?)\]/';

		$content = preg_replace_callback( $pattern, function( $matches ) {
			$shortcode_name = $matches[1];
			$attributes_string = $matches[2];

			// Parse all attributes and encode those with problematic characters.
			$attributes_string = preg_replace_callback(
				'/(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s\]]+))/',
				function( $attr_matches ) {
					$attr_name = $attr_matches[1];

					// Determine which capture group has the value and quote type.
					if ( isset( $attr_matches[2] ) && $attr_matches[2] !== '' ) {
						// Double quoted value.
						$attr_value = $attr_matches[2];

						$quote = '"';
					} elseif ( isset( $attr_matches[3] ) && $attr_matches[3] !== '' ) {
						// Single quoted value
						$attr_value = $attr_matches[3];

						$quote = "'";
					} elseif ( isset( $attr_matches[4] ) && $attr_matches[4] !== '' ) {
						// Unquoted value
						$attr_value = $attr_matches[4];

						$quote = '';
					} else {
						// No value found, skip this attribute
						return $attr_matches[0];
					}

					// Check if value contains problematic characters
					if ( preg_match( '/[<>&\[\]]/', $attr_value ) ) {
						// Base64-encode and prefix with 'b64:'
						$attr_value = 'b64:' . base64_encode( $attr_value );
					}

					return $attr_name . '=' . $quote . $attr_value . $quote;
				},
				$attributes_string
			);

			return '[' . $shortcode_name . $attributes_string . ']';
		}, $content );

		return $content;
	}

	/**
	 * Wrapper callback that's used to normalize attributes and other operations before calling the actual shortcode callback.
	 *
	 * @since TBD
	 *
	 * @param array  $atts    The attributes passed.
	 * @param string $content The content inside the shortcode.
	 * @param string $tag     The tag.
	 *
	 * @return string The output.
	 */
	public function callback_wrapper( $atts, $content = '', $tag = '' ) {
		// Normalize attributes by decoding any base64-encoded values.
		$atts = $this->normalize_attributes( $atts );

		// Call the actual callback.
		return $this->callback( $atts, $content, $tag );
	}

	/**
	 * Decodes base64-encoded attribute values.
	 *
	 * This method handles base64 encoded values (prefixed with 'b64:') that were
	 * encoded to prevent WordPress shortcode parser from breaking on special characters.
	 *
	 * @since TBD
	 *
	 * @param array $atts The shortcode attributes.
	 *
	 * @return array The attributes with decoded values.
	 */
	protected function normalize_attributes( $atts ) {
		if ( ! is_array( $atts ) ) {
			return $atts;
		}

		foreach ( $atts as &$value ) {
			if ( is_string( $value ) && strpos( $value, 'b64:' ) === 0 ) {
				$value = base64_decode( substr( $value, 4 ) );
			}
		}

		return $atts;
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
			self::$shortcodes[ $name ] = $shortcode;
			add_shortcode( $name, [ $shortcode, 'callback_wrapper' ] );
			add_filter( 'get_the_excerpt', [ $shortcode, 'maybe_strip_shortcode_from_content' ] );

			static $filters_added;

			if ( ! $filters_added ) {
				// Add the content pre-processing filter only once.
				add_filter( 'the_content', [ __CLASS__, 'preprocess_shortcode_attributes' ], 5 );
				add_filter( 'widget_text', [ __CLASS__, 'preprocess_shortcode_attributes' ], 5 );
				add_filter( 'widget_text_content', [ __CLASS__, 'preprocess_shortcode_attributes' ], 5 );

				$filters_added = true;
			}
		}

		return self::$shortcodes[ $name ];
	}

	/**
	 * Filters the list of shortcode tags to remove from the content.
	 *
	 * @since TODO
	 *
	 * @internal
	 *
	 * @return array Array of shortcode tags to remove, which is just the current shortcode name.
	 */
	public function _get_strip_shortcode_tagnames() {
		return [ $this->name ];
	}

	/**
	 * Strips the current shortcode from passed content.
	 *
	 * @since TODO
	 *
	 * @param string $content The content.
	 *
	 * @return string The content with the current shortcode removed.
	 */
	function strip_shortcode_from_content( $content ) {
		add_filter( 'strip_shortcodes_tagnames', [ $this, '_get_strip_shortcode_tagnames' ] );

		$content = strip_shortcodes( $content );

		remove_filter( 'strip_shortcodes_tagnames', [ $this, '_get_strip_shortcode_tagnames' ] );

		return $content;
	}

	/**
	 * Strips the current shortcode from passed content if it exists.
	 *
	 * @since TODO
	 *
	 * @param string $content The content.
	 *
	 * @return string The content with the current shortcode removed, if it existed. Otherwise, the original content.
	 */
	function maybe_strip_shortcode_from_content( $content ) {
		if( ! has_shortcode( $content, $this->name ) ) {
			return $content;
		}

		return $this->strip_shortcode_from_content( $content );
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

		$current_rendering_view_id = View::get_current_rendering();

		// If the shortcode is included in the View itself, allow it to render without the valid secret.
		if ( $current_rendering_view_id && $current_rendering_view_id == $atts['view_id'] ) {
			return $view;
		}

		$shortcode     = gv_current_shortcode_tag();

		// Shortcode descriptor (safe fallback if missing). Double brackets to avoid the shortcode being parsed in the message.
		$shortcode_message = $shortcode
			? sprintf( '<strong>[[%s]]</strong> %s', esc_html( $shortcode ), esc_html__( 'shortcode', 'gk-gravityview' ) )
			: esc_html__( 'A GravityView shortcode', 'gk-gravityview' );

		if ( GVCommon::has_cap( 'edit_gravityviews', $view->ID ) ) {
			$message_template = esc_html__(
				'The [shortcode] is missing or has an invalid "secret" attribute. Update the shortcode with the following attribute: [secret]',
				'gk-gravityview'
			);

			$message = strtr( $message_template, [
				'[shortcode]' => $shortcode_message,
				'[secret]'    => '<code>secret="' . esc_attr( $view->get_validation_secret() ) . '"</code>',
			] );

			return new WP_Error(
				'invalid_secret',
				$message
			);
		}

		// If the user can't edit the View, don't show the error message with the secret but display an admin notice.
		if ( class_exists( 'GravityKitFoundation' ) && GravityKitFoundation::notices() ) {
			$current_url = home_url( add_query_arg( null, null ) );
			$page_hash   = md5( strtok( $current_url, '?' ) );

			// Restore the shortcode brackets for display.
			$shortcode_message = str_replace('[[', '[', $shortcode_message );
			$shortcode_message = str_replace(']]', ']', $shortcode_message );

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

			$message_template = esc_html__(
				'[shortcode] on [page_link] is missing or has an invalid "secret" attribute.',
				'gk-gravityview'
			);

			$message = strtr( $message_template, [
				'[shortcode]' => $shortcode_message,
				'[page_link]' => $page_link,
			] );

			GravityKitFoundation::notices()->add_stored( [
				'message'              => $message,
				'severity'             => 'warning',
				'namespace'            => 'gk-gravityview',
				'globally_dismissible' => true,
				'capabilities'         => [ 'manage_options' ],
				'context'              => 'all',
				'screens'              => [
					'dashboard',
				],
				'slug'                 => sprintf( // Unique per shortcode tag + View + page.
					'gv_invalid_secret_%s_view_%d_page_%s',
					$shortcode_key,
					(int) $view->ID,
					$page_hash
				),
			] );
		}

		$message_template = esc_html__(
			'The [shortcode] is missing or has an invalid "secret" attribute.',
			'gk-gravityview'
		);

		$message = strtr( $message_template, [
			'[shortcode]' => $shortcode_message,
			'[secret]'    => '<code>secret="' . esc_attr( $view->get_validation_secret() ) . '"</code>',
		] );

		return new WP_Error(
			'invalid_secret',
			$message
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
