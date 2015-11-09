<?php
/**
 * GravityView preset template
 *
 * @file class-gravityview-preset-business-data.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15
 */

class GravityView_Preset_Business_Data extends GravityView_Default_Template_Table {

	function __construct() {

		$id = 'preset_business_data';

		$settings = array(
			'slug'          => 'table',
			'type'          => 'preset',
			'label'         => __( 'Business Data', 'gravityview' ),
			'description'   => __( 'Display business information in a table.', 'gravityview' ),
			'logo'          => plugins_url( 'includes/presets/business-data/logo-business-data.png', GRAVITYVIEW_FILE ),
			'preview'       => 'http://demo.gravityview.co/blog/view/business-table/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/business-data/form-business-data.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/business-data/fields-business-data.xml'
		);

		parent::__construct( $id, $settings );
	}
}

new GravityView_Preset_Business_Data;
