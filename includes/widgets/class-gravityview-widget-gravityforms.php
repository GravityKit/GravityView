<?php

/**
 * Widget to display a Gravity Forms form
 */
class GravityView_Widget_Gravity_Forms extends \GV\Widget {

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

		/**
		 * gravityview_get_forms() is currently running too early as widgets_init runs before init and
		 * when most Gravity Forms plugins register their own fields like GP Terms of Service.
		 */
		if( \GV\Admin_Request::is_admin() && ! GFForms::is_gravity_page() ) {

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
