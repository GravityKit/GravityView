<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The Entry List Template class .
 *
 * Renders a \GV\Entry using a \GV\Entry_Renderer.
 */
class Entry_List_Template extends Entry_Template {
	/**
	 * @var string The template slug to be loaded (like "table", "list")
	 */
	public static $slug = 'list';

	/**
	 * Output the field in the list view.
	 *
	 * @param \GV\Field $field The field to output.
	 * @param array     $extras Extra stuff, like wpautop, etc.
	 *
	 * @return string
	 */
	public function the_field( \GV\Field $field, $extras = null ) {
		$form  = \GV\GF_Form::by_id( $field->form_id ) ? : $this->view->form;
		$entry = $this->entry->from_field( $field );

		if ( ! $entry ) {
			return '';
		}

		$renderer = new Field_Renderer();
		$source   = is_numeric( $field->ID ) ? ( GF_Form::by_id( $field->form_id ) ? : $this->view->form ) : new Internal_Source();

		$value = $renderer->render( $field, $this->view, $source, $entry, $this->request );

		$context = Template_Context::from_template( $this, compact( 'field', 'entry' ) );

		/**
		 * @deprecated Here for back-compatibility.
		 */
		$label = apply_filters( 'gravityview_render_after_label', $field->get_label( $this->view, $form, $entry ), $field->as_configuration() );
		$label = apply_filters( 'gravityview/template/field_label', $label, $field->as_configuration(), is_numeric( $field->ID ) ? ( $source->form ? $source->form : null ) : null, $entry->as_entry() );

		/**
		 * Override the field label.
		 *
		 * @since 2.0
		 * @param string $label The label to override.
		 * @param \GV\Template_Context $context The context.
		 */
		$label = apply_filters( 'gravityview/template/field/label', $label, $context );

		/**
		 * @filter `gravityview/template/table/entry/hide_empty`
		 * @param boolean $hide_empty Should the row be hidden if the value is empty? Default: don't hide.
		 * @param \GV\Template_Context $context The context ;) Love it, cherish it. And don't you dare modify it!
		 */
		$hide_empty = apply_filters( 'gravityview/render/hide-empty-zone', Utils::get( $extras, 'hide_empty', $this->view->settings->get( 'hide_empty', false ) ), $context );

		if ( is_numeric( $field->ID ) ) {
			$extras['field'] = $field->as_configuration();
		}

		$extras['entry']      = $this->entry->as_entry();
		$extras['hide_empty'] = $hide_empty;
		$extras['label']      = $label;
		$extras['value']      = $value;

		return \gravityview_field_output( $extras, $context );
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
			$zone_var                = str_replace( '-', '_', $zone );
			$vars[ $zone_var ]       = $this->view->fields->by_position( 'single_list-' . $zone )->by_visible( $this->view );
			$vars[ "has_$zone_var" ] = $vars[ $zone_var ]->count();
		}

		return $vars;
	}
}
