<?php

/**
 * GravityView_Default_Template_List class.
 * Defines List (default) template
 */
class GravityView_Default_Template_List extends GravityView_Template {

	function __construct( $id = 'default_list', $settings = array(), $field_options = array(), $areas = array() ) {

		$rtl = is_rtl() ? '-rtl' : '';

		$list_settings = array(
			'slug'        => 'list',
			'type'        => 'custom',
			'label'       => __( 'List (default)', 'gravityview' ),
			'description' => __( 'Display items in a listing view.', 'gravityview' ),
			'logo'        => plugins_url( 'includes/presets/default-list/logo-default-list.png', GRAVITYVIEW_FILE ),
			'css_source'  => gravityview_css_url( 'list-view' . $rtl . '.css', GRAVITYVIEW_DIR . 'templates/css/' ),
		);

		$settings = wp_parse_args( $settings, $list_settings );

		$field_options = array(
			'show_as_link' => array(
				'type'    => 'checkbox',
				'label'   => __( 'Link to single entry', 'gravityview' ),
				'value'   => false,
				'context' => 'directory'
			),
		);

		$areas = array(
			array(
				'1-1' => array(
					array(
						'areaid'   => 'list-title',
						'title'    => __( 'Listing Title', 'gravityview' ),
						'subtitle' => ''
					),
					array(
						'areaid'   => 'list-subtitle',
						'title'    => __( 'Subheading', 'gravityview' ),
						'subtitle' => __( 'Data placed here will be bold.', 'gravityview' ),
					),
				),
				'1-3' => array(
					array(
						'areaid'   => 'list-image',
						'title'    => __( 'Image', 'gravityview' ),
						'subtitle' => __( 'Leave empty to remove.', 'gravityview' ),
					)
				),
				'2-3' => array(
					array(
						'areaid'   => 'list-description',
						'title'    => __( 'Other Fields', 'gravityview' ),
						'subtitle' => __( 'Below the subheading, a good place for description and other data.', 'gravityview' ),
					)
				)
			),
			array(
				'1-2' => array(
					array(
						'areaid'   => 'list-footer-left',
						'title'    => __( 'Footer Left', 'gravityview' ),
						'subtitle' => ''
					)
				),
				'2-2' => array(
					array(
						'areaid'   => 'list-footer-right',
						'title'    => __( 'Footer Right', 'gravityview' ),
						'subtitle' => ''
					)
				)
			)
		);

		parent::__construct( $id, $settings, $field_options, $areas );

	}
}

new GravityView_Default_Template_List;
