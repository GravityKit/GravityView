<?php

/**
 * @inheritDoc
 * @since TODO
 */
class GravityView_Plugin_Hooks_GravityView_DataTables extends GravityView_Plugin_and_Theme_Hooks {

	public $class_name = 'GravityView_Plugin_and_Theme_Hooks'; // Always true!

	public function __construct() {

		if ( defined( 'GV_DT_VERSION' ) ) {
			return;
		}

		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function add_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
	}

	/**
	 * Add DataTables Extension settings
	 */
	function register_metabox() {

		$m = array(
			'id' => 'datatables_settings',
			'title' => __( 'DataTables', 'gv-datatables' ),
			'callback' => array( $this, 'render_placehodler' ),
			'callback_args' => array(),
			'screen' => 'gravityview',
			'file' => '',
			'icon-class' => 'gv-icon-datatables-icon',
			'context' => 'side',
			'priority' => 'default',
		);

		$metabox = new GravityView_Metabox_Tab( $m['id'], $m['title'], $m['file'], $m['icon-class'], $m['callback'], $m['callback_args'] );

		GravityView_Metabox_Tabs::add( $metabox );
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
		echo 'DataTables placeholder!';

	}
}

new GravityView_Plugin_Hooks_GravityView_DataTables();
