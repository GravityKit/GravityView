<?php

/**
 * GravityView_Default_Template_Table class.
 * Defines Table(default) template
 */
class GravityView_Default_Template_Table extends GravityView_Template {

	function __construct( $id = 'default_table', $settings = array(), $field_options = array(), $areas = array() ) {

		/**
		 * Should GravityView use the legacy Table layout stylesheet (from before Version 2.1)?
		 *
		 * @since 2.1.1
		 * @param bool $use_legacy_table_style If true, loads `table-view-legacy.css`. If false, loads `table-view.css`. Default: `false`
		 */
		$use_legacy_table_style = apply_filters( 'gravityview/template/table/use-legacy-style', false );

		$css_filename = 'table-view.css';

		if ( $use_legacy_table_style ) {
			$css_filename = 'table-view-legacy.css';
		}

		$table_settings = array(
			'slug'        => 'table',
			'type'        => 'custom',
			'label'       => __( 'Table', 'gk-gravityview' ),
			'description' => __( 'Display items in a table view.', 'gk-gravityview' ),
			'logo'        => plugins_url( 'includes/presets/default-table/logo-default-table.png', GRAVITYVIEW_FILE ),
			'css_source'  => gravityview_css_url( $css_filename, GRAVITYVIEW_DIR . 'templates/css/' ),
		);

		$settings = wp_parse_args( $settings, $table_settings );

		/**
		 * @see  GravityView_Admin_Views::get_default_field_options() for Generic Field Options
		 * @var array
		 */
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
						'areaid'   => 'table-columns',
						'title'    => __( 'Visible Table Columns', 'gk-gravityview' ),
						'subtitle' => __( 'Each field will be displayed as a column in the table.', 'gk-gravityview' ),
					),
				),
			),
		);

		$this->add_hooks();

		parent::__construct( $id, $settings, $field_options, $areas );
	}

	/**
	 * Adds hooks specific to this template
	 *
	 * @since 2.8.1
	 */
	private function add_hooks() {
		add_filter( 'gravityview/admin/add_button_label', array( $this, 'maybe_modify_button_label' ), 10, 2 );
	}

	/**
	 * Changes the button label to reflect that fields = rows
	 *
	 * @internal
	 *
	 * @param string $label Text for button: "Add Widget" or "Add Field"
	 * @param array  $atts {
	 *   @type string $type 'widget' or 'field'
	 *   @type string $template_id The current slug of the selected View template
	 *   @type string $zone Where is this button being shown? Either 'single', 'directory', 'edit', 'header', 'footer'
	 * }
	 *
	 * @return string|void
	 */
	public function maybe_modify_button_label( $label = '', $atts = array() ) {

		if ( $this->template_id !== \GV\Utils::get( $atts, 'template_id' ) ) {
			return $label;
		}

		if ( 'field' !== \GV\Utils::get( $atts, 'type' ) ) {
			return $label;
		}

		if ( 'edit' === \GV\Utils::get( $atts, 'zone' ) ) {
			return $label;
		}

		return __( 'Add Table Column', 'gk-gravityview' );
	}
}

new GravityView_Default_Template_Table();
