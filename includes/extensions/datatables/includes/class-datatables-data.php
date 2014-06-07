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
		//add_filter( 'gravityview_render_view_sections', array( $this, 'add_ajax_template_section' ), 10, 2 );

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
			GravityView_Plugin::log_debug( '[DataTables] AJAX request - NONCE check failed' );
			echo false;
			die();
		}
	}

	/**
	 * main AJAX logic to retrieve DataTables data
	 */
	function get_datatables_data() {
		$this->check_ajax_nonce();

		if( empty( $_POST['view_id'] ) ) {
			GravityView_Plugin::log_debug( '[DataTables] AJAX request - View ID check failed');
			die();
		}

		GravityView_Plugin::log_debug( '[DataTables] AJAX Request $_POST: ' . print_r( $_POST, true ) );

		// include some frontend logic
		if( class_exists('GravityView_Plugin') && !class_exists('GravityView_View') ) {
			GravityView_Plugin::getInstance()->frontend_actions();
		}

		// build Render View attributes array
		$atts['id'] = $_POST['view_id'];

		// check for order/sorting
		if( isset( $_POST['order'][0]['column'] ) ) {
			$order_index = $_POST['order'][0]['column'];
			if( !empty( $_POST['columns'][ $order_index ]['name'] ) ) {
				// remove prefix 'gv_'
				$atts['sort_field'] = substr( $_POST['columns'][ $order_index ]['name'], 3 );
				$atts['sort_direction'] = !empty( $_POST['order'][0]['dir'] ) ? strtoupper( $_POST['order'][0]['dir'] ) : 'ASC';
			}
		}

		// check for search
		if( !empty( $_POST['search']['value'] ) ) {
			$atts['search_value'] = $_POST['search']['value'];
		}

		// Paging/offset
		$atts['page_size'] = isset( $_POST['length'] ) ? $_POST['length'] : '';
		$atts['offset'] = isset( $_POST['start'] ) ? $_POST['start'] : 0;

		// shortcode attributes ?
		if( !empty( $_POST['shortcode_atts'] ) ) {
			$atts = wp_parse_args( $atts, $_POST['shortcode_atts'] );
		}
		$template_settings = get_post_meta( $atts['id'], '_gravityview_template_settings', true );
		$atts = wp_parse_args( $atts, $template_settings );

		// prepare to get entries
		$atts = wp_parse_args( $atts, GravityView_frontend::get_default_args() );
		$form_id = get_post_meta( $atts['id'], '_gravityview_form_id', true );
		$dir_fields = get_post_meta( $atts['id'], '_gravityview_directory_fields', true );

		// get view entries
		$view_entries = GravityView_frontend::get_view_entries( $atts, $form_id );


		global $gravityview_view;
		$gravityview_view = new GravityView_View();
		$gravityview_view->form_id = $form_id;
		$gravityview_view->view_id = $atts['id'];
		$gravityview_view->fields = $dir_fields;
		$gravityview_view->context = 'directory';
		$gravityview_view->post_id = isset( $_POST['post_id'] ) ? $_POST['post_id'] : '';

		// build output data
		$data = array();
		if( $view_entries['count'] !== 0 ) {
			foreach( $view_entries['entries'] as $entry ) {
				$temp = array();
				if( !empty(  $dir_fields['directory_table-columns'] ) ) {
					foreach( $dir_fields['directory_table-columns'] as $field ) {
						$temp[] = gv_value( $entry, $field );
					}
				}
				$data[] = $temp;
			}
		}


		// wrap all
		$output = array(
			'draw' => intval( $_POST['draw'] ),
			'recordsTotal' => $view_entries['count'],
			'recordsFiltered' => $view_entries['count'],
			'data' => $data
			);

		GravityView_Plugin::log_debug( '[DataTables] Ajax request answer: ' . print_r( $output, true ) );

		echo json_encode($output);
		die();
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

		if( !is_a( $post, 'WP_Post' ) ) {
			return;
		}

		// View was called using the shortcode
		if( has_shortcode( $post->post_content, 'gravityview' ) ) {
			$view_atts = GravityView_frontend::get_view_shortcode_atts( $post->post_content );
			if( !empty( $view_atts['id'] ) ) {
				$view_id = $view_atts['id'];
			} else {
				return;
			}
		} else if( 'gravityview' === get_post_type() ) {
			// view was called directly
			$view_id = $post->ID;
		} else {
			return;
		}

		// get the View template
		$template_id = get_post_meta( $view_id, '_gravityview_directory_template', true );

		// is the View requested a Datatables view ?
		if( empty( $template_id ) || 'datatables_table' !== $template_id ) {
			return;
		}

		// include DataTables core script
		wp_enqueue_script( 'gv-datatables', apply_filters( 'gravityview_datatables_script_src', '//cdn.datatables.net/1.10.0/js/jquery.dataTables.min.js' ), array( 'jquery' ), GV_Extension_DataTables::version, true );

		// include DataTables custom script
		wp_enqueue_script( 'gv-datatables-cfg', plugins_url( 'assets/js/datatables-views.js', GV_DT_FILE ), array( 'gv-datatables' ), GV_Extension_DataTables::version, true );

		// fetch template settings
		$template_settings = get_post_meta( $view_id, '_gravityview_template_settings', true );

		// Prepare DataTables init config
		$dt_config =  array(
			'processing' => true,
			'serverSide' => true,
			'ajax' => array(
				'url' => admin_url( 'admin-ajax.php' ),
				'type' => 'POST',
				'data' => array(
					'action' => 'gv_datatables_data',
					'view_id' => $view_id,
					'post_id' => $post->ID,
					'nonce' => wp_create_nonce( 'gravityview_datatables_data' ),
				),
			),
		);

		// merge shortcode attributes with template settings, if we're running on shortcode
		// inject shortcode atts into ajax
		if( !empty( $view_atts ) ) {
			$args = wp_parse_args( $view_atts, $template_settings );
			$dt_config['ajax']['data']['shortcode_atts'] = $view_atts;
		} else {
			$args = $template_settings;
		}


		// get View directory active fields to init columns
		$dir_fields = get_post_meta( $view_id, '_gravityview_directory_fields', true );
		$columns = array();
		if( !empty( $dir_fields['directory_table-columns'] ) ) {
			foreach( $dir_fields['directory_table-columns'] as $field ) {
				$columns[] = array( 'name' => 'gv_' . $field['id'] );
			}
			$dt_config['columns'] = $columns;
		}

		// set default order
		if( !empty( $args['sort_field'] ) ) {
			foreach ( $columns as $key => $column ) {
				if( $column['name'] === 'gv_'. $args['sort_field'] ) {
					$dir = !empty( $args['sort_direction'] ) ? $args['sort_direction'] : 'asc';
					$dt_config['order'] = array( array( $key, strtolower( $dir ) ) );
				}
			}
		}


		// filter init DataTables options
		$dt_config = apply_filters( 'gravityview_datatables_js_options', $dt_config );

		wp_localize_script( 'gv-datatables-cfg', 'gvDTglobals', $dt_config );

		// DataTables Style
		wp_enqueue_style( 'gv-datatables_style', apply_filters( 'gravityview_datatables_style_src', '//cdn.datatables.net/1.10.0/css/jquery.dataTables.css' ), array(), GV_Extension_DataTables::version, 'all' );


	} // end add_scripts_and_styles




} // end class
new GV_Extension_DataTables_Data;


