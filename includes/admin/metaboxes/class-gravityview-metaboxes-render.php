<?php

class GravityView_Metaboxes_Render {

	protected $tabs = array();

	function __construct() {
		$this->init();
	}

	function init() {
		add_action('add_meta_boxes', array( $this, 'add_meta_box') );
	}

	function add_meta_box( $post ) {

		// Other Settings box
		add_meta_box( 'gravityview_settings', __( 'View Configuration', 'gravityview' ), array( $this, 'render' ), 'gravityview', 'normal', 'core' );

	}

	function render( $post ) {

		// On Comment Edit, for example, $post isn't set.
		if( empty( $post ) || !is_object( $post ) || !isset( $post->ID ) ) {
			return;
		}

		$this->render_tab_navigation();
		$this->render_tab_content();

	}

	function render_tab_navigation() {
		include_once GRAVITYVIEW_DIR .'includes/admin/metaboxes/views/gravityview-navigation.php';
	}

	function render_tab_content() {
		include_once GRAVITYVIEW_DIR .'includes/admin/metaboxes/views/gravityview-content.php';
	}

}

new GravityView_Metaboxes_Render;