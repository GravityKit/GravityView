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

		$atts = wp_parse_args( $atts, array(
			'view_id' => null,
			'entry_id' => null,
			'field_id' => null,
		) );

		/**
		 * @filter `gravityview/shortcodes/gvfield/atts` Filter the [gvfield] shortcode attributes.
		 * @param array $atts The initial attributes.
		 * @since future-render
		 */
		$atts = apply_filters( 'gravityview/shortcodes/gvfield/atts', $atts );

		if ( ! $view = \GV\View::by_id( $atts['view_id'] ) ) {
			gravityview()->log->error( 'View #{view_id} not found', array( 'view_id' => $atts['view_id'] ) );
			return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, null, null, $atts );
		}

		switch( $atts['entry_id'] ):
			case 'last':
				if ( class_exists( '\GF_Query' ) ) {
					/**
					 * @todo Remove once we refactor the use of get_view_entries_parameters.
					 *
					 * Since we're using \GF_Query shorthand initialization we have to reverse the order parameters here.
					 */
					add_filter( 'gravityview_get_entries', $filter = function( $parameters, $args, $form_id ) {
						if ( ! empty( $parameters['sorting'] ) ) {
							/**
							 * Reverse existing sorts.
							 */
							$sort = &$parameters['sorting'];
							$sort['direction'] = $sort['direction'] == 'RAND' ? : ( $sort['direction'] == 'ASC' ? 'DESC' : 'ASC' );
						} else {
							/**
							 * Otherwise, sort by date_created.
							 */
							$parameters['sorting'] = array(
								'key' => 'id',
								'direction' => 'ASC',
								'is_numeric' => true
							);
						}
						return $parameters;
					}, 10, 3 );
					$entries = $view->get_entries( null );
					remove_filter( 'gravityview_get_entries', $filter );
				} else {
					$entries = $view->get_entries( null );

					/** If a sort already exists, reverse it. */
					if ( $sort = end( $entries->sorts ) ) {
						$entries = $entries->sort( new \GV\Entry_Sort( $sort->field, $sort->direction == \GV\Entry_Sort::RAND ? : ( $sort->direction == \GV\Entry_Sort::ASC ? \GV\Entry_Sort::DESC : \GV\Entry_Sort::ASC ) ), $sort->mode );
					} else {
						/** Otherwise, sort by date_created */
						$entries = $entries->sort( new \GV\Entry_Sort( \GV\Internal_Field::by_id( 'id' ), \GV\Entry_Sort::ASC ), \GV\Entry_Sort::NUMERIC );
					}
				}

				if ( ! $entry = $entries->first() ) {
					return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, null, $atts );
				}
				break;
			case 'first':
				if ( ! $entry = $view->get_entries( null )->first() ) {
					return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, null, $atts );
				}
				break;
			default:
				if ( ! $entry = \GV\GF_Entry::by_id( $atts['entry_id'] ) ) {
					gravityview()->log->error( 'Entry #{entry_id} not found', array( 'view_id' => $atts['view_id'] ) );
					return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, null, $atts );
				}
		endswitch;

		$field = is_numeric( $atts['field_id'] ) ? \GV\GF_Field::by_id( $view->form, $atts['field_id'] ) : \GV\Internal_Field::by_id( $atts['field_id'] );

		if ( ! $field ) {
			gravityview()->log->error( 'Field #{field_id} not found', array( 'view_id' => $atts['field_id'] ) );
			return apply_filters( 'gravityview/shortcodes/gvfield/output', '', $view, $entry, $field, $atts );
		}

		/** @todo Protection! */

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
		 * @since future-render
		 */
		return apply_filters( 'gravityview/shortcodes/gvfield/output', $output, $view, $entry, $field, $atts );
	}
}
