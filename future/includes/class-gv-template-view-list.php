<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The View List Template class .
 *
 * Renders a \GV\View and a \GV\Entry_Collection via a \GV\View_Renderer.
 */
class View_List_Template extends View_Template {
	/**
	 * @var string The template slug to be loaded (like "table", "list")
	 */
	public static $slug = 'list';

	/**
	 * Output the field in the list view.
	 *
	 * @param \GV\Field $field The field to output.
	 * @param \GV\Entry $entry The entry.
	 * @param array $wrap Wrap the value in some markup. array( $tag => array( $attribute => $value, ... ) );
	 *
	 * @return void
	 */
	public function the_field( \GV\Field $field, \GV\Entry $entry, $wrap = null ) {
		$renderer = new Field_Renderer();
		$source = is_numeric( $field->ID ) ? $this->view->form : new Internal_Source();
		
		$output = $renderer->render( $field, $this->view, $source, $entry, $this->request );
		
		if ( count( $wrap ) ) {
			$wraps = array_keys( $wrap );
			$tag = array_pop( $wraps );
			$attributes = $wrap[ $tag ];

			/** Glue the attributes together. */
			foreach ( (array)$attributes as $attribute => $value ) {
				$attributes[$attribute] = sprintf( "$attribute=\"%s\"", esc_attr( $value) );
			}
			$attributes = implode( ' ', $attributes );

			printf( '<%s %s>%s</%s>', $tag, $attributes, $output, $tag );
			return;
		}
		
		echo $output;
	}

	/**
	 * Return an array of variables ready to be extracted.
	 *
	 * @param string|array $zones The field zones to grab.
	 *
	 * @return array An array ready to be extract()ed in the form of
	 *  $zone => \GV\Field_Collection
	 *  has_$zone => int
	 */
	public function extract_zone_vars( $zones ) {
		if ( ! is_array( $zones ) ) {
			$zones = array( $zones );
		}

		$vars = array();
		foreach ( $zones as $zone ) {
			$zone_var = str_replace( '-', '_', $zone );
			$vars[ $zone_var ] = $this->view->fields->by_position( 'directory_list-' . $zone )->by_visible();
			$vars[ "has_$zone_var" ] = $vars[ $zone_var ]->count();
		}

		return $vars;
	}
}
