<?php
/**
 * GravityView preset template
 *
 * @file class-gravityview-preset-business-listings.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15
 */

class GravityView_Preset_Business_Listings extends GravityView_Default_Template_List {

	function __construct() {

		$id = 'preset_business_listings';

		$settings = array(
			'slug'          => 'list',
			'type'          => 'preset',
			'label'         => __( 'Business Listings', 'gravityview' ),
			'description'   => __( 'Display business profiles.', 'gravityview' ),
			'logo'          => plugins_url( 'includes/presets/business-listings/logo-business-listings.png', GRAVITYVIEW_FILE ),
			'preview'       => 'http://demo.gravityview.co/blog/view/business-listings/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/business-listings/form-business-listings.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/business-listings/fields-business-listings.xml'
		);

		parent::__construct( $id, $settings );

	}
}

new GravityView_Preset_Business_Listings;
