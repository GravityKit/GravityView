<?php
/**
 * GravityView preset template
 *
 * @file class-gravityview-preset-resume-board.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15
 */

class GravityView_Preset_Resume_Board extends GravityView_Default_Template_Table {
	const ID = 'preset_resume_board';

	function __construct() {
		$settings = array(
			'slug'          => 'table',
			'type'          => 'preset',
			'label'         => __( 'Resume Board', 'gk-gravityview' ),
			'description'   => __( 'Allow job-seekers to post their resumes.', 'gk-gravityview' ),
			'logo'          => plugins_url( 'includes/presets/resume-board/logo-resume-board.png', GRAVITYVIEW_FILE ),
			// 'preview'       => 'http://demo.gravitykit.com/blog/view/resume-board/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/resume-board/form-resume-board.json',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/resume-board/fields-resume-board.xml',
		);

		parent::__construct( self::ID, $settings );
	}
}

new GravityView_Preset_Resume_Board();
