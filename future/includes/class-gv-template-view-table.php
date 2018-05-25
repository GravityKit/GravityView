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
     * Constructor. Add filters to modify output.
     *
	 * @since 2.0.4
	 *
	 * @param View $view
	 * @param Entry_Collection $entries
	 * @param Request $request
	 */
	public function __construct( View $view, Entry_Collection $entries, Request $request ) {

	    add_filter( 'gravityview/template/field/label', array( __CLASS__, 'add_columns_sort_links' ), 100, 2 );

		parent::__construct( $view, $entries, $request );
	}

	/**
     * Add sorting links to HTML columns that support sorting
     *
     * @since 2.0.4
     * @since 2.0.5 Made static
     *
     * @static
     *
	 * @param string $column_label Label for the table column
	 * @param \GV\Template_Context $context
	 *
	 * @return string
	 */
	static public function add_columns_sort_links( $column_label, $context = null ) {

		$sort_columns = $context->view->settings->get( 'sort_columns' );

		if ( empty( $sort_columns ) ) {
            return $column_label;
		}

		if ( ! \GravityView_frontend::getInstance()->is_field_sortable( $context->field->ID, $context->view->form->form ) ) {
			return $column_label;
		}

		$sorting = \GravityView_View::getInstance()->getSorting();

		$class = 'gv-sort';

		$sort_field_id = \GravityView_frontend::_override_sorting_id_by_field_type( $context->field->ID, $context->view->form->ID );

		$sort_args = array(
			'sort' => $context->field->ID,
			'dir' => 'asc',
		);

		if ( ! empty( $sorting['key'] ) && (string) $sort_field_id === (string) $sorting['key'] ) {
			//toggle sorting direction.
			if ( 'asc' === $sorting['direction'] ) {
				$sort_args['dir'] = 'desc';
				$class .= ' gv-icon-sort-desc';
			} else {
				$sort_args['dir'] = 'asc';
				$class .= ' gv-icon-sort-asc';
			}
		} else {
			$class .= ' gv-icon-caret-up-down';
		}

		$url = add_query_arg( $sort_args, remove_query_arg( array('pagenum') ) );

		return '<a href="'. esc_url_raw( $url ) .'" class="'. $class .'" ></a>&nbsp;'. $column_label;
	}

	/**
	 * Output the table column names.
	 *
	 * @return void
	 */
	public function the_columns() {
		$fields = $this->view->fields->by_position( 'directory_table-columns' );

		foreach ( $fields->by_visible()->all() as $field ) {
			$context = Template_Context::from_template( $this, compact( 'field' ) );
			$form = $field->form_id ? GF_Form::by_id( $field->form_id ) : $this->view->form;

			/**
			 * @deprecated Here for back-compatibility.
			 */
			$column_label = apply_filters( 'gravityview_render_after_label', $field->get_label( $this->view, $form ), $field->as_configuration() );
			$column_label = apply_filters( 'gravityview/template/field_label', $column_label, $field->as_configuration(), $form->form ? $form->form : null, null );

			/**
			 * @filter `gravityview/template/field/label` Override the field label.
			 * @since 2.0
			 * @param[in,out] string $column_label The label to override.
			 * @param \GV\Template_Context $context The context. Does not have entry set here.
			 */
			$column_label = apply_filters( 'gravityview/template/field/label', $column_label, $context );

			$args = array(
				'hide_empty' => false,
				'zone_id' => 'directory_table-columns',
				'markup' => '<th id="{{ field_id }}" class="{{ class }}" style="{{width:style}}">{{label}}</th>',
				'label_markup' => '<span class="gv-field-label">{{ label }}</span>',
				'label' => $column_label,
			);

			echo \gravityview_field_output( $args, $context );
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

		$fields = $this->view->fields->by_position( 'directory_table-columns' )->by_visible();

		$context = Template_Context::from_template( $this, compact( 'entry', 'fields' ) );

		/**
		 * Push legacy entry context.
		 */
		\GV\Mocks\Legacy_Context::load( array(
			'entry' => $entry,
		) );

		/**
		 * @filter `gravityview_table_cells` Modify the fields displayed in a table
		 * @param array $fields
		 * @param \GravityView_View $this
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

		$context = Template_Context::from_template( $this, compact( 'entry', 'fields' ) );

		/**
		 * @filter `gravityview/template/table/entry/row/attributes` Filter the row attributes for the row in table view.
		 *
		 * @param array $attributes The HTML attributes.
		 * @param \GV\Template_Context The context.
		 *
		 * @since 2.0
		 */
		$attributes = apply_filters( 'gravityview/template/table/entry/row/attributes', $attributes, $context );

		/** Glue the attributes together. */
		foreach ( $attributes as $attribute => $value ) {
			$attributes[ $attribute ] = sprintf( "$attribute=\"%s\"", esc_attr( $value ) );
		}
		$attributes = implode( ' ', $attributes );

		?>
			<tr<?php echo $attributes ? " $attributes" : ''; ?>>
                <?php

				/**
				 * @action `gravityview/template/table/cells/before` Inside the `tr` while rendering each entry in the loop. Can be used to insert additional table cells.
				 * @since 2.0
				 * @param \GV\Template_Context The context.
				 */
				do_action( 'gravityview/template/table/cells/before', $context );

                /**
                 * @action `gravityview_table_cells_before` Inside the `tr` while rendering each entry in the loop. Can be used to insert additional table cells.
                 * @since 1.0.7
				 * @param \GravityView_View $this Current GravityView_View object
				 * @deprecated Use `gravityview/template/table/cells/before`
                 */
                do_action( 'gravityview_table_cells_before', \GravityView_View::getInstance() );

                foreach ( $fields->all() as $field ) {
					$this->the_field( $field, $entry );
				}

				/**
				 * @action `gravityview/template/table/cells/after` Inside the `tr` while rendering each entry in the loop. Can be used to insert additional table cells.
				 * @since 2.0
				 * @param \GV\Template_Context The context.
				 */
				do_action( 'gravityview/template/table/cells/after', $context );

                /**
                 * @action `gravityview_table_cells_after` Inside the `tr` while rendering each entry in the loop. Can be used to insert additional table cells.
                 * @since 1.0.7
				 * @param \GravityView_View $this Current GravityView_View object
				 * @deprecated Use `gravityview/template/table/cells/after`
                 */
                do_action( 'gravityview_table_cells_after', \GravityView_View::getInstance() );

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
		$form = $this->view->form;

		if ( $entry instanceof Multi_Entry ) {
			if ( ! $entry = Utils::get( $entry, $field->form_id ) ) {
				return;
			}
			$form = GF_Form::by_id( $field->form_id );
		}

		$context = Template_Context::from_template( $this, compact( 'field', 'entry' ) );

		$renderer = new Field_Renderer();
		$source = is_numeric( $field->ID ) ? $this->view->form : new Internal_Source();

		$value = $renderer->render( $field, $this->view, $source, $entry, $this->request );

		$args = array(
			'value' => $value,
			'hide_empty' => false,
			'zone_id' => 'directory_table-columns',
			'markup' => '<td id="{{ field_id }}" class="{{ class }}">{{ value }}</td>',
            'form' => $form,
		);

		/** Output. */
		echo \gravityview_field_output( $args, $context );
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
		 * @since 2.0
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/body/before', $context );

		/**
		* @action `gravityview_table_body_before` Inside the `tbody`, before any rows are rendered. Can be used to insert additional rows.
		* @deprecated Use `gravityview/template/table/body/before`
		* @since 1.0.7
		* @param \GravityView_View $gravityview_view Current GravityView_View object.
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
		 * @since 2.0
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/body/after', $context );

		/**
		* @action `gravityview_table_body_after` Inside the `tbody`, after any rows are rendered. Can be used to insert additional rows.
		* @deprecated Use `gravityview/template/table/body/after`
		* @since 1.0.7
		* @param \GravityView_View $gravityview_view Current GravityView_View object.
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
		 * @since 2.0
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/tr/before', $context );

		/**
		 * @action `gravityview_table_tr_before` Before the `tr` while rendering each entry in the loop. Can be used to insert additional table rows.
		 * @since 1.0.7
		 * @deprecated USe `gravityview/template/table/tr/before`
		 * @param \GravityView_View $gravityview_view Current GraivtyView_View object.
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
		 * @since 2.0
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/tr/after', $context );

		/**
		 * @action `gravityview_table_tr_after` Inside the `tr` while rendering each entry in the loop. Can be used to insert additional table cells.
		 * @since 1.0.7
		 * @deprecated USe `gravityview/template/table/tr/after`
		 * @param \GravityView_View $gravityview_view Current GravityView_View object.
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
		 * @param \GravityView_View $this Current GravityView_View object
		 * @deprecated Use `gravityview/template/table/entry/class`
		 * @return string The modified class.
		 */
		$class = apply_filters( 'gravityview_entry_class', $class, $entry->as_entry(), \GravityView_View::getInstance() );

		/**
		 * @filter `gravityview/template/table/entry/class` Modify the class aplied to the entry row.
		 * @param string $class The existing class.
		 * @param \GV\Template_Context The context.
		 * @return string The modified class.
		 */
		return apply_filters( 'gravityview/template/table/entry/class', $class, Template_Context::from_template( $context->template, compact( 'entry' ) ) );
	}
}
