<?php

add_action( 'init', function () {
	/**
	 * GravityView_Default_Template_Table class.
	 * Defines Table(default) template
	 */
	class GravityView_Upsell_Template_Placeholder extends GravityView_Template {

		function __construct( $id = 'template_upsell', $settings = array() ) {

			if ( class_exists( 'GravityView_Maps_Template_Map_Default' ) ) {
				return;
			}

			$table_settings = array(
				'slug'        => 'map_upsell',
				'type'        => 'custom',
				'label'       => __( 'Map!', 'gravityview' ),
				'description' => __( 'NOT INCLUDED IN YOUR FUCKING PLAN', 'gravityview' ),
				'logo'        => plugins_url( 'includes/presets/default-table/logo-default-table.png', GRAVITYVIEW_FILE ),
				'buy_source'  => 'https://gravityview.co/extensions/map/',
			);

			$settings = wp_parse_args( $settings, $table_settings );


			parent::__construct( $id, $settings, array(), array() );

		}
	}
}, 20 );

add_action( 'init', 'gravityview_register_upsell_templates', 40 );

function gravityview_register_upsell_templates() {

	$upsells = array(
		'GravityView_DataTables_Template' => array(
			'slug' => 'table-dt',
			'label' =>  __( 'DataTables Table', 'gv-datatables', 'gravityview' ),
			'description' => __('Display items in a dynamic table powered by DataTables.', 'gv-datatables', 'gravityview'),
			'logo' => plugins_url('assets/images/templates/logo-datatables.png', GRAVITYVIEW_FILE ),
		),
		'GravityView_Maps_Template_Map_Default' => array(
			'slug' => 'map',
			'label' =>  __( 'Map', 'gravityview-maps', 'gravityview' ),
			'description' => __( 'Display entries on a map.', 'gravityview-maps', 'gravityview' ),
			'logo' => plugins_url( 'assets/images/templates/default-map.png', GRAVITYVIEW_FILE ),
		),
		'GravityView_DIY_Template' => array(
			'slug'        => 'diy',
			'label'       => _x( 'DIY', 'DIY means "Do It Yourself"', 'gravityview-diy', 'gravityview' ),
			'description' => esc_html__( 'A flexible, powerful layout for designers & developers.', 'gravityview-diy', 'gravityview' ),
			'logo' => plugins_url( 'assets/images/templates/logo-diy.png', GRAVITYVIEW_FILE ),
		),
	);

	foreach ( $upsells as $upsell ) {

		if ( class_exists( 'GravityView_Maps_Template_Map_Default' ) ) {
			continue;
		}

		$upsell['type'] = 'custom';

		new GravityView_Upsell_Template_Placeholder( $upsell['slug'], $upsell );
	}

}
