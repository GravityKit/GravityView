<?php

/**
 * FixedHeader & FixedColumns
 */
class GV_Extension_DataTables_Scroller {

	function __construct() {

		// extensions - Scroller
		add_action( 'gravityview_datatables_scripts_styles', array( $this, 'scroller_add_scripts' ), 10, 3 );
		add_filter( 'gravityview_datatables_js_options', array( $this, 'scroller_add_config' ), 10, 3 );

		add_action( 'gravityview_datatables_settings_row', array( $this, 'settings_row' ) );
		add_filter( 'gravityview_dt_default_settings', array( $this, 'defaults') );
	}

	function defaults( $settings ) {

		$settings['scroller'] = false;
		$settings['scrolly'] = 500;

		return $settings;
	}

	function settings_row( $ds ) {
	?>
		<h3 style="margin-top:1em;">Scroller:</h3>
		<table class="form-table">
			<tr valign="top">
				<td colspan="2">
					<?php
						echo GravityView_Admin_Views::render_field_option( 'datatables_settings[scroller]', array( 'label' => __( 'Enable Scroller', 'gravity-view' ), 'type' => 'checkbox', 'value' => 1 ), $ds['scroller'] );
					?>
				</td>
			</tr>
			<tr valign="top">
				<td scope="row">
					<label for="gravityview_dt_scrollerheight"><?php esc_html_e( 'Table Height', 'gravity-view'); ?></label>
				</td>
				<td>
					<input name="datatables_settings[scrolly]" id="gravityview_dt_scrollerheight" type="number" step="1" min="50" value="<?php empty( $ds['scrolly'] ) ? print 500 : print $ds['scrolly']; ?>" class="small-text">
				</td>
			</tr>
		</table>
	<?php
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

		// Disable the pagination menu
		//$dt_config['lengthMenu'] = -1;

		// init Scroller
		$dt_config['dom'] = empty( $dt_config['dom'] ) ? 'frtiS' : $dt_config['dom'].'S';

		// set table height
		$settings['scrolly'] = empty( $settings['scrolly'] ) ? '500' : (string)$settings['scrolly'];
		$dt_config['scrollY'] = empty( $dt_config['scrollY'] ) ? $settings['scrolly'] : $dt_config['scrollY'];
		$dt_config['scrollY'] .= 'px';

		do_action( 'gravityview_log_debug', '[scroller_add_config] Inserting Scroller config. Data:', $dt_config );

		return $dt_config;
	}

}

new GV_Extension_DataTables_Scroller;
