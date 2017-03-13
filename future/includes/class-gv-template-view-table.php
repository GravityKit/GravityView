<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The View Table Template class .
 *
 * Attached to a \GV\View and used by a \GV\View_Renderer.
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

		foreach ( $fields->by_visible()->all() as $field ) {
			printf( '<th id="gv-field-%d-%s" class="gv-field-%d-%s"><span class="gv-field-label">%s</span></th>',
				esc_attr( $form->ID ), esc_attr( $field->ID ), esc_attr( $form->ID ), esc_attr( $field->ID ), esc_html( $field->label )
			);
		}
	}
}
