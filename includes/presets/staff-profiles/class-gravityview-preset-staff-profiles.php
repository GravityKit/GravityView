<?php
/**
 * GravityView preset template
 *
 * @file      class-gravityview-preset-staff-profiles.php
 * @since     1.15
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

class GravityView_Preset_Staff_Profiles extends GravityView_Default_Template_List {
	const ID = 'preset_staff_profiles';

	function __construct() {
		$settings = array(
			'slug'          => 'list',
			'type'          => 'preset',
			'label'         => __( 'Staff Profiles', 'gk-gravityview' ),
			'description'   => __( 'List members of your team.', 'gk-gravityview' ),
			'logo'          => plugins_url( 'includes/presets/staff-profiles/logo-staff-profiles.png', GRAVITYVIEW_FILE ),
			// 'preview'       => 'https://site.try.gravitykit.com/view/staff-profiles/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/staff-profiles/form-staff-profiles.json',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/staff-profiles/fields-staff-profiles.xml',
		);

		parent::__construct( self::ID, $settings );
	}
}

new GravityView_Preset_Staff_Profiles();
