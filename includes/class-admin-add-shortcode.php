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
			


	}
	
	
	function add_shortcode_button() {
		
		echo '<a href="#TB_inline?width=480&inlineId=select_gravityview_view" class="thickbox button gform_media_link" id="add_gravityview" title="' . esc_attr__("Add a Gravity Forms View", 'gravity-view') . '"><span class="dashicons dashicons-feedback"></span> ' . esc_html__( 'Add View', 'gravity-view' ) . '</a>';
		
	}
	
	
	
	function add_shortcode_popup() {
		?>
		<div id="select_gravityview_view">
			<form action="#" method="get" id="select_gravityview_view_form">
				<div class="wrap">
					<h3><?php esc_html_e( 'Insert a View', 'gravity-view' ); ?></h3>
					<table class="form-table">
						<tr valign="top">
							<td scope="row"><label for="gravityview_view_id"><?php esc_html_e( 'Select a View', 'gravity-view' ); ?></label></td>
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
						
						<?php //todo: sort field, sort direction, start date, end date, class ?>
						
						<tr valign="top">
							<td scope="row"><label for="tablecell">Table Cell #5, with label</label></td>
							<td>Table Cell #6</td>
						</tr>
					</table>
					
					
				
						<div class="submit">
							<input type="submit" class="button-primary" style="margin-right:15px;" value="Insert View" id="insert_gravityview_view" />
							<a class="button button-secondary" style="color:#bbb;" href="#" onclick="tb_remove(); return false;"><?php _e("Cancel", "gravity-forms-addons"); ?></a>
						</div>
				
				</div>
			</form>
		</div>
		<?php
		
	}
	
	
	

	function add_scripts_and_styles( $hook ) {
		
		wp_enqueue_style( 'dashicons' );
		
		//enqueue styles
		wp_register_style( 'gravityview_postedit_styles', GRAVITYVIEW_URL . 'includes/css/admin-post-edit.css', array() );
		wp_enqueue_style( 'gravityview_postedit_styles' );
	}
	
	
	

}