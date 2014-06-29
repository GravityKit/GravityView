<?php
/**
 * GravityView Extension -- DataTables -- Common functions
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.6
 */

class GV_Extension_DataTables_Common {

	// TableTools helper functions

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
			'pdf' => 'PDF',
			'print' => __( 'Print', 'gravity-view' )
		);
	}
}
