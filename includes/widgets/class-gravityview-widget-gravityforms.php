<?php

/**
 * Widget to display a Gravity Forms form
 */
class GravityView_Widget_Gravity_Forms extends \GV\Widget {

	public $icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSItMTUgNzcgNTgxIDY0MCIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAtMTUgNzcgNTgxIDY0MCIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PGcgaWQ9IkxheWVyXzIiPjxwYXRoIGZpbGw9IiM2Qzc3ODEiIGQ9Ik00ODkuNSwyMjdMNDg5LjUsMjI3TDMxNS45LDEyNi44Yy0yMi4xLTEyLjgtNTguNC0xMi44LTgwLjUsMEw2MS44LDIyN2MtMjIuMSwxMi44LTQwLjMsNDQuMi00MC4zLDY5Ljd2MjAwLjVjMCwyNS42LDE4LjEsNTYuOSw0MC4zLDY5LjdsMTczLjYsMTAwLjJjMjIuMSwxMi44LDU4LjQsMTIuOCw4MC41LDBMNDg5LjUsNTY3YzIyLjItMTIuOCw0MC4zLTQ0LjIsNDAuMy02OS43VjI5Ni44QzUyOS44LDI3MS4yLDUxMS43LDIzOS44LDQ4OS41LDIyN3ogTTQwMSwzMDAuNHY1OS4zSDI0MXYtNTkuM0g0MDF6IE0xNjMuMyw0OTAuOWMtMTYuNCwwLTI5LjYtMTMuMy0yOS42LTI5LjZjMC0xNi40LDEzLjMtMjkuNiwyOS42LTI5LjZzMjkuNiwxMy4zLDI5LjYsMjkuNkMxOTIuOSw0NzcuNiwxNzkuNiw0OTAuOSwxNjMuMyw0OTAuOXogTTE2My4zLDM1OS43Yy0xNi40LDAtMjkuNi0xMy4zLTI5LjYtMjkuNnMxMy4zLTI5LjYsMjkuNi0yOS42czI5LjYsMTMuMywyOS42LDI5LjZTMTc5LjYsMzU5LjcsMTYzLjMsMzU5Ljd6IE0yNDEsNDkwLjl2LTU5LjNoMTYwdjU5LjNIMjQxeiIvPjwvZz48L3N2Zz4=';

	/**
	 * Does this get displayed on a single entry?
	 * @var boolean
	 */
	protected $show_on_single = true;

	function __construct() {

		$this->widget_description = __('Display a Gravity Forms form.', 'gravityview' );

		$default_values = array(
			'header' => 1,
			'footer' => 1,
		);

		$settings = array(
			'form_id' => array(
				'type' => 'select',
				'label' => __( 'Form to display', 'gravityview' ),
				'value' => '',
				'options' => $this->_get_form_choices(),
			),
			'title' => array(
				'type' => 'checkbox',
				'label' => __( 'Show form title?', 'gravityview' ),
				'value' => 1,
			),
			'description' => array(
				'type' => 'checkbox',
				'label' => __( 'Show form description?', 'gravityview' ),
				'value' => 1,
			),
			'ajax' => array(
				'type' => 'checkbox',
				'label' => __( 'Enable AJAX', 'gravityview' ),
				'desc' => '',
				'value' => 1,
			),
			'field_values' => array(
				'type' => 'text',
				'class' => 'code',
				'label' => __( 'Field value parameters', 'gravityview' ),
				'desc' => '',
				'value' => '',
			),
		);

		add_filter( 'gravityview/widget/hide_until_searched/whitelist', array( $this, 'add_to_allowlist' ) );

		parent::__construct( __( 'Gravity Forms', 'gravityview' ) , 'gravityforms', $default_values, $settings );
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
			0 => '&mdash; ' . esc_html__( 'list of forms', 'gravityview' ) . '&mdash;',
		);

		if ( ! class_exists( 'GFAPI' ) ) {
			return $choices;
		}

		// Inside GV's widget AJAX request
		$doing_ajax = defined( 'DOING_AJAX' ) && 'gv_field_options' === \GV\Utils::_POST( 'action' );

		/**
		 * gravityview_get_forms() is currently running too early as widgets_init runs before init and
		 * when most Gravity Forms plugins register their own fields like GP Terms of Service.
		 */
		if( $doing_ajax || ( \GV\Admin_Request::is_admin() && ! GFForms::is_gravity_page() ) ) {

			// check for available gravity forms
			$forms = gravityview_get_forms();

			foreach ( $forms as $form ) {
				$choices[ $form['id'] ] = $form['title'];
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

		$form_id = \GV\Utils::get( $widget_args, 'form_id' );

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

			gravity_form( $form_id, ! empty( $description ), ! empty( $title ) );
		}
	}

}

new GravityView_Widget_Gravity_Forms;
