<?php
/**
 * GravityView preset template
 *
 * @file      class-gravityview-preset-event-listings.php
 * @since     1.15
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @package   GravityView
 */

class GravityView_Preset_Event_Listings extends GravityView_Default_Template_List {
	const ID = 'preset_event_listings';

	function __construct() {
		$settings = array(
			'slug'          => 'list',
			'type'          => 'preset',
			'label'         => __( 'Event Listings', 'gk-gravityview' ),
			'description'   => __( 'Present a list of your events.', 'gk-gravityview' ),
			'logo'          => plugins_url( 'includes/presets/event-listings/logo-event-listings.png', GRAVITYVIEW_FILE ),
			// 'preview'       => 'http://demo.gravitykit.com/blog/view/event-listings/',
			'preset_form'   => GRAVITYVIEW_DIR . 'includes/presets/event-listings/form-event-listings.json',
			'preset_fields' => GRAVITYVIEW_DIR . 'includes/presets/event-listings/fields-event-listings.xml',
		);

		parent::__construct( self::ID, $settings );
	}
}

new GravityView_Preset_Event_Listings();
