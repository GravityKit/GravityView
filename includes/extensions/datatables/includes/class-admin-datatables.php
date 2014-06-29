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

		$defaults = array(
			'tabletools' => true,
			'tt_buttons' => array(
				'select_all' => 1,
				'select_none' => 1,
				'copy' => 1,
				'csv' => 1,
				'xls' => 1,
				'pdf' => 1,
				'print' => 1
			),
			'tt_row_selection' => 'os',
		);

		$tt_buttons_labels = GV_Extension_DataTables_Common::tabletools_button_labels();

		$ds = wp_parse_args( $settings, $defaults );

		?>

		<h3 style="margin-top:1em;">TableTools:</h3>
		<table class="form-table">
			<tr valign="top">
				<td colspan="2">
					<?php
						echo GravityView_Admin_Views::render_field_option( 'datatables_settings[tabletools]', array( 'label' => __( 'Enable TableTools', 'gravity-view' ), 'type' => 'checkbox', 'value' => 1 ), $ds['tabletools'] );
					?>
				</td>
			</tr>
			<tr valign="top">
				<td scope="row">
					<?php esc_html_e( 'Display Buttons', 'gravity-view' ); ?>
				</td>
				<td>
					<a href="#" id="gv_dt_tt_showbuttons"><?php esc_html_e( 'Customize', 'gravity-view' ); ?></a>
				</td>
			</tr>
			<tr valign="top" id="gv_dt_tt_buttons" class="hide-if-js">
				<td colspan="2">
					<?php
					foreach( $defaults['tt_buttons'] as $b_key => $b_value ) {
						echo GravityView_Admin_Views::render_field_option( 'datatables_settings[tt_buttons]['. $b_key .']', array( 'label' => $tt_buttons_labels[ $b_key ], 'type' => 'checkbox', 'value' => 1 ), $ds['tt_buttons'][ $b_key ] );
					}
					?>
				</td>
			</tr>
			<tr>
				<td scope="row">
					<label for="gravityview_tt_rowselection"><?php esc_html_e( 'Row Selection', 'gravity-view'); ?></label>
				</td>
				<td>
					<select name="datatables_settings[tt_row_selection]" id="gravityview_tt_rowselection" class="widefat">
						<option value="none" <?php selected( 'none', $ds['tt_row_selection'], true ); ?>><?php esc_html_e( 'None', 'gravity-view'); ?></option>
						<option value="single" <?php selected( 'single', $ds['tt_row_selection'], true ); ?>><?php esc_html_e( 'Single row', 'gravity-view'); ?></option>
						<option value="multi" <?php selected( 'multi', $ds['tt_row_selection'], true ); ?>><?php esc_html_e( 'Multiple row', 'gravity-view'); ?></option>
						<option value="os" <?php selected( 'os', $ds['tt_row_selection'], true ); ?>><?php esc_html_e( 'Operating System like selection', 'gravity-view'); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<script>

			var gvTableTools = {
				init: function() {
					jQuery('#gv_dt_tt_showbuttons').click( gvTableTools.showButtonsOptions );
				},
				showButtonsOptions: function(e) {
					e.preventDefault();
					jQuery('#gv_dt_tt_buttons').slideToggle();
				}
			};

			gvTableTools.init();

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
