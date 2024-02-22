<?php
/**
 * Add Pageviews.io output to GravityView
 *
 * @file      class-gravityview-plugin-hooks-gravity-flow.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 1.17
 */

/**
 * @inheritDoc
 * @since 1.17
 */
class GravityView_Plugin_Hooks_Pageviews extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @var string Check for the Gravity Flow constant
	 */
	protected $class_name = 'Pageviews';

	/**
	 * @var The next entry ID before the shortcode is gonna be output.
	 */
	public $next_id = false;

	/**
	 * @var The shortcode attributes.
	 */
	public $atts = array();

	/**
	 * Filter the values shown in GravityView frontend
	 *
	 * @since 1.17
	 */
	function add_hooks() {

		parent::add_hooks();

		add_shortcode( 'gv_pageviews', array( $this, 'pageviews' ) );

		add_filter( 'gravityview/fields/custom/decode_shortcodes', array( $this, 'inject_entry_id' ), 10, 3 );

		add_filter( 'template_redirect', array( $this, 'remove_autocontent' ), 11 );

		add_action( 'pageviews_before_js', array( $this, 'increment_callback' ) );
	}

	/**
	 * Remove the autocontent filter on single entries.
	 * We do not need to be outputting the View counter.
	 */
	public function remove_autocontent( $r ) {
		if ( gravityview()->request->is_entry() ) {
			remove_filter( 'the_content', 'Pageviews::compat_the_content' );
		}
		return $r;
	}

	/**
	 * Maybe set self::$next_id from the context.
	 *
	 * Used as sort of an action via the gravityview/fields/custom/decode_shortcodes filter.
	 */
	public function inject_entry_id( $r, $content, $context ) {
		if ( ! empty( $context->entry['id'] ) ) {
			$this->next_id = $context->entry['id'];
		} else {
			$this->next_id = false; // Nothing to look at, move along
		}

		return $r;
	}

	/**
	 * Output the Pageviews stuffs
	 *
	 * Shortcode: [gv_pageviews]
	 *
	 * Attributes: `id` Overload the Entry ID. Default: {entry_id} for the custom content field
	 *             `preload` The preload text. Default: ...
	 *
	 * @since develop
	 *
	 * @param array $atts The shortcode arguments
	 *
	 * @return string The content
	 */
	public function pageviews( $atts ) {
		$this->atts = shortcode_atts(
			array(
				'preload' => '...',
				'id'      => $this->next_id,
			),
			$atts
		);

		if ( ! $this->atts['id'] ) {
			return; // The ID was not set
		}

		add_filter( 'pageviews_placeholder_preload', array( $this, 'preload_callback' ) );

		$output = Pageviews::placeholder( 'GV' . $this->atts['id'] ); // Prefix the ID to avoid collissions with default post IDs

		remove_filter( 'pageviews_placeholder_preload', array( $this, 'preload_callback' ) );

		return $output;
	}

	/**
	 * Set the preload text.
	 */
	public function preload_callback( $preload ) {
		return $this->atts['preload'];
	}

	/**
	 * Set the increment configuration parameter.
	 */
	public function increment_callback() {
		if ( $entry = gravityview()->request->is_entry() ) {
			$increment = 'GV' . $entry['id'];
			?>
				_pv_config.incr = <?php echo json_encode( $increment ); ?>;
			<?php
		}
	}
}

new GravityView_Plugin_Hooks_Pageviews();
