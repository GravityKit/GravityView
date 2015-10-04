<?php
/**
 * GravityView preset template
 *
 * @file class-gravityview-preset-website-showcase.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15
 */

class GravityView_Preset_Website_Showcase extends GravityView_Default_Template_List {

	function __construct() {

		$id = 'preset_website_showcase';

		$settings = array(
			'slug'          => 'list',
			'type'          => 'preset',
			'label'         => __( 'Website Showcase', 'gravityview' ),
			'description'   => __( 'Feature submitted websites with screenshots.', 'gravityview' ),
			'logo'          => plugins_url( 'includes/presets/website-showcase/logo-website-showcase.png', GRAVITYVIEW_FILE ),
			'preview'       => 'http://demo.gravityview.co/blog/view/website-showcase/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/website-showcase/form-website-showcase.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/website-showcase/fields-website-showcase.xml'
		);

		parent::__construct( $id, $settings );

	}
}

new GravityView_Preset_Website_Showcase;
