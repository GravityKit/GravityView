<?php

class GV_Extension_DataTables_TableTools {

	function __construct() {

		// extensions - TableTools
		add_action( 'gravityview_datatables_scripts_styles', array( $this, 'tabletools_add_scripts' ), 10, 3 );
		add_filter( 'gravityview_datatables_js_options', array( $this, 'tabletools_add_config' ), 10, 3 );

		add_action( 'gravityview_datatables_settings_row', array( $this, 'settings_row' ) );
		add_filter( 'gravityview_dt_default_settings', array( $this, 'defaults') );
	}

	function defaults( $settings ) {

		$settings['tabletools'] = true;
		$settings['tt_buttons'] = array(
			'copy' => 1,
			'csv' => 1,
			'xls' => 0,
			'pdf' => 0,
			'newpdf' => 1,
			'print' => 1
		);

		return $settings;
	}

	function settings_row( $ds ) {

		$tt_buttons_labels = GV_Extension_DataTables_TableTools::tabletools_button_labels();

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
					foreach( $ds['tt_buttons'] as $b_key => $b_value ) {
						echo GravityView_Admin_Views::render_field_option( 'datatables_settings[tt_buttons]['. $b_key .']', array( 'label' => $tt_buttons_labels[ $b_key ], 'type' => 'checkbox', 'value' => 1 ), $ds['tt_buttons'][ $b_key ] );
					}
					?>
				</td>
			</tr>
			<?php /*
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
			*/ ?>
		</table>
		<?php
	}

	/**
	 * Returns the TableTools buttons' labels
	 * @return array
	 */
	public static function tabletools_button_labels() {
		return array(
			'select_all' => __( 'Select All', 'gravity-view' ),
			'select_none' => __( 'Deselect All', 'gravity-view' ),
			'copy' => __( 'Copy', 'gravity-view' ),
			'csv' => 'CSV',
			'xls' => 'XLS',
			'newpdf' => 'NEW PDF',
			'pdf' => 'PDF',
			'print' => __( 'Print', 'gravity-view' )
		);
	}

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
			$button_labels = self::tabletools_button_labels();

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

		do_action( 'gravityview_log_debug', '[tabletools_add_config] Inserting TableTools config. Data: ', $dt_config );

		return $dt_config;
	}

}

new GV_Extension_DataTables_TableTools;
