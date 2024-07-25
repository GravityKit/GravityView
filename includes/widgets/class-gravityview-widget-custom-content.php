<?php

/**
 * Widget to add custom content
 *
 * @since 1.5.4
 *
 * @extends GravityView_Widget
 */
class GravityView_Widget_Custom_Content extends \GV\Widget {

	public $icon = 'dashicons-editor-code';

	/**
	 * Does this get displayed on a single entry?
	 *
	 * @var boolean
	 */
	protected $show_on_single = false;

	function __construct() {

		$this->widget_description = __( 'Insert custom text or HTML as a widget', 'gk-gravityview' );

		$default_values = array(
			'header' => 1,
			'footer' => 1,
		);

		$settings = array(
			'content'     => array(
				'type'            => 'textarea',
				'label'           => __( 'Custom Content', 'gk-gravityview' ),
				'desc'            => __( 'Enter text or HTML. Also supports shortcodes.', 'gk-gravityview' ),
				'value'           => '',
				'class'           => 'code',
				'merge_tags'      => false,
				'show_all_fields' => true, // Show the `{all_fields}` and `{pricing_fields}` merge tags
			),
			'wpautop'     => array(
				'type'    => 'checkbox',
				'label'   => __( 'Automatically add paragraphs to content', 'gk-gravityview' ),
				'tooltip' => __( 'Wrap each block of text in an HTML paragraph tag (recommended for text).', 'gk-gravityview' ),
				'value'   => '',
			),
			'admin_label' => array(
				'type'  => 'text',
				'class' => 'widefat',
				'label' => __( 'Admin Label', 'gk-gravityview' ),
				'desc'  => __( 'A label that is only shown in the GravityView View configuration screen.', 'gk-gravityview' ),
				'value' => '',
			),
		);

		parent::__construct( __( 'Custom Content', 'gk-gravityview' ), 'custom_content', $default_values, $settings );
	}

	public function render_frontend( $widget_args, $content = '', $context = '' ) {

		if ( ! $this->pre_render_frontend( $context ) ) {
			return;
		}

		if ( ! empty( $widget_args['title'] ) ) {
			echo $widget_args['title'];
		}

		// Make sure the class is loaded in DataTables
		if ( ! class_exists( 'GFFormDisplay' ) ) {
			include_once GFCommon::get_base_path() . '/form_display.php';
		}

		$widget_args['content'] = trim( rtrim( \GV\Utils::get( $widget_args, 'content', '' ) ) );

		// No custom content
		if ( empty( $widget_args['content'] ) ) {
			gravityview()->log->debug( 'No content.' );
			return;
		}

		// Add paragraphs?
		if ( ! empty( $widget_args['wpautop'] ) ) {
			$widget_args['content'] = wpautop( $widget_args['content'] );
		}

		$content = $widget_args['content'];

		$content = GravityView_Merge_Tags::replace_variables( $content, array(), array(), false, true, false );

		// Enqueue scripts needed for Gravity Form display, if form shortcode exists.
		// Also runs `do_shortcode()`
		$content = GFCommon::gform_do_shortcode( $content );

		// Add custom class
		$class = ! empty( $widget_args['custom_class'] ) ? $widget_args['custom_class'] : '';
		$class = gravityview_sanitize_html_class( $class );

		echo '<div class="gv-widget-custom-content ' . $class . '">' . $content . '</div>';
	}
}

new GravityView_Widget_Custom_Content();
