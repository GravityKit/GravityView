<?php
/**
 * GravityView oEmbed handling
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 * @since 1.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Register oEmbed handlers for embedding GravityView data and render that data
 *
 * @since 1.6
 */
class GravityView_oEmbed {
	protected $entry_id = null;

	static $instance = null;

	private function __construct() {}

	/**
	 * @deprecated Use \GV\oEmbed instead.
	 */
	public function initialize() {
		gravityview()->log->notice( '\GravityView_oEmbed is deprecated. Use \GV\oEmbed instead.' );
	}

	/**
	 * @deprecated Use \GV\oEmbed instead.
	 *
	 * @return GravityView_oEmbed
	 * @since 1.6
	 */
	static function getInstance() {
		gravityview()->log->notice( '\GravityView_oEmbed is deprecated. Use \GV\oEmbed instead.' );

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->initialize();
		}

		return self::$instance;
	}

	/**
	 * Register the oEmbed handler
	 *
	 * @deprecated Use \GV\oEmbed instead.
	 *
	 * @since 1.6
	 */
	function register_handler() {
		gravityview()->log->notice( '\GravityView_oEmbed is deprecated. Use \GV\oEmbed instead.' );
	}

	/**
	 * Become an oEmbed provider for GravityView.
	 *
	 * @deprecated Use \GV\oEmbed instead.
	 *
	 * @return void
	 */
	function add_provider() {
		gravityview()->log->notice( '\GravityView_oEmbed is deprecated. Use \GV\oEmbed instead.' );
	}

	/**
	 * Output a response as a provider for an entry oEmbed URL.
	 *
	 * @deprecated Use \GV\oEmbed instead.
	 *
	 * For now we only output the JSON format and don't care about the size (width, height).
	 * Our only current use-case is for it to provide output to the Add Media / From URL box
	 *  in WordPress 4.8.
	 *
	 * @since 1.21.5.3
	 *
	 * @return void
	 */
	function render_provider_request() {
		gravityview()->log->notice( '\GravityView_oEmbed is deprecated. Use \GV\oEmbed instead.' );
	}

	/**
	 * Get the entry id for the current oEmbedded entry
	 *
	 * @since 1.6
	 *
	 * @deprecated Use \GV\oEmbed instead.
	 *
	 * @return int|null
	 */
	public function get_entry_id() {
		gravityview()->log->notice( '\GravityView_oEmbed is deprecated. Use \GV\oEmbed instead.' );
		return $this->entry_id;
	}

	/**
	 * @deprecated Use \GV\oEmbed instead.
	 *
	 * @since 1.6
	 * @see GravityView_oEmbed::add_providers() for the regex
	 *
	 * @param array  $matches The regex matches from the provided regex when calling wp_embed_register_handler()
	 * @param array  $attr Embed attributes.
	 * @param string $url The original URL that was matched by the regex.
	 * @param array  $rawattr The original unmodified attributes.
	 * @return string The embed HTML.
	 */
	public function render_handler( $matches, $attr, $url, $rawattr ) {
		gravityview()->log->notice( '\GravityView_oEmbed is deprecated. Use \GV\oEmbed instead.' );
		return '';
	}

	/**
	 * Tell get_gravityview() to display a single entry
	 *
	 * REQUIRED FOR THE VIEW TO OUTPUT A SINGLE ENTRY
	 *
	 * @deprecated Use \GV\oEmbed instead.
	 *
	 * @param bool|int $is_single_entry Existing single entry. False, because GV thinks we're in a post or page.
	 *
	 * @return int The current entry ID
	 */
	public function set_single_entry_id( $is_single_entry = false ) {
		gravityview()->log->notice( '\GravityView_oEmbed is deprecated. Use \GV\oEmbed instead.' );
		return $this->entry_id;
	}
}
