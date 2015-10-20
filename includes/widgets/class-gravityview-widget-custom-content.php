<?php

/**
 * Widget to add custom content
 *
 * @since 1.5.4
 *
 * @extends GravityView_Widget
 */
class GravityView_Widget_Custom_Content extends GravityView_Widget {

	/**
	 * Does this get displayed on a single entry?
	 * @var boolean
	 */
	protected $show_on_single = false;

	function __construct() {

		$this->widget_description = __('Insert custom text or HTML as a widget', 'gravityview' );

		$default_values = array(
			'header' => 1,
			'footer' => 1,
		);

		$settings = array(
			'content' => array(
				'type' => 'textarea',
				'label' => __( 'Custom Content', 'gravityview' ),
				'desc' => __( 'Enter text or HTML. Also supports shortcodes.', 'gravityview' ),
				'value' => '',
				'class'	=> 'code',
				'merge_tags' => false,
				'show_all_fields' => true, // Show the `{all_fields}` and `{pricing_fields}` merge tags
			),
			'wpautop' => array(
				'type' => 'checkbox',
				'label' => __( 'Automatically add paragraphs to content', 'gravityview' ),
				'tooltip' => __( 'Wrap each block of text in an HTML paragraph tag (recommended for text).', 'gravityview' ),
				'value' => '',
			),
		);

		parent::__construct( __( 'Custom Content', 'gravityview' ) , 'custom_content', $default_values, $settings );
	}

	public function render_frontend( $widget_args, $content = '', $context = '') {

		if( !$this->pre_render_frontend() ) {
			return;
		}

		if( !empty( $widget_args['title'] ) ) {
			echo $widget_args['title'];
		}


		// Make sure the class is loaded in DataTables
		if( !class_exists( 'GFFormDisplay' ) ) {
			include_once( GFCommon::get_base_path() . '/form_display.php' );
		}

		$widget_args['content'] = trim( rtrim( $widget_args['content'] ) );

		// No custom content
		if( empty( $widget_args['content'] ) ) {
			do_action('gravityview_log_debug', sprintf( '%s[render_frontend]: No content.', get_class($this)) );
			return;
		}

		// Add paragraphs?
		if( !empty( $widget_args['wpautop'] ) ) {
			$widget_args['content'] = wpautop( $widget_args['content'] );
		}

		$content = $widget_args['content'];

		$content = GravityView_Merge_Tags::replace_variables( $content );

		// Enqueue scripts needed for Gravity Form display, if form shortcode exists.
		// Also runs `do_shortcode()`
		$content = GFCommon::gform_do_shortcode( $content );


		// Add custom class
		$class = !empty( $widget_args['custom_class'] ) ? $widget_args['custom_class'] : '';
		$class = gravityview_sanitize_html_class( $class );

		echo '<div class="gv-widget-custom-content '.$class.'">'. $content .'</div>';

	}

}

new GravityView_Widget_Custom_Content;