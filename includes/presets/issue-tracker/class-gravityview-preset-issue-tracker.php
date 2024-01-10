<?php
/**
 * GravityView preset template
 *
 * @file      class-gravityview-preset-issue-tracker.php
 * @since     1.15
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

class GravityView_Preset_Issue_Tracker extends GravityView_Default_Template_Table {
	const ID = 'preset_issue_tracker';

	function __construct() {
		$settings = array(
			'slug'          => 'table',
			'type'          => 'preset',
			'label'         => __( 'Issue Tracker', 'gk-gravityview' ),
			'description'   => __( 'Manage issues and their statuses.', 'gk-gravityview' ),
			'logo'          => plugins_url( 'includes/presets/issue-tracker/logo-issue-tracker.png', GRAVITYVIEW_FILE ),
			// 'preview'       => 'https://site.try.gravitykit.com/task-management/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/issue-tracker/form-issue-tracker.json',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/issue-tracker/fields-issue-tracker.xml',
		);

		parent::__construct( self::ID, $settings );
	}
}

new GravityView_Preset_Issue_Tracker();
