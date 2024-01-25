<?php
/**
 * GravityView preset template
 *
 * @file      class-gravityview-preset-business-data.php
 * @since     1.15
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

class GravityView_Preset_Business_Data extends GravityView_Default_Template_Table {
	const ID = 'preset_business_data';

	function __construct() {
		$settings = array(
			'slug'          => 'table',
			'type'          => 'preset',
			'label'         => __( 'Business Data', 'gk-gravityview' ),
			'description'   => __( 'Display business information in a table.', 'gk-gravityview' ),
			'logo'          => plugins_url( 'includes/presets/business-data/logo-business-data.png', GRAVITYVIEW_FILE ),
			// 'preview'       => 'http://demo.gravitykit.com/blog/view/business-table/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/business-data/form-business-data.json',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/business-data/fields-business-data.xml',
		);

		parent::__construct( self::ID, $settings );
	}
}

new GravityView_Preset_Business_Data();
