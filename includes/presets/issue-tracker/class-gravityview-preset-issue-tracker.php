<?php
/**
 * GravityView preset template
 *
 * @file class-gravityview-preset-issue-tracker.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15
 */

class GravityView_Preset_Issue_Tracker extends GravityView_Default_Template_Table {

	function __construct() {

		$id = 'preset_issue_tracker';

		$settings = array(
			'slug'          => 'table',
			'type'          => 'preset',
			'label'         => __( 'Issue Tracker', 'gravityview' ),
			'description'   => __( 'Manage issues and their statuses.', 'gravityview' ),
			'logo'          => plugins_url( 'includes/presets/issue-tracker/logo-issue-tracker.png', GRAVITYVIEW_FILE ),
			'preview'       => 'http://demo.gravityview.co/blog/view/issue-tracker/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/issue-tracker/form-issue-tracker.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/issue-tracker/fields-issue-tracker.xml'

		);

		parent::__construct( $id, $settings );

	}
}

new GravityView_Preset_Issue_Tracker;
