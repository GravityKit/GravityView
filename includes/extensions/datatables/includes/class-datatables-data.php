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

		// extensions - TableTools
		add_action( 'gravityview_datatables_scripts_styles', array( $this, 'tabletools_add_scripts' ), 10, 3 );
		add_filter( 'gravityview_datatables_js_options', array( $this, 'tabletools_add_config' ), 10, 3 );

		// extensions - Scroller
		add_action( 'gravityview_datatables_scripts_styles', array( $this, 'scroller_add_scripts' ), 10, 3 );
		add_filter( 'gravityview_datatables_js_options', array( $this, 'scroller_add_config' ), 10, 3 );

		// extensions - FixedHeader & FixedColumns
		add_action( 'gravityview_datatables_scripts_styles', array( $this, 'fixedheadercolumns_add_scripts' ), 10, 3 );
		add_filter( 'gravityview_datatables_js_options', array( $this, 'fixedheadercolumns_add_config' ), 10, 3 );

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



		// check if someone requested the full filtered data (eg. TableTools print button)
		if( $atts['page_size'] == '-1' ) {
			$mode = 'all';
			$atts['page_size'] = '200';
		} else {
			// regular mode - get view entries
			$mode = 'page';
		}

		$view_entries = GravityView_frontend::get_view_entries( $atts, $form_id );

		global $gravityview_view;
		$gravityview_view = new GravityView_View( array(
			'form_id' => $form_id,
			'view_id' => $atts['id'],
			'fields'  => $dir_fields,
			'context' => 'directory',
			'post_id' => ( isset( $_POST['post_id'] ) ? $_POST['post_id'] : '' ),
		) );

		// build output data
		$data = array();
		if( $view_entries['count'] !== 0 ) {

			$total = $view_entries['count'];
			$i = 0;
			do {
				foreach( $view_entries['entries'] as $entry ) {
					$temp = array();
					if( !empty(  $dir_fields['directory_table-columns'] ) ) {
						foreach( $dir_fields['directory_table-columns'] as $field ) {
							$temp[] = gv_value( $entry, $field );
						}
					}
					$data[] = $temp;
					$i++;
				}

				//prepare for one more loop (in case)
				if( 'all' === $mode && $i < $total ) {
					$atts['offset'] = $view_entries['paging']['offset'] + $atts['page_size'];
					$view_entries = GravityView_frontend::get_view_entries( $atts, $form_id );
				}

			} while( 'all' === $mode && $view_entries['count'] > 0 && $i < $total );
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

		// Index 100 is the default GravityView template path.
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
		if( 'gravityview' === get_post_type( $post ) ) {
			// view was called directly
			$view_id = $post->ID;
		} else if( has_gravityview_shortcode( $post ) ) {
			$view_atts = GravityView_frontend::get_view_shortcode_atts( $post->post_content );

			if( !empty( $view_atts['id'] ) ) {
				$view_id = $view_atts['id'];
			} else {
				GravityView_Plugin::log_error( 'GV_Extension_DataTables_Data[add_scripts_and_styles] Returning; no ID defined.');
				return;
			}
		} else {
			GravityView_Plugin::log_debug( 'GV_Extension_DataTables_Data[add_scripts_and_styles] Not GravityView type and no shortcode found.');
			return;
		}

		// get the View template
		$template_id = get_post_meta( $view_id, '_gravityview_directory_template', true );

		// is the View requested a Datatables view ?
		if( empty( $template_id ) || 'datatables_table' !== $template_id ) {
			GravityView_Plugin::log_debug( 'GV_Extension_DataTables_Data[add_scripts_and_styles] DataTables view not requested.');
			return;
		}


		// fetch template settings
		$template_settings = get_post_meta( $view_id, '_gravityview_template_settings', true );

		// Prepare DataTables init config
		$dt_config =  array(
			'processing' => true,
			'serverSide' => true,
			// On refresh (and on single entry view, then clicking "go back"), save the page you were on.
			'stateSave'	 => true,
			// Only save the state for the session.
			// Use to time in seconds (like the DAY_IN_SECONDS WordPress constant) if you want to modify.
			'stateDuration' => -1,
			'oLanguage' => array(
				'sProcessing' => __( 'Loading data...', 'gravity-view' ),
			),
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

		// page size, if defined
		if( !empty( $args['page_size'] ) ) {
			$dt_config['pageLength'] = $args['page_size'];
		}

		// filter init DataTables options
		$dt_config = apply_filters( 'gravityview_datatables_js_options', $dt_config, $view_id, $post );

		GravityView_Plugin::log_debug( 'GV_Extension_DataTables_Data[add_scripts_and_styles] DataTables configuration: '. print_r( $dt_config, true ) );





		/**
		 * Include DataTables core script
		 * Use your own DataTables core script by using the `gravityview_datatables_script_src` filter
		 */
		wp_enqueue_script( 'gv-datatables', apply_filters( 'gravityview_datatables_script_src', '//cdn.datatables.net/1.10.0/js/jquery.dataTables.min.js' ), array( 'jquery' ), GV_Extension_DataTables::version, true );

		/**
		 * Use your own DataTables stylesheet by using the `gravityview_datatables_style_src` filter
		 */
		wp_enqueue_style( 'gv-datatables_style', apply_filters( 'gravityview_datatables_style_src', '//cdn.datatables.net/1.10.0/css/jquery.dataTables.css' ), array(), GV_Extension_DataTables::version, 'all' );

		// include DataTables custom script
		wp_enqueue_script( 'gv-datatables-cfg', plugins_url( 'assets/js/datatables-views.js', GV_DT_FILE ), array( 'gv-datatables' ), GV_Extension_DataTables::version, true );

		wp_localize_script( 'gv-datatables-cfg', 'gvDTglobals', $dt_config );

		// Extend datatables by including other scripts and styles
		do_action( 'gravityview_datatables_scripts_styles', $dt_config, $view_id, $post );


	} // end add_scripts_and_styles


	/** ---- DATATABLES EXTENSIONS ---- */


	/** TableTools */

	/**
	 * Inject TableTools Scripts and Styles if needed
	 */
	function tabletools_add_scripts( $dt_config, $view_id, $post ) {

		$settings = get_post_meta( $view_id, '_gravityview_datatables_settings', true );

		if( empty( $settings['tabletools'] ) ) {
			return;
		}

		/**
		 * Include TableTools core script (DT plugin)
		 * Use your own DataTables core script by using the `gravityview_datatables_script_src` filter
		 */
		wp_enqueue_script( 'gv-dt-tabletools', apply_filters( 'gravityview_dt_tabletools_script_src', '//cdn.datatables.net/tabletools/2.2.1/js/dataTables.tableTools.min.js' ), array( 'jquery', 'gv-datatables' ), GV_Extension_DataTables::version, true );

		/**
		 * Use your own TableTools stylesheet by using the `gravityview_dt_tabletools_style_src` filter
		 */
		wp_enqueue_style( 'gv-dt_tabletools_style', apply_filters( 'gravityview_dt_tabletools_style_src', '//cdn.datatables.net/tabletools/2.2.1/css/dataTables.tableTools.css' ), array('gv-datatables_style'), GV_Extension_DataTables::version, 'all' );

	}


	/**
	 * TableTools add specific config data based on admin settings
	 */
	function tabletools_add_config( $dt_config, $view_id, $post  ) {

		$settings = get_post_meta( $view_id, '_gravityview_datatables_settings', true );

		if( empty( $settings['tabletools'] ) ) {
			return $dt_config;
		}

		// init TableTools
		$dt_config['dom'] = empty( $dt_config['dom'] ) ? 'T<"clear">lfrtip' : 'T<"clear">'. $dt_config['dom'];
		$dt_config['tableTools']['sSwfPath'] = plugins_url( 'assets/swf/copy_csv_xls_pdf.swf', GV_DT_FILE );

		// row selection mode option
		//$dt_config['tableTools']['sRowSelect'] = empty( $settings['tt_row_selection'] ) ? 'none' : $settings['tt_row_selection'];
		$dt_config['tableTools']['sRowSelect'] = apply_filters( 'gravityview_dt_tabletools_rowselect', 'none', $dt_config, $view_id, $post );

		// display buttons
		if( !empty( $settings['tt_buttons'] ) && is_array( $settings['tt_buttons'] ) ) {

			//fetch buttons' labels
			$button_labels = GV_Extension_DataTables_Common::tabletools_button_labels();

			//calculate who's in
			$buttons = array_keys( $settings['tt_buttons'], 1 );

			if( !empty( $buttons ) ) {
				foreach( $buttons as $button ) {
					$dt_config['tableTools']['aButtons'][] = array(
						'sExtends' => $button,
						'sButtonText' => $button_labels[ $button ],
					);
				}
			}

		}

		GravityView_Plugin::log_debug( '[tabletools_add_config] Inserting TableTools config. Data: ' . print_r( $dt_config, true ) );

		return $dt_config;
	}


	/** Scroller */

	/**
	 * Inject Scroller Scripts and Styles if needed
	 */
	function scroller_add_scripts( $dt_config, $view_id, $post ) {

		$settings = get_post_meta( $view_id, '_gravityview_datatables_settings', true );

		if( empty( $settings['scroller'] ) ) {
			return;
		}

		/**
		 * Include Scroller core script (DT plugin)
		 * Use your own DataTables core script by using the `gravityview_dt_scroller_script_src` filter
		 */
		wp_enqueue_script( 'gv-dt-scroller', apply_filters( 'gravityview_dt_scroller_script_src', '//cdn.datatables.net/scroller/1.2.1/js/dataTables.scroller.min.js' ), array( 'jquery', 'gv-datatables' ), GV_Extension_DataTables::version, true );

		/**
		 * Use your own Scroller stylesheet by using the `gravityview_dt_scroller_style_src` filter
		 */
		wp_enqueue_style( 'gv-dt_scroller_style', apply_filters( 'gravityview_dt_scroller_style_src', '//cdn.datatables.net/scroller/1.2.1/css/dataTables.scroller.css' ), array('gv-datatables_style'), GV_Extension_DataTables::version, 'all' );

	}


	/**
	 * Scroller add specific config data based on admin settings
	 */
	function scroller_add_config( $dt_config, $view_id, $post  ) {

		$settings = get_post_meta( $view_id, '_gravityview_datatables_settings', true );

		if( empty( $settings['scroller'] ) ) {
			return $dt_config;
		}

		// init Scroller
		$dt_config['dom'] = empty( $dt_config['dom'] ) ? 'frtiS' : $dt_config['dom'].'S';

		// set table height
		$settings['scrolly'] = empty( $settings['scrolly'] ) ? '400' : (string)$settings['scrolly'];
		$dt_config['scrollY'] = empty( $dt_config['scrollY'] ) ? $settings['scrolly'] : $dt_config['scrollY'];
		$dt_config['scrollY'] .= 'px';

		GravityView_Plugin::log_debug( '[scroller_add_config] Inserting Scroller config. Data: ' . print_r( $dt_config, true ) );

		return $dt_config;
	}


	/** FixedHeader & FixedColumns */

	/**
	 * Inject FixedHeader & FixedColumns Scripts and Styles if needed
	 */
	function fixedheadercolumns_add_scripts( $dt_config, $view_id, $post ) {

		$settings = get_post_meta( $view_id, '_gravityview_datatables_settings', true );

		$fixed_config = array(
			'fixedColumns' => 0,
			'fixedHeader' => 0,
		);

		if( !empty( $settings['fixedheader'] ) ) {
			wp_enqueue_script( 'gv-dt-fixedheader', apply_filters( 'gravityview_dt_fixedheader_script_src', '//cdn.datatables.net/fixedheader/2.1.1/js/dataTables.fixedHeader.min.js' ), array( 'jquery', 'gv-datatables' ), GV_Extension_DataTables::version, true );
			wp_enqueue_style( 'gv-dt_fixedheader_style', apply_filters( 'gravityview_dt_fixedheader_style_src', '//cdn.datatables.net/fixedheader/2.1.1/css/dataTables.fixedHeader.css' ), array('gv-datatables_style'), GV_Extension_DataTables::version, 'all' );

			$fixed_config['fixedHeader'] = 1;
		}

		if( !empty( $settings['fixedcolumns'] ) ) {
			wp_enqueue_script( 'gv-dt-fixedcolumns', apply_filters( 'gravityview_dt_fixedcolumns_script_src', '//cdn.datatables.net/fixedcolumns/3.0.1/js/dataTables.fixedColumns.min.js' ), array( 'jquery', 'gv-datatables' ), GV_Extension_DataTables::version, true );
			wp_enqueue_style( 'gv-dt_fixedcolumns_style', apply_filters( 'gravityview_dt_fixedcolumns_style_src', '//cdn.datatables.net/fixedcolumns/3.0.1/css/dataTables.fixedColumns.css' ), array('gv-datatables_style'), GV_Extension_DataTables::version, 'all' );
			$fixed_config['fixedColumns'] = 1;
		}

		wp_localize_script( 'gv-datatables-cfg', 'gvDTFixedHeaderColumns', $fixed_config );

	}

	/**
	 * FixedColumns add specific config data based on admin settings
	 */
	function fixedheadercolumns_add_config( $dt_config, $view_id, $post  ) {

		$settings = get_post_meta( $view_id, '_gravityview_datatables_settings', true );

		if( empty( $settings['fixedcolumns'] ) ) {
			return $dt_config;
		}

		// FixedColumns need scrollX to be set
		$dt_config['scrollX'] = true;


		GravityView_Plugin::log_debug( '[fixedheadercolumns_add_config] Inserting FixedColumns config. Data: ' . print_r( $dt_config, true ) );

		return $dt_config;
	}




} // end class
new GV_Extension_DataTables_Data;


