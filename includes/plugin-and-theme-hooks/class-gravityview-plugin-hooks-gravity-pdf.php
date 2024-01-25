<?php
/**
 * Add GravityView compatibility to Gravity PDF
 *
 * @file      class-gravityview-plugin-hooks-gravity-pdf.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @since develop
 */

/**
 * @inheritDoc
 * @since develop
 */
class GravityView_Plugin_Hooks_Gravity_PDF extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @inheritDoc
	 * @since 1.15.2
	 */
	protected $constant_name = 'PDF_EXTENDED_VERSION';

	public function add_hooks() {
		parent::add_hooks();

		add_filter( 'gravityview/fields/custom/content_before', array( $this, 'fix_entry_id_for_custom_content_shortcode' ), 11, 2 );
	}

	/**
	 * @see https://github.com/gravityview/Multiple-Forms/issues/41
	 */
	public function fix_entry_id_for_custom_content_shortcode( $content, $context ) {
		if ( ! $context->entry->is_multi() ) {
			return $content;
		}

		if ( ! class_exists( 'GPDFAPI' ) ) {
			return $content;
		}

		if ( ! $shortcodes = GPDFAPI::get_mvc_class( 'Model_Shortcodes' ) ) {
			return $content;
		}

		global $wpdb;
		$table = GFFormsModel::get_meta_table_name();

		foreach ( $shortcodes->get_shortcode_information( 'gravitypdf', $content ) as $shortcode ) {
			// Let's make sure this entry ID is correct for the supplied form
			$form_id = $wpdb->get_var( $wpdb->prepare( "SELECT form_id FROM $table WHERE display_meta LIKE %s", '%"' . $wpdb->esc_like( $shortcode['attr']['id'] ) . '"%' ) );

			// Inject the needed entry ID
			$replace = str_replace(
				sprintf( 'entry="%d"', $shortcode['attr']['entry'] ),
				sprintf( 'entry="%d"', $context->entry[ $form_id ]['id'] ),
				$shortcode['shortcode']
			);

			$content = str_replace( $shortcode['shortcode'], $replace, $content );
		}

		return $content;
	}
}

new GravityView_Plugin_Hooks_Gravity_PDF();
