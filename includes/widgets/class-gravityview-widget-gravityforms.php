<?php

use GV\View;

/**
 * Widget to display a Gravity Forms form
 */
class GravityView_Widget_Gravity_Forms extends \GV\Widget {

	/**
	 * @var string
	 * @since TODO
	 */
	public $widget_id = 'gravityforms';

	/**
	 * @var string
	 * @since 2.19
	 */
	public $icon = 'data:image/svg+xml,%3Csvg%20enable-background%3D%22new%200%200%20391.6%20431.1%22%20viewBox%3D%220%200%20391.6%20431.1%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22m391.6%20292.8c0%2019.7-14%2043.9-31%2053.7l-133.8%2077.2c-17.1%209.9-45%209.9-62%200l-133.8-77.2c-17.1-9.9-31-34-31-53.7v-154.5c0-19.7%2013.9-43.9%2031-53.7l133.8-77.2c17.1-9.9%2045-9.9%2062%200l133.7%2077.2c17.1%209.8%2031%2034%2031%2053.7z%22%20fill%3D%22%2340464D%22%2F%3E%3Cpath%20d%3D%22m157.8%20179.8h177.2v-49.8h-176.8c-25.3%200-46.3%208.7-62.3%2025.7-38.6%2041.1-39.6%20144.6-39.6%20144.6h277.4v-93.6h-49.8v43.8h-174.4c1.1-16.3%208.6-45.5%2022.8-60.6%206.4-6.9%2014.5-10.1%2025.5-10.1z%22%20fill%3D%22%23fff%22%2F%3E%3C%2Fsvg%3E';

	/**
	 * Does this get displayed on a single entry?
	 *
	 * @var boolean
	 */
	protected $show_on_single = true;

	/**
	 * Should the widget be initialized?
	 *
	 * Initialization is heavy (querying for all GF forms), so we only do it when necessary.
	 *
	 * @since TODO
	 *
	 * @return boolean
	 */
	private function should_initialize() {
		$doing_ajax = wp_doing_ajax();

		// Saving a View.
		if ( $doing_ajax && 'gv_field_options' === \GV\Utils::_POST( 'action' ) ) {
			return true;
		}

		// Editing a View.
		if ( 'edit' === \GV\Utils::_GET( 'action' ) && 'gravityview' === get_post_type( \GV\Utils::_GET( 'post' ) ) ) {
			return true;
		}

		// Frontend.
		if ( gravityview()->request->is_frontend() ) {
			return true;
		}

		// Elementor AJAX request.
		if ( $doing_ajax && 'elementor_ajax' === \GV\Utils::_POST( 'action' ) ) {
			return true;
		}

		return false;
	}

	public function __construct() {

		if ( ! $this->should_initialize() ) {
			return;
		}

		$this->widget_description = __( 'Display a Gravity Forms form.', 'gk-gravityview' );

		$default_values = array(
			'header' => 1,
			'footer' => 1,
		);

		$settings = array(
			'widget_form_id' => array(
				'type'    => 'select',
				'label'   => __( 'Form to display', 'gk-gravityview' ),
				'value'   => '',
				'options' => GVCommon::get_forms_as_options(),
			),
			'title'          => array(
				'type'  => 'checkbox',
				'label' => __( 'Show form title?', 'gk-gravityview' ),
				'value' => 1,
			),
			'description'    => array(
				'type'  => 'checkbox',
				'label' => __( 'Show form description?', 'gk-gravityview' ),
				'value' => 1,
			),
			'ajax'           => array(
				'type'  => 'checkbox',
				'label' => __( 'Enable AJAX', 'gk-gravityview' ),
				'desc'  => '',
				'value' => 1,
			),
			'field_values'   => array(
				'type'  => 'text',
				'class' => 'code widefat',
				'label' => __( 'Field value parameters', 'gk-gravityview' ),
				'desc'  => '<a href="https://docs.gravityforms.com/using-dynamic-population/" rel="external">' . esc_html__( 'Learn how to dynamically populate a field.', 'gk-gravityview' ) . '</a>',
				'value' => '',
			),
		);

		add_filter( 'gravityview/widget/hide_until_searched/allowlist', array( $this, 'add_to_allowlist' ) );

		parent::__construct( __( 'Gravity Forms', 'gk-gravityview' ), 'gravityforms', $default_values, $settings );
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
	 * @param array                       $widget_args
	 * @param string|\GV\Template_Context $content
	 * @param string                      $context
	 */
	public function render_frontend( $widget_args, $content = '', $context = '' ) {

		if ( ! $this->pre_render_frontend( $context ) ) {
			return;
		}

		$form_id = \GV\Utils::get( $widget_args, 'widget_form_id' );

		if ( empty( $form_id ) ) {
			return;
		}

		$title        = \GV\Utils::get( $widget_args, 'title' );
		$description  = \GV\Utils::get( $widget_args, 'description' );
		$field_values = \GV\Utils::get( $widget_args, 'field_values' );
		$ajax         = \GV\Utils::get( $widget_args, 'ajax' );

		gravity_form( $form_id, ! empty( $title ), ! empty( $description ), false, $field_values, $ajax );

		// If the form has been submitted, show the confirmation above the form, then show the form again below.
		if ( isset( GFFormDisplay::$submission[ $form_id ] ) ) {

			unset( GFFormDisplay::$submission[ $form_id ] );

			gravity_form( $form_id, ! empty( $title ), ! empty( $description ), false, $field_values, $ajax );
		}
	}
}

new GravityView_Widget_Gravity_Forms();
