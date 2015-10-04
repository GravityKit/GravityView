<?php
/**
 * GravityView preset template
 *
 * @file class-gravityview-preset-event-listings.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15
 */

class GravityView_Preset_Event_Listings extends GravityView_Default_Template_List {

	function __construct() {

		$id = 'preset_event_listings';

		$settings = array(
			'slug'          => 'list',
			'type'          => 'preset',
			'label'         => __( 'Event Listings', 'gravityview' ),
			'description'   => __( 'Present a list of your events.', 'gravityview' ),
			'logo'          => plugins_url( 'includes/presets/event-listings/logo-event-listings.png', GRAVITYVIEW_FILE ),
			'preview'       => 'http://demo.gravityview.co/blog/view/event-listings/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/event-listings/form-event-listings.xml',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/event-listings/fields-event-listings.xml'
		);

		parent::__construct( $id, $settings );

	}
}

new GravityView_Preset_Event_Listings;
