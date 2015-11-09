<?php
/**
 * GravityView preset template
 *
 * @file class-gravityview-preset-resume-board.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15
 */

class GravityView_Preset_Resume_Board extends GravityView_Default_Template_Table {

	function __construct() {

		$id = 'preset_resume_board';

		$settings = array(
			'slug'          => 'table',
			'type'          => 'preset',
			'label'         => __( 'Resume Board', 'gravityview' ),
			'description'   => __( 'Allow job-seekers to post their resumes.', 'gravityview' ),
			'logo'          => plugins_url( 'includes/presets/resume-board/logo-resume-board.png', GRAVITYVIEW_FILE ),
			'preview'       => 'http://demo.gravityview.co/blog/view/resume-board/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/resume-board/form-resume-board.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/resume-board/fields-resume-board.xml'
		);

		parent::__construct( $id, $settings );

	}
}

new GravityView_Preset_Resume_Board;
