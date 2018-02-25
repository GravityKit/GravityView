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
	 * @param array $extras Extra stuff, like wpautop, etc.
	 *
	 * @return string
	 */
	public function the_field( \GV\Field $field, \GV\Entry $entry, $extras = null ) {
		$form = $this->view->form;

		if ( $entry instanceof Multi_Entry ) {
			if ( ! $entry = Utils::get( $entry, $field->form_id ) ) {
				return;
			}
			$form = GF_Form::by_id( $field->form_id );
		}

		$renderer = new Field_Renderer();
		$source = is_numeric( $field->ID ) ? $form : new Internal_Source();
		
		$output = $renderer->render( $field, $this->view, $source, $entry, $this->request );

		/** No value? don't output anything. */
		if ( ! $output ) {
			return;
		}

		/** Auto paragraph the value. */
		if ( ! empty( $extras['wpautop'] ) ) {
			$output = wpautop( $output );
		}

		$label = apply_filters( 'gravityview/template/field_label', $field->get_label( $this->view, $form ), $field->as_configuration(), $form->form ? $form->form : null, null );

		/** Wrap the label as needed */
		$label = $this->wrap( $label, array( 'span' => array( 'class' => 'gv-field-label' ) ) );
		if ( !empty( $extras['label_tag'] ) ) {
			$label = $this->wrap( $label, array( $extras['label_tag'] => array() ) );
		}
		
		return $label . $output;
	}

	/**
	 * Generate the default field attributes.
	 *
	 * @param \GV\Field $field The field.
	 * @param array $attributes Optional overrides.
	 *
	 * @return array An array of attributes.
	 */
	public function the_field_attributes( $field, $attributes = array() ) {
		return wp_parse_args( $attributes, array(
			'id' => sprintf( 'gv-field-%d-%s', $this->view->form ? $this->view->form->ID : 0, $field->ID ),
			'class' => sprintf( 'gv-field-%d-%s', $this->view->form ? $this->view->form->ID : 0, $field->ID ),
		) );
	}

	/**
	 * Wrap content into some tags.
	 *
	 * @param string $content The content to wrap.
	 * @param array $wrap The wrapper in the form of array( $tag => array( $attribute => $value, .. ) )
	 *
	 * @todo reuse
	 *
	 * @return string The wrapped string
	 */
	public function wrap( $content, $wrap ) {
		if ( ! is_array( $wrap ) || ! count( $wrap ) ) {
			return $content;
		}

		$wraps = array_keys( $wrap );
		$tag = array_pop( $wraps );
		$attributes = $wrap[ $tag ];

		/** Glue the attributes together. */
		foreach ( (array)$attributes as $attribute => $value ) {
			if ( $value ) {
				$attributes[ $attribute ] = sprintf( "$attribute=\"%s\"", esc_attr( $value) );
			} else {
				unset( $attributes[ $attribute ] );
			}
		}
		$attributes = implode( ' ', $attributes );
		if ( $attributes ) {
			$attributes = " $attributes";
		}

		return sprintf( '<%s%s>%s</%s>', $tag, $attributes, $content, $tag );
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
