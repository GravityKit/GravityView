<?php

/**
 * @inheritDoc
 * @since TODO
 */
class GravityView_Plugin_Hooks_GravityView_Advanced_Filtering extends GravityView_Plugin_and_Theme_Hooks {

	public $class_name = 'GravityView_Plugin_and_Theme_Hooks'; // Always true!

	public function __construct() {

		if ( defined( 'GRAVITYKIT_ADVANCED_FILTERING_VERSION' ) ) {
			return;
		}

		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function add_hooks() {
		add_action( 'gravityview_metabox_sort_filter_after', [ $this, 'render_placehodler' ] );
	}

	/**
	 * Render placeholder HTML.
	 *
	 * @access public
	 * @param WP_Post $post
	 * @return void
	 */
	function render_placehodler( $post ) {

		// Placeholder
		echo '<tr><td>Advanced Filtering placeholder!</td></tr>';

	}
}

new GravityView_Plugin_Hooks_GravityView_Advanced_Filtering();
