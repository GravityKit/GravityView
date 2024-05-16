<?php
/**
 * GravityView preset template
 *
 * @file      class-gravityview-preset-job-board.php
 * @since     1.15
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

class GravityView_Preset_Job_Board extends GravityView_Default_Template_List {
	const ID = 'preset_job_board';

	function __construct() {
		$settings = array(
			'slug'          => 'list',
			'type'          => 'preset',
			'label'         => __( 'Job Board', 'gk-gravityview' ),
			'description'   => __( 'Post available jobs in a simple job board.', 'gk-gravityview' ),
			'logo'          => plugins_url( 'includes/presets/job-board/logo-job-board.png', GRAVITYVIEW_FILE ),
			// 'preview'       => 'https://site.try.gravitykit.com/job-board/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/job-board/form-job-board.json',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/job-board/fields-job-board.xml',
		);

		parent::__construct( self::ID, $settings );
	}
}

new GravityView_Preset_Job_Board();
