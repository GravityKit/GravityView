<?php

use GV\View;

/**
 * Widget to display a Gravity Forms form
 */
class GravityView_Widget_Gravity_Forms extends \GV\Widget {

	public $icon = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA1MDguMyA1NTkuNSIgZm9jdXNhYmxlPSJmYWxzZSIgYXJpYS1oaWRkZW49InRydWUiIGNsYXNzPSJkYXNoaWNvbiBkYXNoaWNvbi1ncmF2aXR5Zm9ybXMiIHJvbGU9ImltZyI+PGc+PHBhdGggY2xhc3M9InN0MCIgZD0iTTQ2OCwxMDkuOEwyOTQuNCw5LjZjLTIyLjEtMTIuOC01OC40LTEyLjgtODAuNSwwTDQwLjMsMTA5LjhDMTguMiwxMjIuNiwwLDE1NCwwLDE3OS41VjM4MAljMCwyNS42LDE4LjEsNTYuOSw0MC4zLDY5LjdsMTczLjYsMTAwLjJjMjIuMSwxMi44LDU4LjQsMTIuOCw4MC41LDBMNDY4LDQ0OS44YzIyLjItMTIuOCw0MC4zLTQ0LjIsNDAuMy02OS43VjE3OS42CUM1MDguMywxNTQsNDkwLjIsMTIyLjYsNDY4LDEwOS44eiBNMzk5LjMsMjQ0LjRsLTE5NS4xLDBjLTExLDAtMTkuMiwzLjItMjUuNiwxMGMtMTQuMiwxNS4xLTE4LjIsNDQuNC0xOS4zLDYwLjdIMzQ4di0yNi40aDQ5LjkJdjc2LjNIMTExLjNsLTEuOC0yM2MtMC4zLTMuMy01LjktODAuNywzMi44LTEyMS45YzE2LjEtMTcuMSwzNy4xLTI1LjgsNjIuNC0yNS44aDE5NC43VjI0NC40eiI+PC9wYXRoPjwvZz48L3N2Zz4=';

	/**
	 * Does this get displayed on a single entry?
	 * @var boolean
	 */
	protected $show_on_single = true;

	function __construct() {
		// Initialize widget in the frontend or when editing a View/performing widget AJAX action
		$doing_ajax   = defined( 'DOING_AJAX' ) && DOING_AJAX && 'gv_field_options' === \GV\Utils::_POST( 'action' );
		$editing_view = 'edit' === \GV\Utils::_GET( 'action' ) && 'gravityview' === get_post_type( \GV\Utils::_GET( 'post' ) );
		$is_frontend  = gravityview()->request->is_frontend();

		if ( ! $doing_ajax && ! $editing_view && ! $is_frontend ) {
			return;
		}

		$this->widget_description = __('Display a Gravity Forms form.', 'gk-gravityview' );

		$default_values = array(
			'header' => 1,
			'footer' => 1,
		);

		$settings = array(
			'widget_form_id' => array(
				'type' => 'select',
				'label' => __( 'Form to display', 'gk-gravityview' ),
				'value' => '',
				'options' => $this->_get_form_choices(),
			),
			'title' => array(
				'type' => 'checkbox',
				'label' => __( 'Show form title?', 'gk-gravityview' ),
				'value' => 1,
			),
			'description' => array(
				'type' => 'checkbox',
				'label' => __( 'Show form description?', 'gk-gravityview' ),
				'value' => 1,
			),
			'ajax' => array(
				'type' => 'checkbox',
				'label' => __( 'Enable AJAX', 'gk-gravityview' ),
				'desc' => '',
				'value' => 1,
			),
			'field_values' => array(
				'type' => 'text',
				'class' => 'code widefat',
				'label' => __( 'Field value parameters', 'gk-gravityview' ),
				'desc' => '<a href="https://docs.gravityforms.com/using-dynamic-population/" rel="external">' . esc_html__( 'Learn how to dynamically populate a field.', 'gk-gravityview' ) . '</a>',
				'value' => '',
			),
		);

		add_filter( 'gravityview/widget/hide_until_searched/allowlist', array( $this, 'add_to_allowlist' ) );

		parent::__construct( __( 'Gravity Forms', 'gk-gravityview' ) , 'gravityforms', $default_values, $settings );
	}

	/**
	 * Returns an array of active forms to show as choices for the widget
	 *
	 * @since 2.9.0.1
	 *
	 * @return array Array with key set to Form ID => Form Title, with `0` as default placeholder.
	 */
	private function _get_form_choices() {

		$choices = array(
			0 => '&mdash; ' . esc_html__( 'list of forms', 'gk-gravityview' ) . '&mdash;',
		);

		if ( ! class_exists( 'GFAPI' ) ) {
			return $choices;
		}

		if( gravityview()->request->is_frontend() ) {
			return $choices;
		}

		global $wpdb;

		$table = GFFormsModel::get_form_table_name();

		$results = $wpdb->get_results( "SELECT id, title FROM ${table} WHERE is_active = 1" );

		if ( ! empty( $results ) ) {
			foreach ( $results as $form ) {
				$choices[ $form->id ] = $form->title;
			}
		}

		return $choices;
	}

	/**
	 * Add widget to a list of allowed "Hide Until Searched" items
	 *
	 * @param array $allowlist Array of widgets to show before a search is performed, if the setting is enabled.
	 *
	 * @return array
	 */
	function add_to_allowlist( $allowlist ) {

		$allowlist[] = 'gravityforms';

		return $allowlist;
	}

	/**
	 * @param array $widget_args
	 * @param string $content
	 * @param string $context
	 */
	public function render_frontend( $widget_args, $content = '', $context = '') {

		if ( ! $this->pre_render_frontend() ) {
			return;
		}

		$form_id = \GV\Utils::get( $widget_args, 'widget_form_id', \GV\Utils::get( $widget_args, 'form_id' ) );

		if ( empty( $form_id ) ) {
			return;
		}

		$title       = \GV\Utils::get( $widget_args, 'title' );
		$description = \GV\Utils::get( $widget_args, 'description' );
		$field_values = \GV\Utils::get( $widget_args, 'field_values' );
		$ajax = \GV\Utils::get( $widget_args, 'ajax' );

		gravity_form( $form_id, ! empty( $title ), ! empty( $description ), false, $field_values, $ajax );

		// If the form has been submitted, show the confirmation above the form, then show the form again below.
		if ( isset( GFFormDisplay::$submission[ $form_id ] ) ) {

			unset( GFFormDisplay::$submission[ $form_id ] );

			gravity_form( $form_id, ! empty( $title ), ! empty( $description ), false, $field_values, $ajax );
		}
	}

}

new GravityView_Widget_Gravity_Forms;
