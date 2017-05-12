<?php
/**
 * Adds a button to add the View shortcode into the post content
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

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
	 * @return bool
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

		/**
		 * @since 1.15.3
		 */
		if( ! GVCommon::has_cap( array( 'publish_gravityviews' ) ) ) {
			return;
		}

		if( !$this->is_post_editor_screen() ) {
			return;
		}
		?>
		<a href="#TB_inline?width=600&amp;height=800&amp;inlineId=select_gravityview_view" class="thickbox hide-if-no-js button gform_media_link" id="add_gravityview" title="<?php esc_attr_e("Insert View", 'gravityview'); ?>"><span class="icon gv-icon-astronaut-head"></span><?php esc_html_e( 'Add View', 'gravityview' ); ?></a>
		<?php

	}



	/**
	 * Add shortcode popup div
	 *
	 * @access public
	 * @return void
	 */
	function add_shortcode_popup() {
		global $post;

		if( !$this->is_post_editor_screen() ) {
			return;
		}

		$post_type = get_post_type_object($post->post_type);

		$views = get_posts( array('post_type' => 'gravityview', 'posts_per_page' => -1 ) );

		// If there are no views set up yet, we get outta here.
		if( empty( $views ) ) {
			echo '<div id="select_gravityview_view"><div class="wrap">' . GravityView_Admin::no_views_text() . '</div></div>';
			return;
		}

		?>
		<div id="select_gravityview_view">
			<form action="#" method="get" id="select_gravityview_view_form">
				<div class="wrap">

					<h2 class=""><?php esc_html_e( 'Embed a View', 'gravityview' ); ?></h2>
					<p class="subtitle"><?php printf( esc_attr ( __( 'Use this form to embed a View into this %s. %sLearn more about using shortcodes.%s', 'gravityview') ), $post_type->labels->singular_name, '<a href="http://docs.gravityview.co/article/73-using-the-shortcode" target="_blank" rel="noopener noreferrer">', '</a>' ); ?></p>

					<div>
						<h3><label for="gravityview_id"><?php esc_html_e( 'Select a View', 'gravityview' ); ?></label></h3>

						<select name="gravityview_id" id="gravityview_id">
							<option value=""><?php esc_html_e( '&mdash; Select a View to Insert &mdash;', 'gravityview' ); ?></option>
							<?php
							foreach( $views as $view ) {
								$title = empty( $view->post_title ) ? __('(no title)', 'gravityview') : $view->post_title;
								echo '<option value="'. $view->ID .'">'. esc_html( sprintf('%s #%d', $title, $view->ID ) ) .'</option>';
							}
							?>
						</select>
					</div>

					<table class="form-table hide-if-js">

						<caption><?php esc_html_e( 'View Settings', 'gravityview' ); ?></caption>

						<?php

						$settings = defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ? \GV\View_Settings::defaults( true ) : GravityView_View_Data::get_default_args( true );

						foreach ( $settings as $key => $setting ) {

							if( empty( $setting['show_in_shortcode'] ) ) { continue; }

							GravityView_Render_Settings::render_setting_row( $key, array(), NULL, 'gravityview_%s', 'gravityview_%s' );
						}
						?>

					</table>

					<div class="submit">
						<input type="submit" class="button button-primary button-large alignleft hide-if-js" value="<?php esc_attr_e('Insert View', 'gravityview' ); ?>" id="insert_gravityview_view" />
						<input class="button button-secondary alignright" type="submit" onclick="tb_remove(); return false;" value="<?php esc_attr_e("Cancel", 'gravityview'); ?>" />
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
	 * @return void
	 */
	function add_scripts_and_styles() {

		if( ! $this->is_post_editor_screen() ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		// date picker
		wp_enqueue_script( 'jquery-ui-datepicker' );

		$protocol = is_ssl() ? 'https://' : 'http://';

		wp_enqueue_style( 'jquery-ui-datepicker', $protocol.'ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css', array(), GravityView_Plugin::version );

		//enqueue styles
		wp_register_style( 'gravityview_postedit_styles', plugins_url('assets/css/admin-post-edit.css', GRAVITYVIEW_FILE), array(), GravityView_Plugin::version );
		wp_enqueue_style( 'gravityview_postedit_styles' );

		$script_debug = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

		// custom js
		wp_register_script( 'gravityview_postedit_scripts',  plugins_url('assets/js/admin-post-edit'.$script_debug.'.js', GRAVITYVIEW_FILE), array( 'jquery', 'jquery-ui-datepicker' ), GravityView_Plugin::version );
		wp_enqueue_script( 'gravityview_postedit_scripts' );
		wp_localize_script('gravityview_postedit_scripts', 'gvGlobals', array(
			'nonce' => wp_create_nonce( 'gravityview_ajaxaddshortcode'),
			'loading_text' => esc_html__( 'Loading&hellip;', 'gravityview' ),
			'alert_1' => esc_html__( 'Please select a View', 'gravityview'),
		));

	}



	/**
	 * Ajax
	 * Given a View id, calculates the assigned form, and returns the form fields (only the sortable ones )
	 *
	 * @access public
	 * @return void
	 */
	function get_sortable_fields() {

		// Not properly formatted request
		if ( empty( $_POST['viewid'] ) || !is_numeric( $_POST['viewid'] ) ) {
			exit( false );
		}

		// Not valid request
		if( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxaddshortcode' ) ) {
			exit( false );
		}

		$viewid = (int)$_POST['viewid'];

		// fetch form id assigned to the view
		$formid = gravityview_get_form_id( $viewid );

		// Get the default sort field for the view
		$sort_field = gravityview_get_template_setting( $viewid, 'sort_field' );

		// Generate the output `<option>`s
		$response = gravityview_get_sortable_fields( $formid, $sort_field );

		exit( $response );
	}

}

new GravityView_Admin_Add_Shortcode;
