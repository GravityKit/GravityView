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
	public function the_field( \GV\Field $field, ?Entry $entry = null ) {
		$renderer = new Field_Renderer();
		$source   = is_numeric( $field->ID ) ? ( GF_Form::by_id( $field->form_id ) ?: $this->view->form ) : new Internal_Source();

		return $renderer->render(
			$field,
			$this->view,
			$source,
			$entry ?? $this->entry->from_field( $field ),
			$this->request
		);
	}

	/**
	 * Out the single entry table body.
	 *
	 * @return void
	 */
	public function the_entry() {

		/** @type \GV\Field_Collection $fields */
		$fields = $this->view->fields->by_position( 'single_table-columns' )->by_visible( $this->view );

		$context = Template_Context::from_template( $this, compact( 'fields' ) );

		/**
		 * Modify the fields displayed in a table.
		 *
		 * @param array $fields
		 * @param \GravityView_View $this
		 * @deprecated Use `gravityview/template/table/fields`
		 */
		$fields = apply_filters( 'gravityview_table_cells', $fields->as_configuration(), \GravityView_View::getInstance() );
		$fields = Field_Collection::from_configuration( $fields );

		/**
		 * Modify the fields displayed in this tables.
		 *
		 * @since 2.0
		 *
		 * @param \GV\Field_Collection $fields  The fields.
		 * @param \GV\Template_Context $context The context.
		 */
		$fields = apply_filters( 'gravityview/template/table/fields', $fields, $context );

		foreach ( $fields->all() as $field ) {
			$context = Template_Context::from_template( $this, compact( 'field' ) );

			$form  = \GV\GF_Form::by_id( $field->form_id ) ?: $this->view->form;
			$entry = $this->entry->from_field( $field );

			if ( ! $entry ) {
				continue;
			}

			/**
			 * @deprecated Here for back-compatibility.
			 */
			$column_label = apply_filters(
				'gravityview_render_after_label',
				$field->get_label( $this->view, $form, $entry ),
				$field->as_configuration()
			);

			$column_label = apply_filters(
				'gravityview/template/field_label',
				$column_label,
				$field->as_configuration(),
				$form->form ? $form->form : null,
				$entry->as_entry()
			);

			/** @var GF_Field $field */
			$results = $field->get_results( $this->view, null, $entry, $this->request );

			/**
			 * Override the field label.
			 *
			 * @since 2.0
			 *
			 * @param string               $column_label The label to override.
			 * @param \GV\Template_Context $context      The context.
			 */
			$column_label = apply_filters( 'gravityview/template/field/label', $column_label, $context );

			/**
			 * @filter `gravityview/template/table/entry/hide_empty`
			 *
			 * @param boolean              $hide_empty Should the row be hidden if the value is empty? Default: don't hide.
			 * @param \GV\Template_Context $context    The context ;) Love it, cherish it. And don't you dare modify it!
			 */
			$hide_empty = apply_filters(
				'gravityview/render/hide-empty-zone',
				$this->view->settings->get( 'hide_empty_single', false ),
				$context
			);

			$markup = '<tr id="{{ field_id }}" class="{{ class }}"><th scope="row">{{ label }}</th><td>{{ value }}</td></tr>';
			/**
			 * Modifies the table row markup for an entry.
			 *
			 * @filter `gravityview/template/table/entry/markup`
			 *
			 * @since  2.29.0
			 *
			 * @param string    $markup The markup.
			 * @param \GV\Field $field  The field.
			 */
			$markup = apply_filters( 'gravityview/template/table/entry/markup', $markup, $field );

			$output_atts = [
				'entry'      => $entry->as_entry(),
				'field'      => is_numeric( $field->ID ) ? $field->as_configuration() : null,
				'label'      => $column_label,
				'value'      => $this->the_field( $field, $entry ),
				'markup'     => $markup,
				'hide_empty' => $hide_empty,
				'zone_id'    => 'single_table-columns',
			];

			if ( ! $results ) {
				echo \gravityview_field_output( $output_atts, $context );

				continue;
			}

			$rowspan = count( $results );
			$markup  = '<tr id="{{ field_id }}" class="{{ class }}" data-row="{{ row }}"><th rowspan="{{ rowspan }}" scope="row">{{ label }}</th><td>{{ value }}</td></tr>';

			foreach ( $results as $i => $value ) {
				if ( $i > 0 ) {
					$markup = '<tr id="{{ field_id }}-{{ row }} " class="{{ class }}" data-row="{{ row }}"><td>{{ value }}</td></tr>';
				}
				$markup = apply_filters( 'gravityview/template/table/entry/markup', $markup, $field );

				$data     = $entry->as_entry();
				$field_id = $field->ID;
				if ( is_array( $value ) && isset( $value[ $field_id ] ) && false !== strpos( $field_id, '.' ) ) {
					// This field might be an input of a complex field.
					$value = $value[ $field_id ];
				}
				$data[ $field->ID ] = $value;

				// Temporarily overwrite entry for rendering.
				$tmp_entry = GF_Entry::from_entry( $data );
				if ( ! $tmp_entry ) {
					continue;
				}

				$output_atts_row = array_merge(
					$output_atts,
					[
						'markup'  => $markup,
						'entry'   => $tmp_entry->as_entry(),
						'row'     => $i,
						'rowspan' => $rowspan,
						'value'   => $this->the_field( $field, $tmp_entry ),
					]
				);

				echo \gravityview_field_output( $output_atts_row, $context );
			}
		}
	}
}
