<?php
/**
 * FixedHeader & FixedColumns
 */
class GV_Extension_DataTables_FixedHeader {

	function __construct() {

		// extensions - FixedHeader & FixedColumns
		add_action( 'gravityview_datatables_scripts_styles', array( $this, 'fixedheadercolumns_add_scripts' ), 10, 3 );
		add_filter( 'gravityview_datatables_js_options', array( $this, 'fixedheadercolumns_add_config' ), 10, 3 );

		add_action( 'gravityview_datatables_settings_row', array( $this, 'settings_row' ) );
		add_filter( 'gravityview_dt_default_settings', array( $this, 'defaults') );
	}

	function defaults( $settings ) {
		$settings['fixedcolumns'] = false;
		$settings['fixedheaders'] = false;

		return $settings;
	}

	function settings_row( $ds ) {
		?>
		<h3 style="margin-top:1em;">FixedHeader &amp; FixedColumns:</h3>
		<table class="form-table">
			<tr valign="top">
				<td colspan="2">
					<?php
						echo GravityView_Admin_Views::render_field_option( 'datatables_settings[fixedheader]', array( 'label' => __( 'Enable FixedHeader', 'gravity-view' ), 'type' => 'checkbox', 'value' => 1 ), $ds['fixedheader'] );
					?>
				</td>
			</tr>
			<tr valign="top">
				<td colspan="2">
					<?php
						echo GravityView_Admin_Views::render_field_option( 'datatables_settings[fixedcolumns]', array( 'label' => __( 'Enable FixedColumns', 'gravity-view' ), 'type' => 'checkbox', 'value' => 1 ), $ds['fixedcolumns'] );
					?>
				</td>
			</tr>
		</table>
	<?php
	}

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


		do_action( 'gravityview_log_debug', '[fixedheadercolumns_add_config] Inserting FixedColumns config. Data: ', $dt_config );

		return $dt_config;
	}

}

new GV_Extension_DataTables_FixedHeader;
