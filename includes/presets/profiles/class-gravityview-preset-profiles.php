<?php
/**
 * GravityView preset template
 *
 * @file      class-gravityview-preset-profiles.php
 * @since     1.15
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

class GravityView_Preset_Profiles extends GravityView_Default_Template_List {
	const ID = 'preset_profiles';

	function __construct() {
		$settings = array(
			'slug'          => 'list',
			'type'          => 'preset',
			'label'         => __( 'People Profiles', 'gk-gravityview' ),
			'description'   => __( 'List people with individual profiles.', 'gk-gravityview' ),
			'logo'          => plugins_url( 'includes/presets/profiles/logo-profiles.png', GRAVITYVIEW_FILE ),
			// 'preview'       => 'https://site.try.gravitykit.com/member-directory/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/profiles/form-profiles.json',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/profiles/fields-profiles.xml',
		);

		parent::__construct( self::ID, $settings );
	}
}

new GravityView_Preset_Profiles();
