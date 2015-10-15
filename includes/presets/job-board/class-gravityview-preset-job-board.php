<?php
/**
 * GravityView preset template
 *
 * @file class-gravityview-preset-job-board.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15
 */

class GravityView_Preset_Job_Board extends GravityView_Default_Template_List {

	function __construct() {

		$id = 'preset_job_board';

		$settings = array(
			'slug'          => 'list',
			'type'          => 'preset',
			'label'         => __( 'Job Board', 'gravityview' ),
			'description'   => __( 'Post available jobs in a simple job board.', 'gravityview' ),
			'logo'          => plugins_url( 'includes/presets/job-board/logo-job-board.png', GRAVITYVIEW_FILE ),
			'preview'       => 'http://demo.gravityview.co/blog/view/job-board/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/job-board/form-job-board.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/job-board/fields-job-board.xml'

		);

		parent::__construct( $id, $settings );
	}
}

new GravityView_Preset_Job_Board;
