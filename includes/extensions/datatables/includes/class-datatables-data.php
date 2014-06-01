<?php
/**
 * GravityView Extension -- DataTables -- Server side data
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.3
 */


class GV_Extension_DataTables_Data {

	public function __construct() {

		// enable ajax
		add_action( 'wp_ajax_gv_datatables_data', array( $this, 'get_datatables_data' ) );
		add_action( 'wp_ajax_nopriv_gv_datatables_data', array( $this, 'get_datatables_data' ) );

		// replace template section by specific ajax
		add_filter( 'gravityview_render_view_sections', array( $this, 'add_ajax_template_section' ), 10, 2 );

		// add template path
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

		if( !is_admin() ) {
			// Enqueue scripts and styles
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_and_styles' ) );
		}



	}

	function add_ajax_template_section( $sections, $template_id = '' ) {
		if( 'datatables_table' === $template_id && defined( 'DOING_AJAX') && DOING_AJAX ) {
			$sections = array( 'ajax' );
		}
		return $sections;
	}

	/**
	 * Verify AJAX request nonce
	 */
	function check_ajax_nonce() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_datatables_data' ) ) {
			echo false;
			die();
		}
	}

	/**
	 * main AJAX logic to retrieve DataTables data
	 */
	function get_datatables_data() {
		$this->check_ajax_nonce();

		error_log( 'this datatables _POST' . print_r( $_POST , true ) );

		if( class_exists('GravityView_Plugin') && !class_exists('GravityView_frontend') ) {
			GravityView_Plugin::getInstance()->frontend_actions();
		}



	}

	/**
	 * Include this extension templates path
	 * @param array $file_paths List of template paths ordered
	 */
	function add_template_path( $file_paths ) {
		$file_paths[101] = GV_DT_DIR . 'templates/';
		return $file_paths;
	}


	/**
	 * Enqueue Scripts and Styles for DataTable View Type
	 */
	function add_scripts_and_styles() {

		global $post;

		if( is_a( $post, 'WP_Post' ) && ( has_shortcode( $post->post_content, 'gravityview' ) ||  'gravityview' === get_post_type() ) ) {

			// get the View template
			$template_id = get_post_meta( $post->ID, '_gravityview_directory_template', true );

			if( !empty( $template_id ) && 'datatables_table' === $template_id ) {
				// include datatables script
				wp_enqueue_script( 'gv-datatables', apply_filters( 'gravityview_datatables_script_src', '//cdn.datatables.net/1.10.0/js/jquery.dataTables.min.js' ), array( 'jquery' ), GV_Extension_DataTables::version, true );

				wp_enqueue_script( 'gv-datatables-cfg', plugins_url( 'assets/js/datatables-views.js', GV_DT_FILE ), array( 'gv-datatables' ), GV_Extension_DataTables::version, true );

				// Prepare DataTables init config
				$dt_config = apply_filters( 'gravityview_datatables_js_options', array(
					'processing' => true,
					'serverSide' => true,
					'ajax' => array(
						'url' => admin_url( 'admin-ajax.php' ),
						'type' => 'POST',
						'data' => array(
							'action' => 'gv_datatables_data',
							'view_id' => $post->ID,
							'nonce' => wp_create_nonce( 'gravityview_datatables_data' ),
						),
					),
				) );

				wp_localize_script( 'gv-datatables-cfg', 'gvDTglobals', $dt_config );

				wp_enqueue_style( 'gv-datatables_style', apply_filters( 'gravityview_datatables_style_src', '//cdn.datatables.net/1.10.0/css/jquery.dataTables.css' ), array(), GV_Extension_DataTables::version, 'all' );

			}
		}

	} // end add_scripts_and_styles




} // end class
new GV_Extension_DataTables_Data;


