<?php
namespace GV\Shortcodes;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The [gvfield] shortcode.
 */
class gvfield extends \GV\Shortcode {
	/**
	 * {@inheritDoc}
	 */
	public $name = 'gvfield';

	/**
	 * Process and output the [gvfield] shortcode.
	 *
	 * @param array $atts The attributes passed.
	 * @param string $content The content inside the shortcode.
	 *
	 * @return string The output.
	 */
	public function callback( $atts, $content = null ) {
		$request = gravityview()->request;

		if ( $request->is_admin() ) {
			return apply_filters( 'gravityview/shortcodes/gvfield/output', '', null, null, $atts );
		}

		$atts = wp_parse_args( $atts, array(
			'view_id' => null,
			'entry_id' => null,
			'field_id' => null,
		) );

		/**
		 * @filter `gravityview/shortcodes/gvfield/atts` Filter the [gvfield] shortcode attributes.
		 * @param array $atts The initial attributes.
		 * @since 2.0
		 */
		$atts = apply_filters( 'gravityview/shortcodes/gvfield/atts', $atts );

		$view = \GV\View::by_id( $atts['view_id'] );

		if ( ! $view ) {
			gravityview()->log->error( 'View does not exist #{view_id}', array( 'view_id' => $atts['view_id'] ) );
			return apply_filters( 'gravityview/shortcodes/gventry/output', '', null, null, $atts );
		}

		$entry = $this->get_entry_or_message( $atts['entry_id'], $view, $atts );

		// Entry not found; return string message
		if ( ! $entry instanceof \GV\Entry ) {
			return $entry;
		}

		$restrict_access = $this->restrict_access( $view, $entry, $atts );

		// Something's amiss; you shall not pass
		if( null !== $restrict_access ) {
			return $restrict_access;
		}

		$field = is_numeric( $atts['field_id'] ) ? \GV\GF_Field::by_id( $view->form, $atts['field_id'] ) : \GV\Internal_Field::by_id( $atts['field_id'] );

		if ( ! $field ) {
			gravityview()->log->error( 'Field #{field_id} not found', array( 'view_id' => $atts['field_id'] ) );
			return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, $field, $atts );
		}

		$field->update_configuration( $atts );

		$renderer = new \GV\Field_Renderer();
		$output = $renderer->render( $field, $view, is_numeric( $field->ID ) ? $view->form : new \GV\Internal_Source(), $entry, gravityview()->request );

		/**
		 * @filter `gravityview/shortcodes/gvfield/output` Filter the [gvfield] output.
		 * @param string $output The output.
		 * @param \GV\View|null $view The View detected or null.
		 * @param \GV\Entry|null $entry The Entry or null.
		 * @param \GV\Field|null $field The Field or null.
		 *
		 * @since 2.0
		 */
		return apply_filters( 'gravityview/shortcodes/gvfield/output', $output, $view, $entry, $field, $atts );
	}
}
