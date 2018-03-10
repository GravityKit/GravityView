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
	 *
	 * @return string|false The field output or false if "hide_empty" is set.
	 */
	public function the_field( \GV\Field $field ) {
		$context = Template_Context::from_template( $this, compact( 'field' ) );

		/**
		 * @filter `gravityview/entry/cell/attributes` Filter the row attributes for the row in table view.
		 *
		 * @param array $attributes The HTML attributes.
		 * @param \GV\Template_Context This template.
		 *
		 * @since future
		 */
		$attributes = apply_filters( 'gravityview/entry/cell/attributes', array(), $context );

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

		$output = $renderer->render( $field, $this->view, $source, $this->entry, $this->request );

		/**
		 * @filter `gravityview/template/table/entry/hide_empty`
		 * @param boolean Should the row be hidden if the value is empty? Default: don't hide.
		 * @param \GV\Template_Context $context The context ;) Love it, cherish it. And don't you dare modify it!
		 */
		$hide_empty = apply_filters( 'gravityview/render/hide-empty-zone', $this->view->settings->get( 'hide_empty', false ), Template_Context::from_template( $this, compact( $field ) ) );

		/**
		 * Hide empty if nothing to show.
		 */
		if ( $hide_empty && gv_empty( $output, false, false ) ) {
			return false;
		}

		/** Output. */
		return sprintf( '<td%s>%s</td>', $attributes, $output );
	}

	/**
	 * Out the single entry table body.
	 *
	 * @return void
	 */
	public function the_entry() {

		$fields = $this->view->fields->by_position( 'single_table-columns' )->by_visible();
		$form = $this->view->form;

		$context = Template_Context::from_template( $this, compact( 'fields' ) );

		/**
		 * @filter `gravityview_table_cells` Modify the fields displayed in a table
		 * @param array $fields
		 * @param GravityView_View $this
		 * @deprecated Use `gravityview/template/table/fields`
		 */
		$fields = apply_filters( 'gravityview_table_cells', $fields->as_configuration(), \GravityView_View::getInstance() );
		$fields = Field_Collection::from_configuration( $fields );

		/**
		 * @filter `gravityview/template/table/fields` Modify the fields displayed in this tables.
		 * @param \GV\Field_Collection $fields The fields.
		 * @param \GV\Template_Context $context The context.
		 * @since 2.0
		 */
		$fields = apply_filters( 'gravityview/template/table/fields', $fields, $context );

		foreach ( $fields->all() as $field ) {
			/**
			 * @deprecated Here for back-compatibility.
			 */
			$column_label = apply_filters( 'gravityview_render_after_label', $field->get_label( $this->view, $form ), $field->as_configuration() );
			$column_label = apply_filters( 'gravityview/template/field_label', $column_label, $field->as_configuration(), $form->form ? $form->form : null, $this->entry->as_entry() );

			/**
			 * @filter `gravityview/template/field/label` Override the field label.
			 * @since 2.0
			 * @param[in,out] string $column_label The label to override.
			 * @param \GV\Template_Context $context The context.
			 */
			$column_label = apply_filters( 'gravityview/template/field/label', $column_label, Template_Context::from_template( $this, compact( $field ) ) );

			/**
			 * @filter `gravityview/template/table/entry/hide_empty`
			 * @param boolean Should the row be hidden if the value is empty? Default: don't hide.
			 * @param \GV\Template_Context $context The context ;) Love it, cherish it. And don't you dare modify it!
			 */
			$hide_empty = apply_filters( 'gravityview/render/hide-empty-zone', $this->view->settings->get( 'hide_empty', false ), Template_Context::from_template( $this, compact( $field ) ) );

			if ( ( $field_output = $this->the_field( $field ) ) || ! $hide_empty ) {
				printf( '<tr id="gv-field-%d-%s" class="gv-field-%d-%s">', $form->ID, $field->ID, $form->ID, $field->ID );
					printf( '<th scope="row"><span class="gv-field-label">%s</span></th>', $column_label );
					echo $field_output ? : '<td></td>';
				printf( '</tr>' );
			}
		}
	}
}
