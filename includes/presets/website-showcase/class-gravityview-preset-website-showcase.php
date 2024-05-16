<?php
/**
 * GravityView preset template
 *
 * @file      class-gravityview-preset-website-showcase.php
 * @since     1.15
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

class GravityView_Preset_Website_Showcase extends GravityView_Default_Template_List {
	const ID = 'preset_website_showcase';

	function __construct() {
		$settings = array(
			'slug'          => 'list',
			'type'          => 'preset',
			'label'         => __( 'Website Showcase', 'gk-gravityview' ),
			'description'   => __( 'Feature submitted websites with screenshots.', 'gk-gravityview' ),
			'logo'          => plugins_url( 'includes/presets/website-showcase/logo-website-showcase.png', GRAVITYVIEW_FILE ),
			// 'preview'       => 'http://demo.gravitykit.com/blog/view/website-showcase/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/website-showcase/form-website-showcase.json',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/website-showcase/fields-website-showcase.xml',
		);

		parent::__construct( self::ID, $settings );
	}
}

new GravityView_Preset_Website_Showcase();
