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

		$table_settings = [
			'slug'        => 'table',
			'type'        => 'custom',
			'label'       => __( 'Table', 'gk-gravityview' ),
			'description' => __( 'Display items in a table view.', 'gk-gravityview' ),
			'logo'        => plugins_url( 'includes/presets/default-table/logo-default-table.png', GRAVITYVIEW_FILE ),
			'icon'        => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzYiIGhlaWdodD0iMzQiIHZpZXdCb3g9IjAgMCAzNiAzNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik0zIDMzSDMyLjZDMzMuNzA0NiAzMyAzNC42IDMyLjEwNDYgMzQuNiAzMVYzQzM0LjYgMS44OTU0MyAzMy43MDQ2IDEgMzIuNiAxSDNDMS44OTU0MyAxIDEgMS44OTU0MyAxIDNWMzFDMSAzMi4xMDQ2IDEuODk1NDMgMzMgMyAzM1oiIGZpbGw9IndoaXRlIiBzdHJva2U9IiMyQzMzMzgiIHN0cm9rZS13aWR0aD0iMS41IiBzdHJva2UtbWl0ZXJsaW1pdD0iMTAiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgogICAgPHBhdGggZD0iTTUuMzk5OSAxOC4ySDEyLjU5OTkiIHN0cm9rZT0iIzJDMzMzOCIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+CiAgICA8cGF0aCBkPSJNNS4zOTk5IDI3LjhIMTIuNTk5OSIgc3Ryb2tlPSIjMkMzMzM4IiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLW1pdGVybGltaXQ9IjEwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICAgIDxwYXRoIGQ9Ik01LjM5OTkgMjNIMTIuNTk5OSIgc3Ryb2tlPSIjMkMzMzM4IiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLW1pdGVybGltaXQ9IjEwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICAgIDxwYXRoIGQ9Ik01LjM5OTkgN0gxMi41OTk5IiBzdHJva2U9IiMyQzMzMzgiIHN0cm9rZS13aWR0aD0iMS41IiBzdHJva2UtbWl0ZXJsaW1pdD0iMTAiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgogICAgPHBhdGggZD0iTTIwLjYwMDEgMTguMkgyMi4yMDAxIiBzdHJva2U9IiMyQzMzMzgiIHN0cm9rZS13aWR0aD0iMS41IiBzdHJva2UtbWl0ZXJsaW1pdD0iMTAiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgogICAgPHBhdGggZD0iTTIwLjYwMDEgMjcuOEgyMi4yMDAxIiBzdHJva2U9IiMyQzMzMzgiIHN0cm9rZS13aWR0aD0iMS41IiBzdHJva2UtbWl0ZXJsaW1pdD0iMTAiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgogICAgPHBhdGggZD0iTTIwLjYwMDEgMjNIMjIuMjAwMSIgc3Ryb2tlPSIjMkMzMzM4IiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLW1pdGVybGltaXQ9IjEwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICAgIDxwYXRoIGQ9Ik0yMC42MDAxIDdIMjIuMjAwMSIgc3Ryb2tlPSIjMkMzMzM4IiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLW1pdGVybGltaXQ9IjEwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICAgIDxwYXRoIGQ9Ik0yOS4zOTk5IDE4LjJIMzAuOTk5OSIgc3Ryb2tlPSIjMkMzMzM4IiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLW1pdGVybGltaXQ9IjEwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICAgIDxwYXRoIGQ9Ik0yOS4zOTk5IDI3LjhIMzAuOTk5OSIgc3Ryb2tlPSIjMkMzMzM4IiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLW1pdGVybGltaXQ9IjEwIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICAgIDxwYXRoIGQ9Ik0yOS4zOTk5IDIzSDMwLjk5OTkiIHN0cm9rZT0iIzJDMzMzOCIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+CiAgICA8cGF0aCBkPSJNMjkuMzk5OSA3SDMwLjk5OTkiIHN0cm9rZT0iIzJDMzMzOCIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+CiAgICA8cGF0aCBkPSJNMTcgMUwxNyAzMi4yIiBzdHJva2U9IiMyQzMzMzgiIHN0cm9rZS13aWR0aD0iMS41IiBzdHJva2UtbWl0ZXJsaW1pdD0iMTAiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgogICAgPHBhdGggZD0iTTI1Ljc5OTggMUwyNS43OTk4IDMyLjIiIHN0cm9rZT0iIzJDMzMzOCIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+CiAgICA8cGF0aCBkPSJNMSAxMi42MDAxSDM0LjYiIHN0cm9rZT0iIzJDMzMzOCIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1taXRlcmxpbWl0PSIxMCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+Cjwvc3ZnPgo=',
			'css_source'  => gravityview_css_url( $css_filename, GRAVITYVIEW_DIR . 'templates/css/' ),
		];

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
