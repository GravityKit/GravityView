<?php
/**
 * Add Gravity Forms scripts and styles to GravityView no-conflict list
 *
 * @file      class-gravityview-plugin-hooks-gravity-forms.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since 1.15.2
 */

/**
 * @inheritDoc
 * @since 1.15.2
 */
class GravityView_Plugin_Hooks_Gravity_Forms extends GravityView_Plugin_and_Theme_Hooks {

	public $class_name = 'GFForms';

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $style_handles = array(
		'gform_tooltip',
		'gform_font_awesome',
	);

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $script_handles = array(
		'gform_tooltip_init',
		'gform_field_filter',
		'gform_forms',
	);

	function add_hooks() {
		parent::add_hooks();

		// Needs to be early to be triggered before DataTables
		add_filter( 'gravityview_view_ids', array( $this, 'parse_form_html_fields' ), 20 );
	}

	/**
	 * Parse HTML fields for [gravityview] shortcode content
	 *
	 * @since 1.20.1
	 *
	 * @see GFFormDisplay::enqueue_scripts for the code structure
	 *
	 * @param array $form GF Form array
	 *
	 * @return array
	 */
	function parse_form_html_fields( $original_view_ids = array() ) {
		global $wp_query;

		if ( isset( $wp_query->posts ) && is_array( $wp_query->posts ) ) {

			require_once( GFCommon::get_base_path() . '/form_display.php' );

			$view_ids = array();

			foreach ( $wp_query->posts as $post ) {

				$ajax = false;

				$forms = GFFormDisplay::get_embedded_forms( $post->post_content, $ajax );

				foreach ( $forms as $form ) {
					$html_fields = GFCommon::get_fields_by_type( $form, array( 'html' ) );
					foreach ( $html_fields as $html_field ) {
						$field_view_ids = GravityView_View_Data::getInstance()->parse_post_content( $html_field->content );
						$view_ids = array_merge( $view_ids, (array) $field_view_ids );
					}
				}
			}
		}

		return array_merge( $original_view_ids, $view_ids );
	}
}

new GravityView_Plugin_Hooks_Gravity_Forms;