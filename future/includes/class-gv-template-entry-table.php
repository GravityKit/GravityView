<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The Entry Table Template class .
 *
 * Renders a \GV\Entry using a \GV\Entry_Renderer.
 */
class Entry_Table_Template extends Entry_Template {
	/**
	 * @var string The template slug to be loaded (like "table", "list")
	 */
	public static $slug = 'table';

	/**
	 * Output a field cell.
	 *
	 * @param \GV\Field $field The field to be ouput.
	 * @param \GV\Field $entry The entry this field is for.
	 *
	 * @return void
	 */
	public function the_field( \GV\Field $field ) {
		/**
		 * @filter `gravityview/entry/cell/attributes` Filter the row attributes for the row in table view.
		 *
		 * @param array $attributes The HTML attributes.
		 * @param \GV\Field $field The field these attributes are for.
		 * @param \GV\Entry $entry The entry this is being called for.
		 * @param \GV\Entry_Template This template.
		 *
		 * @since future
		 */
		$attributes = apply_filters( 'gravityview/entry/cell/attributes', array(), $field, $this->entry, $this );

		/** Glue the attributes together. */
		foreach ( $attributes as $attribute => $value ) {
			$attributes[$attribute] = sprintf( "$attribute=\"%s\"", esc_attr( $value) );
		}
		$attributes = implode( ' ', $attributes );
		if ( $attributes ) {
			$attributes = " $attributes";
		}

		$renderer = new Field_Renderer();
		$source = is_numeric( $field->ID ) ? $this->view->form : new Internal_Source();

		/** Output. */
		printf( '<td%s>%s</td>', $attributes, $renderer->render( $field, $this->view, $source, $this->entry, $this->request ) );
	}

	/**
	 * Out the single entry table body.
	 *
	 * @return void
	 */
	public function the_entry() {

		$fields = $this->view->fields->by_position( 'single_table-columns' )->by_visible();
		$form = $this->view->form;

		/** @todo add filters from old code */
		foreach ( $fields->all() as $field ) {
			$column_label = apply_filters( 'gravityview/template/field_label', $field->get_label( $this->view, $form ), $field->as_configuration(), $form->form ? $form->form : null, null );
			printf( '<tr id="gv-field-%d-%s" class="gv-field-%d-%s">', $form->ID, $field->ID, $form->ID, $field->ID );
				printf( '<th scope="row"><span class="gv-field-label">%s</span></th>', $column_label );
				$this->the_field( $field );
			printf( '</tr>' );
		}
	}

	public function render() {
		/**
		 * Hide rows the fields of which are "gvempty".
		 */
		add_filter( 'gravityview/field/is_visible', array( $this, 'hide_empty_rows' ), 10, 2 );

		parent::render();

		/**
		 * Do only in this context.
		 */
		remove_filter( 'gravityview/field/is_visible', array( $this, 'hide_empty_rows' ), 10, 2 );
	}

	/**
	 * Hide rows the fields of which are "gvempty".
	 *
	 * Called on the `gravityview/field/is_visible` filter.
	 *
	 * @param boolean $visible This field is visible... or not.
	 * @param \GV\Field $field The field.
	 *
	 * @return boolean Visible or not.
	 */
	public function hide_empty_rows( $visible, $field ) {
		if ( $visible ) {
			$value = $field->get_value( $this->view, is_numeric( $field->ID ) ? $this->view->form : new Internal_Source(), $this->entry, $this->request );
			return ! gv_empty( $value, false, false );
		}
		return $visible;
	}
}
