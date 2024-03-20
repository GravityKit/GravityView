<?php
/**
 * GravityView preset template
 *
 * @file      class-gravityview-preset-business-listings.php
 * @since     1.15
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

class GravityView_Preset_Business_Listings extends GravityView_Default_Template_List {
	const ID = 'preset_business_listings';

	function __construct() {
		$settings = array(
			'slug'          => 'list',
			'type'          => 'preset',
			'label'         => __( 'Business Listing', 'gk-gravityview' ),
			'description'   => __( 'Display business profiles.', 'gk-gravityview' ),
			'logo'          => plugins_url( 'includes/presets/business-listings/logo-business-listings.png', GRAVITYVIEW_FILE ),
			// 'preview'       => 'http://demo.gravitykit.com/blog/view/business-listings/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/business-listings/form-business-listings.json',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/business-listings/fields-business-listings.xml',
		);

		parent::__construct( self::ID, $settings );
	}
}

new GravityView_Preset_Business_Listings();
