<?php
/**
 * Adds a button to add the View shortcode into the post content
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2013, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */

class GravityView_Admin_Add_Shortcode {

	function __construct() {

			add_action( 'media_buttons', array( $this, 'add_shortcode_button'), 30);

			add_action( 'admin_footer',	array( $this, 'add_shortcode_popup') );

			// adding styles and scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_styles') );

			// ajax - populate sort fields based on the selected view
			add_action( 'wp_ajax_gv_sortable_fields', array( $this, 'get_sortable_fields' ) );
	}


	/**
	 * check if screen post editor and is not related with post type 'gravityview'
	 *
	 * @access public
	 * @return void
	 */
	function is_post_editor_screen() {
		global $current_screen, $pagenow;
		return !empty( $current_screen->post_type ) && 'gravityview' != $current_screen->post_type && in_array( $pagenow , array( 'post.php' , 'post-new.php' ) );
	}


	/**
	 * Add shortcode button to the Add Media right
	 *
	 * @access public
	 * @return void
	 */
	function add_shortcode_button() {
		if( !$this->is_post_editor_screen() ) {
			return;
		}
		echo '<a href="#TB_inline?width=480&inlineId=select_gravityview_view&width=600&height=600" class="thickbox button gform_media_link" id="add_gravityview" title="' . esc_attr__("Add a Gravity Forms View", 'gravity-view') . '"><span class="gv_button_icon"></span> ' . esc_html__( 'Add View', 'gravity-view' ) . '</a>';
		//echo '<a href="#TB_inline?width=480&inlineId=select_gravityview_view&width=600&height=600" class="thickbox button gform_media_link" id="add_gravityview" title="' . esc_attr__("Add a Gravity Forms View", 'gravity-view') . '"><span class="dashicons dashicons-feedback"></span> ' . esc_html__( 'Add View', 'gravity-view' ) . '</a>';

	}



	/**
	 * Add shortcode popup div
	 *
	 * @access public
	 * @return void
	 */
	function add_shortcode_popup() {
		if( !$this->is_post_editor_screen() ) {
			return;
		}
		?>
		<div id="select_gravityview_view">
			<form action="#" method="get" id="select_gravityview_view_form">
				<div class="wrap">
					<h3><?php esc_html_e( 'Insert a View', 'gravity-view' ); ?></h3>
					<table class="form-table">
						<tr valign="top">
							<td><label for="gravityview_view_id"><?php esc_html_e( 'Select a View', 'gravity-view' ); ?></label></td>
							<td>
								<select name="gravityview_view_id" id="gravityview_view_id">
									<option value=""><?php esc_html_e( '-- views --', 'gravity-view' ); ?></option>
									<?php $views = get_posts( array('post_type' => 'gravityview', 'posts_per_page' => -1 ) );
									foreach( $views as $view ) {
										echo '<option value="'. $view->ID .'">'. esc_html( $view->post_title ) .'</option>';
									}
									?>
								</select>
							</td>
						</tr>

						<tr valign="top" class="alternate">
							<td>
								<label for="gravityview_page_size"><?php esc_html_e( 'Number of entries to show per page', 'gravity-view'); ?></label>
							</td>
							<td>
								<input name="gravityview_page_size" id="gravityview_page_size" type="number" step="1" min="1" value="25" class="small-text">
							</td>
						</tr>

						<tr valign="top">
							<td>
								<label for="gravityview_sort_field"><?php esc_html_e( 'Sort by field', 'gravity-view'); ?></label>
							</td>
							<td>
								<select name="gravityview_sort_field" id="gravityview_sort_field">
									<option value=""><?php esc_html_e( 'Default', 'gravity-view'); ?></option>
									<option value="date_created"><?php esc_html_e( 'Date Created', 'gravity-view'); ?></option>

								</select>
							</td>
						</tr>

						<tr valign="top" class="alternate">
							<td>
								<label for="gravityview_sort_direction"><?php esc_html_e( 'Sort direction', 'gravity-view'); ?></label>
							</td>
							<td>
								<select name="gravityview_sort_direction" id="gravityview_sort_direction">
									<option value="ASC"><?php esc_html_e( 'ASC', 'gravity-view'); ?></option>
									<option value="DESC"><?php esc_html_e( 'DESC', 'gravity-view'); ?></option>
								</select>
							</td>
						</tr>

						<tr valign="top">
							<td>
								<label for="gravityview_start_date"><?php esc_html_e( 'Filter by Start Date', 'gravity-view'); ?></label>
							</td>
							<td>
								<input name="gravityview_start_date" id="gravityview_start_date" type="text" class="gv-datepicker">
							</td>
						</tr>

						<tr valign="top" class="alternate">
							<td>
								<label for="gravityview_end_date"><?php esc_html_e( 'Filter by End Date', 'gravity-view'); ?></label>
							</td>
							<td>
								<input name="gravityview_end_date" id="gravityview_end_date" type="text" class="gv-datepicker">
							</td>
						</tr>

					</table>

					<div class="submit">
						<input type="submit" class="button-primary" value="Insert View" id="insert_gravityview_view" />
						<input class="button-secondary" type="submit" onclick="tb_remove(); return false;" value="<?php _e("Cancel", "gravity-view"); ?>" />
					</div>

				</div>
			</form>
		</div>
		<?php

	}




	/**
	 * Enqueue scripts and styles
	 *
	 * @access public
	 * @param mixed $hook
	 * @return void
	 */
	function add_scripts_and_styles() {

		if( ! $this->is_post_editor_screen() ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		// date picker
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui-datepicker', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css' );

		//enqueue styles
		wp_register_style( 'gravityview_postedit_styles', GRAVITYVIEW_URL . 'includes/css/admin-post-edit.css', array() );
		wp_enqueue_style( 'gravityview_postedit_styles' );


		// custom js
		wp_register_script( 'gravityview_postedit_scripts',  GRAVITYVIEW_URL  . 'includes/js/admin-post-edit.js', array( 'jquery', 'jquery-ui-datepicker' ), '1.0.0');
		wp_enqueue_script( 'gravityview_postedit_scripts' );
		wp_localize_script('gravityview_postedit_scripts', 'gvGlobals', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'gravityview_ajaxaddshortcode'), 'alert_1' => esc_html__( 'Please select a View', 'gravity-view') )  );

	}



	/**
	 * Ajax
	 * Given a View id, calculates the assigned form, and returns the form fields (only the sortable ones )
	 *
	 * @access public
	 * @return void
	 */
	function get_sortable_fields() {
		$response = false;

		if( empty( $_POST['viewid'] ) ) {
			echo $response;
			die();
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxaddshortcode' ) ) {
			echo $response;
			die();
		}

		// fetch form id assigned to the view
		$formid = get_post_meta( $_POST['viewid'], '_gravityview_form_id', true );
		$fields = gravityview_get_form_fields( $formid );

		if( !empty( $fields ) ) {

			$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array() );

			$response = '<option value="">'. esc_html__( 'Default', 'gravity-view') .'</option>';
			$response .= '<option value="date_created">'. esc_html__( 'Date Created', 'gravity-view' ) .'</option>';
			foreach( $fields as $id => $field ) {
				if( in_array( $field['type'], $blacklist_field_types ) ) {
					continue;
				}
				$response .= '<option value="'. $id .'">'. $field['label'] .'</option>';
			}

		}

		echo $response;
		die();
	}

}

new GravityView_Admin_Add_Shortcode;
