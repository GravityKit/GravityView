<?php
/**
 * GravityView Extension -- DataTables ADMIN
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.6
 */

class GV_Extension_DataTables_Admin {

	function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'save_post', array( $this, 'save_postdata' ) );

	}

	/**
	 * Add DataTables Extension settings
	 */
	function register_metabox() {
		add_meta_box( 'gravityview_datatables_settings', __( 'DataTables Settings', 'gravity-view' ), array( $this, 'render_metabox' ), 'gravityview', 'side', 'default' );
	}

	/**
	 * Render html for metabox
	 *
	 * @access public
	 * @param object $post
	 * @return void
	 */
	function render_metabox( $post ) {

		// Use nonce for verification
		wp_nonce_field( 'gravityview_dt_settings', 'gravityview_dt_settings_nonce' );

		// View DataTables settings
		$settings = get_post_meta( $post->ID, '_gravityview_datatables_settings', true );

		$defaults = add_filter('gravityview_dt_default_settings', array() );

		$ds = wp_parse_args( $settings, $defaults );

		do_action( 'gravityview_datatables_settings_row', $ds );
		?>

		<script>
		// TODO: Convert this to not hard-coded logic
			var gvDataTablesExt = {
				init: function() {

					jQuery('#gravityview_directory_template').change( gvDataTablesExt.showMetabox ).change();

					jQuery('#datatables_settingstabletools, #datatables_settingsscroller').change( gvDataTablesExt.showGroupOptions ).change();

					jQuery('#gv_dt_tt_showbuttons').click( gvDataTablesExt.showButtonsOptions );

				},

				showMetabox: function() {
					var template = jQuery('#gravityview_directory_template').val();
					if( 'datatables_table' === template ) {
						jQuery('#gravityview_datatables_settings').slideDown();
					} else {
						jQuery('#gravityview_datatables_settings').slideUp();
					}
				},

				showButtonsOptions: function(e) {
					e.preventDefault();
					jQuery('#gv_dt_tt_buttons').slideToggle();
				},


				showGroupOptions: function() {
					var _this = jQuery(this);
					if( _this.is(':checked') ) {
						_this.parents('tr').siblings().not('#gv_dt_tt_buttons').slideDown();
					} else {
						_this.parents('tr').siblings().slideUp();
					}
				}

			};

			jQuery(document).ready( gvDataTablesExt.init );

		</script>


		<?php

	}

	/**
	 * Save settings
	 *
	 * @access public
	 * @param mixed $post_id
	 * @return void
	 */
	function save_postdata( $post_id ) {

		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
			return;
		}

		// validate post_type
		if ( ! isset( $_POST['post_type'] ) || 'gravityview' != $_POST['post_type'] ) {
			return;
		}

		// validate user can edit and save post/page
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return;
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return;
		}

		// nonce verification
		if ( isset( $_POST['gravityview_dt_settings_nonce'] ) && wp_verify_nonce( $_POST['gravityview_dt_settings_nonce'], 'gravityview_dt_settings' ) ) {

			if( empty( $_POST['datatables_settings'] ) ) {
				$_POST['datatables_settings'] = array();
			}
			update_post_meta( $post_id, '_gravityview_datatables_settings', $_POST['datatables_settings'] );
		}


	} // end save configuration

}

new GV_Extension_DataTables_Admin;
