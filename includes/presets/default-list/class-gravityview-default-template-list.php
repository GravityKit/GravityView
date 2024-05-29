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
			'label'       => __( 'List', 'gk-gravityview' ),
			'description' => __( 'Display items in a listing view.', 'gk-gravityview' ),
			'logo'        => plugins_url( 'includes/presets/default-list/logo-default-list.png', GRAVITYVIEW_FILE ),
			'icon'        => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzQiIGhlaWdodD0iMzQiIHZpZXdCb3g9IjAgMCAzNCAzNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik0zIDMzSDMxQzMyLjEwNDYgMzMgMzMgMzIuMTA0NiAzMyAzMVYzQzMzIDEuODk1NDMgMzIuMTA0NiAxIDMxIDFIM0MxLjg5NTQzIDEgMSAxLjg5NTQzIDEgM1YzMUMxIDMyLjEwNDYgMS44OTU0MyAzMyAzIDMzWiIgZmlsbD0id2hpdGUiIHN0cm9rZT0iIzJDMzMzOCIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+CiAgICA8cGF0aCBkPSJNNS4zOTk5IDguMTk5OTVINi45OTk5IiBzdHJva2U9IiMyQzMzMzgiIHN0cm9rZS13aWR0aD0iMS41IiBzdHJva2UtbWl0ZXJsaW1pdD0iMTAiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgogICAgPHBhdGggZD0iTTUuMzk5OSAxMy44SDYuOTk5OSIgc3Ryb2tlPSIjMkMzMzM4IiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLW1pdGVybGltaXQ9IjEwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICAgIDxwYXRoIGQ9Ik0xMSA4LjE5OTk1SDI3LjgiIHN0cm9rZT0iIzJDMzMzOCIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+CiAgICA8cGF0aCBkPSJNMTEgMTMuOEgyNy44IiBzdHJva2U9IiMyQzMzMzgiIHN0cm9rZS13aWR0aD0iMS41IiBzdHJva2UtbWl0ZXJsaW1pdD0iMTAiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgogICAgPHBhdGggZD0iTTUuMzk5OSAxOS4zOTk5SDYuOTk5OSIgc3Ryb2tlPSIjMkMzMzM4IiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLW1pdGVybGltaXQ9IjEwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICAgIDxwYXRoIGQ9Ik0xMSAxOS4zOTk5SDI3LjgiIHN0cm9rZT0iIzJDMzMzOCIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+CiAgICA8cGF0aCBkPSJNNS4zOTk5IDI1SDYuOTk5OSIgc3Ryb2tlPSIjMkMzMzM4IiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLW1pdGVybGltaXQ9IjEwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICAgIDxwYXRoIGQ9Ik0xMSAyNUgyNy44IiBzdHJva2U9IiMyQzMzMzgiIHN0cm9rZS13aWR0aD0iMS41IiBzdHJva2UtbWl0ZXJsaW1pdD0iMTAiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8L3N2Zz4K',
			'css_source'  => gravityview_css_url( 'list-view' . $rtl . '.css', GRAVITYVIEW_DIR . 'templates/css/' ),
		);

		$settings = wp_parse_args( $settings, $list_settings );

		$field_options = array(
			'show_as_link' => array(
				'type'     => 'checkbox',
				'label'    => __( 'Link to single entry', 'gk-gravityview' ),
				'value'    => false,
				'context'  => 'directory',
				'priority' => 1190,
				'group'    => 'display',
			),
		);

		$areas = array(
			array(
				'1-1' => array(
					array(
						'areaid'   => 'list-title',
						'title'    => __( 'Listing Title', 'gk-gravityview' ),
						'subtitle' => '',
					),
					array(
						'areaid'   => 'list-subtitle',
						'title'    => __( 'Subheading', 'gk-gravityview' ),
						'subtitle' => __( 'Data placed here will be bold.', 'gk-gravityview' ),
					),
				),
				'1-3' => array(
					array(
						'areaid'   => 'list-image',
						'title'    => __( 'Image', 'gk-gravityview' ),
						'subtitle' => __( 'Leave empty to remove.', 'gk-gravityview' ),
					),
				),
				'2-3' => array(
					array(
						'areaid'   => 'list-description',
						'title'    => __( 'Other Fields', 'gk-gravityview' ),
						'subtitle' => __( 'Below the subheading, a good place for description and other data.', 'gk-gravityview' ),
					),
				),
			),
			array(
				'1-2' => array(
					array(
						'areaid'   => 'list-footer-left',
						'title'    => __( 'Footer Left', 'gk-gravityview' ),
						'subtitle' => '',
					),
				),
				'2-2' => array(
					array(
						'areaid'   => 'list-footer-right',
						'title'    => __( 'Footer Right', 'gk-gravityview' ),
						'subtitle' => '',
					),
				),
			),
		);

		parent::__construct( $id, $settings, $field_options, $areas );
	}
}

new GravityView_Default_Template_List();
