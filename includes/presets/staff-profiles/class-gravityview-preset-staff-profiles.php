<?php
/**
 * GravityView preset template
 *
 * @file class-gravityview-preset-staff-profiles.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15
 */

class GravityView_Preset_Staff_Profiles extends GravityView_Default_Template_List {

	function __construct() {

		$id = 'preset_staff_profiles';

		$settings = array(
			'slug'          => 'list',
			'type'          => 'preset',
			'label'         => __( 'Staff Profiles', 'gravityview' ),
			'description'   => __( 'List members of your team.', 'gravityview' ),
			'logo'          => plugins_url( 'includes/presets/staff-profiles/logo-staff-profiles.png', GRAVITYVIEW_FILE ),
			'preview'       => 'http://demo.gravityview.co/blog/view/staff-profiles/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/staff-profiles/form-staff-profiles.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/staff-profiles/fields-staff-profiles.xml',
		);

		parent::__construct( $id, $settings );

	}
}

new GravityView_Preset_Staff_Profiles;
