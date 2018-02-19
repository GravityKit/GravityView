<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The View Table Template class .
 *
 * Renders a \GV\View and a \GV\Entry_Collection via a \GV\View_Renderer.
 */
class View_Table_Template extends View_Template {
	/**
	 * @var string The template slug to be loaded (like "table", "list")
	 */
	public static $slug = 'table';

	/**
	 * Output the table column names.
	 *
	 * @return void
	 */
	public function the_columns() {
		$fields = $this->view->fields->by_position( 'directory_table-columns' );
		$form = $this->view->form;

		/** @todo Add class filters from the old code. */
		foreach ( $fields->by_visible()->all() as $field ) {

			$column_label = apply_filters( 'gravityview/template/field_label', $field->get_label( $this->view, $form ), $field->as_configuration(), $form->form ? $form->form : null, null );

			printf( '<th id="gv-field-%d-%s" class="gv-field-%d-%s"%s><span class="gv-field-label">%s</span></th>',
				esc_attr( $form->ID ), esc_attr( $field->ID ), esc_attr( $form->ID ), esc_attr( $field->ID ),
				$field->width ? sprintf( ' style="width: %d%%"', $field->width ) : '', $column_label
			);
		}
	}

	/**
	 * Output the entry row.
	 *
	 * @param \GV\Entry $entry The entry to be rendered.
	 * @param array $attributes The attributes for the <tr> tag
	 *
	 * @return void
	 */
	public function the_entry( \GV\Entry $entry, $attributes ) {

		$context = Template_Context::from_template( $this );

		/**
		 * @filter `gravityview/entry/row/attributes` Filter the row attributes for the row in table view.
		 *
		 * @param array $attributes The HTML attributes.
		 * @param \GV\Entry $entry The entry this is being called for.
		 * @param \GV\View_Template This template.
		 *
		 * @since 2.0
		 */
		$attributes = apply_filters( 'gravityview/entry/row/attributes', $attributes, $entry, $this );

		/** Glue the attributes together. */
		foreach ( $attributes as $attribute => $value ) {
			$attributes[$attribute] = sprintf( "$attribute=\"%s\"", esc_attr( $value) );
		}
		$attributes = implode( ' ', $attributes );

		$fields = $this->view->fields->by_position( 'directory_table-columns' )->by_visible();

		?>
			<tr<?php echo $attributes ? " $attributes" : ''; ?>>
                <?php

                /**
                 * @action `gravityview_table_cells_before` Inside the `tr` while rendering each entry in the loop. Can be used to insert additional table cells.
                 * @since 1.0.7
                 * @since 2.0 Updated to pass \GV\Template_Context instead of \GravityView_View
                 * @param \GV\Template_Context $context Current $gravityview state
                 */
                do_action('gravityview_table_cells_before', $context );

                foreach ( $fields->all() as $field ) {
					$this->the_field( $field, $entry );
				}

                /**
                 * @action `gravityview_table_cells_after` Inside the `tr` while rendering each entry in the loop. Can be used to insert additional table cells.
                 * @since 1.0.7
                 * @since 2.0 Updated to pass \GV\Template_Context instead of \GravityView_View
                 * @param \GV\Template_Context $context Current $gravityview state
                 */
                do_action('gravityview_table_cells_after', $context );

				?>
			</tr>
		<?php
	}

	/**
	 * Output a field cell.
	 *
	 * @param \GV\Field $field The field to be ouput.
	 * @param \GV\Field $entry The entry this field is for.
	 *
	 * @return void
	 */
	public function the_field( \GV\Field $field, \GV\Entry $entry ) {

	    $attributes = array(
			'id' => \GravityView_API::field_html_attr_id( $field->as_configuration(), $this->view->form, $entry->as_entry() ),
			'class' => gv_class( $field->as_configuration(), $this->view->form, $entry->as_entry() ),
		);

		/**
		 * @filter `gravityview/entry/cell/attributes` Filter the row attributes for the row in table view.
		 *
		 * @param array $attributes The HTML attributes.
		 * @param \GV\Field $field The field these attributes are for.
		 * @param \GV\Entry $entry The entry this is being called for.
		 * @param \GV\View_Template This template.
		 *
		 * @since future
		 */
		$attributes = apply_filters( 'gravityview/entry/cell/attributes', $attributes, $field, $entry, $this );

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
		printf( '<td%s>%s</td>', $attributes, $renderer->render( $field, $this->view, $source, $entry, $this->request ) );
	}
}
