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
 * @since 1.0.4
 */


class GV_Extension_DataTables_Data {

	public function __construct() {

		// enable ajax
		add_action( 'wp_ajax_gv_datatables_data', array( $this, 'get_datatables_data' ) );
		add_action( 'wp_ajax_nopriv_gv_datatables_data', array( $this, 'get_datatables_data' ) );

		// add template path
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

		if( !is_admin() ) {
			// Enqueue scripts and styles
			add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_and_styles' ) );
		}
	}

	/**
	 * Verify AJAX request nonce
	 */
	function check_ajax_nonce() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_datatables_data' ) ) {
			do_action( 'gravityview_log_debug', '[DataTables] AJAX request - NONCE check failed' );
			exit( false );
		}
	}

	/**
	 * main AJAX logic to retrieve DataTables data
	 */
	function get_datatables_data() {

		$this->check_ajax_nonce();

		if( empty( $_POST['view_id'] ) ) {
			do_action( 'gravityview_log_debug', '[DataTables] AJAX request - View ID check failed');
			exit( false );
		}

		do_action( 'gravityview_log_debug', '[DataTables] AJAX Request ($_POST)', $_POST );

		// include some frontend logic
		if( class_exists('GravityView_Plugin') && !class_exists('GravityView_View') ) {
			GravityView_Plugin::getInstance()->frontend_actions();
		}

		$atts = array();

		// build Render View attributes array
		$view_data = GravityView_View_Data::add_view( (int)$_POST['view_id'] );

		$atts = $view_data['atts'];

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
		$atts['page_size'] = isset( $_POST['length'] ) ? intval( $_POST['length'] ) : '';
		$atts['offset'] = isset( $_POST['start'] ) ? intval( $_POST['start'] ) : 0;

		// prepare to get entries
		$atts = wp_parse_args( $atts, GravityView_View_Data::get_default_args() );

		// check if someone requested the full filtered data (eg. TableTools print button)
		if( $atts['page_size'] == '-1' ) {
			$mode = 'all';
			$atts['page_size'] = '200';
		} else {
			// regular mode - get view entries
			$mode = 'page';
		}

		$view_entries = GravityView_frontend::get_view_entries( $atts, $view_data['form_id'] );

		global $gravityview_view;
		$gravityview_view = new GravityView_View( array(
			'form_id' => $view_data['form_id'],
			'view_id' => $atts['id'],
			'fields'  => $view_data['fields'],
			'context' => 'directory',
			'post_id' => ( isset( $_POST['post_id'] ) ? $_POST['post_id'] : '' ),
			'atts' => $atts,
		) );

		// build output data
		$data = array();
		if( $view_entries['count'] !== 0 ) {

			$total = $view_entries['count'];
			$i = 0;
			do {
				foreach( $view_entries['entries'] as $entry ) {
					$temp = array();
					if( !empty(  $view_data['fields']['directory_table-columns'] ) ) {
						foreach( $view_data['fields']['directory_table-columns'] as $field ) {
							$temp[] = gv_value( $entry, $field );
						}
					}
					$data[] = $temp;
					$i++;
				}

				//prepare for one more loop (in case)
				if( 'all' === $mode && $i < $total ) {
					$atts['offset'] = $view_entries['paging']['offset'] + $atts['page_size'];
					$view_entries = GravityView_frontend::get_view_entries( $atts, $view_data['form_id'] );
				}

			} while( 'all' === $mode && $view_entries['count'] > 0 && $i < $total );
		}

		// wrap all
		$output = array(
			'draw' => intval( $_POST['draw'] ),
			'recordsTotal' => intval( $view_entries['count'] ),
			'recordsFiltered' => intval( $view_entries['count'] ),
			'data' => $data
		);

		do_action( 'gravityview_log_debug', '[DataTables] Ajax request answer', $output );

		exit( json_encode($output) );
	}

	/**
	 * Include this extension templates path
	 * @param array $file_paths List of template paths ordered
	 */
	function add_template_path( $file_paths ) {

		// Index 100 is the default GravityView template path.
		$file_paths[101] = GV_DT_DIR . 'templates/';

		return $file_paths;
	}


	/**
	 * Enqueue Scripts and Styles for DataTable View Type
	 *
	 * @filter gravityview_datatables_loading_text Modify the text shown while the DataTable is loading
	 */
	function add_scripts_and_styles() {
		global $post;

		if( !is_a( $post, 'WP_Post' ) ) {
			return;
		}

		// build Render View attributes array
		$view_data = GravityView_View_Data::add_view( $post->ID );

		if( empty( $view_data['id'] ) ) {
			do_action( 'gravityview_log_error', 'GV_Extension_DataTables_Data[add_scripts_and_styles] Returning; no ID defined.');
		}

		// is the View requested a Datatables view ?
		if( empty( $view_data['template_id'] ) || 'datatables_table' !== $view_data['template_id'] ) {
			do_action( 'gravityview_log_debug', 'GV_Extension_DataTables_Data[add_scripts_and_styles] DataTables view not requested.');
			return;
		}

		// Prepare DataTables init config
		$dt_config =  array(
			'processing' => true,
			'serverSide' => true,
			'retrieve'	 => true, // Only initialize once
			// On refresh (and on single entry view, then clicking "go back"), save the page you were on.
			'stateSave'	 => true,
			// Only save the state for the session.
			// Use to time in seconds (like the DAY_IN_SECONDS WordPress constant) if you want to modify.
			'stateDuration' => -1,
			'oLanguage' => array(
				'sProcessing' => apply_filters( 'gravityview_datatables_loading_text', __( 'Loading data&hellip;', 'gravity-view' ) ),
			),
			'ajax' => array(
				'url' => admin_url( 'admin-ajax.php' ),
				'type' => 'POST',
				'data' => array(
					'action' => 'gv_datatables_data',
					'view_id' => $view_data['id'],
					'post_id' => $post->ID,
					'nonce' => wp_create_nonce( 'gravityview_datatables_data' ),
				),
			),
		);

		// page size, if defined
		if( !empty( $view_data['atts']['page_size'] ) && is_numeric( $view_data['atts']['page_size'] ) ) {
			$dt_config['pageLength'] = intval( $view_data['atts']['page_size'] );
		}

		$columns = array();
		if( !empty( $view_data['fields']['directory_table-columns'] ) ) {
			foreach( $view_data['fields']['directory_table-columns'] as $field ) {
				$columns[] = array( 'name' => 'gv_' . $field['id'] );
			}
			$dt_config['columns'] = $columns;
		}

		// set default order
		if( !empty( $view_data['atts']['sort_field'] ) ) {
			foreach ( $columns as $key => $column ) {
				if( $column['name'] === 'gv_'. $view_data['atts']['sort_field'] ) {
					$dir = !empty( $view_data['atts']['sort_direction'] ) ? $view_data['atts']['sort_direction'] : 'asc';
					$dt_config['order'] = array( array( $key, strtolower( $dir ) ) );
				}
			}
		}

		// filter init DataTables options
		$dt_config = apply_filters( 'gravityview_datatables_js_options', $dt_config, $view_data['id'], $post );

		do_action('gravityview_log_debug', 'GV_Extension_DataTables_Data[add_scripts_and_styles] DataTables configuration: ', $dt_config );

		/**
		 * Include DataTables core script
		 * Use your own DataTables core script by using the `gravityview_datatables_script_src` filter
		 */
		wp_enqueue_script( 'gv-datatables', apply_filters( 'gravityview_datatables_script_src', '//cdn.datatables.net/1.10.0/js/jquery.dataTables.min.js' ), array( 'jquery' ), GV_Extension_DataTables::version, true );

		/**
		 * Use your own DataTables stylesheet by using the `gravityview_datatables_style_src` filter
		 */
		wp_enqueue_style( 'gv-datatables_style', apply_filters( 'gravityview_datatables_style_src', '//cdn.datatables.net/1.10.0/css/jquery.dataTables.css' ), array(), GV_Extension_DataTables::version, 'all' );

		$script_debug = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

		// include DataTables custom script
		wp_enqueue_script( 'gv-datatables-cfg', plugins_url( 'assets/js/datatables-views'.$script_debug.'.js', GV_DT_FILE ), array( 'gv-datatables' ), GV_Extension_DataTables::version, true );

		wp_localize_script( 'gv-datatables-cfg', 'gvDTglobals', $dt_config );

		// Extend datatables by including other scripts and styles
		do_action( 'gravityview_datatables_scripts_styles', $dt_config, $view_data['id'], $post );


	} // end add_scripts_and_styles

} // end class

new GV_Extension_DataTables_Data;
