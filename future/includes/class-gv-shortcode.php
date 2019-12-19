<?php
namespace GV;

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
	/*
	 * @var array All GravityView-registered and loaded shortcodes can be found here.
	 */
	private static $shortcodes;

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
	 * @param array $atts The attributes passed.
	 * @param string $content The content inside the shortcode.
	 * @param string $tag The tag.
	 *
	 * @return string The output.
	 */
	public function callback( $atts, $content = '', $tag = '' ) {
		gravityview()->log->error( '[{shortcode}] shortcode {class}::callback method not implemented.', array( 'shortcode' => $this->name, 'class' => get_class( $this ) ) );
		return '';
	}

	/**
	 * Register this shortcode class with the WordPress Shortcode API.
	 *
	 * @internal
	 *
	 * @since develop
	 * @param string $name A shortcode name override. Default: self::$name
	 *
	 * @return \GV\Shortcode|null The only internally registered instance of this shortcode, or null on error.
	 */
	public static function add( $name = null ) {
		$shortcode = new static();
		$name = $name ? $name : $shortcode->name;
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
		unset( self::$shortcodes[$shortcode->name] );
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

			$shortcode_atts = shortcode_parse_atts( $shortcode[3] );
			$shortcode_content = $shortcode[5];

			/** This is a registered GravityView shortcode. */
			if ( !empty( self::$shortcodes[$shortcode_name] ) ) {
				$shortcode = clone self::$shortcodes[$shortcode_name];
			} else {
				/** This is some generic shortcode. */
				$shortcode = new self;
				$shortcode->name = $shortcode_name;
			}

			$shortcode->atts = $shortcode_atts;
			$shortcode->content = $shortcode_content;

			/** Merge inner shortcodes. */
			$shortcodes = array_merge( $shortcodes, array( $shortcode ), self::parse( $shortcode_content ) );
		}

		return $shortcodes;
	}
}
