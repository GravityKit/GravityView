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

	/**
	 * `gravityview_table_body_before` and `gravityview/template/table/body/before` actions.
	 *
	 * Output inside the `tbody` of the table.
	 *
	 * @param $context \GV\Template_Context The 2.0 context.
	 *
	 * @return void
	 */
	public static function body_before( $context ) {
		/**
		 * @action `gravityview/template/table/body/before` Output inside the `tbody` of the table.
		 * @since future
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/body/before', $context );

		/**
		* @action `gravityview_table_body_before` Inside the `tbody`, before any rows are rendered. Can be used to insert additional rows.
		* @deprecated Use `gravityview/template/table/body/before`
		* @since 1.0.7
		* @param GravityView_View $gravityview_view Current GravityView_View object.
		*/
		do_action( 'gravityview_table_body_before', \GravityView_View::getInstance() /** ugh! */ );
	}

	/**
	 * `gravityview_table_body_after` and `gravityview/template/table/body/after` actions.
	 *
	 * Output inside the `tbody` of the table.
	 *
	 * @param $context \GV\Template_Context The 2.0 context.
	 *
	 * @return void
	 */
	public static function body_after( $context ) {
		/**
		 * @action `gravityview/template/table/body/after` Output inside the `tbody` of the table at the end.
		 * @since future
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/body/after', $context );

		/**
		* @action `gravityview_table_body_after` Inside the `tbody`, after any rows are rendered. Can be used to insert additional rows.
		* @deprecated Use `gravityview/template/table/body/after`
		* @since 1.0.7
		* @param GravityView_View $gravityview_view Current GravityView_View object.
		*/
		do_action( 'gravityview_table_body_after', \GravityView_View::getInstance() /** ugh! */ );
	}

	/**
	 * `gravityview_table_tr_before` and `gravityview/template/table/tr/after` actions.
	 *
	 * Output inside the `tr` of the table.
	 *
	 * @param $context \GV\Template_Context The 2.0 context.
	 *
	 * @return void
	 */
	public static function tr_before( $context ) {
		/**
		 * @action `gravityview/template/table/tr/before` Output inside the `tr` of the table when there are no results.
		 * @since future
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/tr/before', $context );

		/**
		 * @action `gravityview_table_tr_before` Before the `tr` while rendering each entry in the loop. Can be used to insert additional table rows.
		 * @since 1.0.7
		 * @deprecated USe `gravityview/template/table/tr/before`
		 * @param GravityView_View $gravityview_view Current GraivtyView_View object.
		 */
		do_action( 'gravityview_table_tr_before', \GravityView_View::getInstance() /** ugh! */ );
	}

	/**
	 * `gravityview_table_tr_after` and `gravityview/template/table/tr/after` actions.
	 *
	 * Output inside the `tr` of the table.
	 *
	 * @param $context \GV\Template_Context The 2.0 context.
	 *
	 * @return void
	 */
	public static function tr_after( $context ) {
		/**
		 * @action `gravityview/template/table/tr/after` Output inside the `tr` of the table when there are no results.
		 * @since future
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/tr/after', $context );

		/**
		 * @action `gravityview_table_tr_after` Inside the `tr` while rendering each entry in the loop. Can be used to insert additional table cells.
		 * @since 1.0.7
		 * @deprecated USe `gravityview/template/table/tr/after`
		 * @param GravityView_View $gravityview_view Current GraivtyView_View object.
		 */
		do_action( 'gravityview_table_tr_after', \GravityView_View::getInstance() /** ugh! */ );
	}

	/**
	 * `gravityview_entry_class` and `gravityview/template/table/entry/class` filters.
	 *
	 * Modify of the class of a row.
	 *
	 * @param string $class The class.
	 * @param \GV\Entry $entry The entry.
	 * @param \GV\Template_Context The context.
	 *
	 * @return string The classes.
	 */
	public static function entry_class( $class, $entry, $context ) {
		/**
		 * @filter `gravityview_entry_class` Modify the class applied to the entry row.
		 * @param string $class Existing class.
		 * @param array $entry Current entry being displayed
		 * @param GravityView_View $this Current GravityView_View object
		 * @deprecated Use `gravityview/template/table/entry/class`
		 * @return string The modified class.
		 */
		$class = apply_filters( 'gravityview_entry_class', $class, $entry->as_entry(), \GravityView_View::getInstance() );

		/**
		 * @filter `gravityview/template/table/entry/class` Modify the class aplied to the entry row.
		 * @param string $class The existing class.
		 * @param \GV\Entry $entry The entry.
		 * @param \GV\Template_Context The context.
		 * @return string The modified class.
		 */
		return apply_filters( 'gravityview/template/table/entry/class', $class, $entry, $context );
	}
}



