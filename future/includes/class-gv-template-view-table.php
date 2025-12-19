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
	 * @param View             $view
	 * @param Entry_Collection $entries
	 * @param Request          $request
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
	 * @param string               $column_label Label for the table column
	 * @param \GV\Template_Context $context
	 *
	 * @return string
	 */
	public static function add_columns_sort_links( $column_label, $context = null ) {

		$sort_columns = $context->view->settings->get( 'sort_columns' );

		if ( empty( $sort_columns ) ) {
            return $column_label;
		}

		if ( ! \GravityView_frontend::getInstance()->is_field_sortable( $context->field->ID, $context->view->form->form ) ) {
			return $column_label;
		}

		$sorting = array();

		$directions = $context->view->settings->get( 'sort_direction' );

		$sorts = Utils::_GET( 'sort' );

		if ( $sorts ) {
			if ( is_array( $sorts ) ) {
				foreach ( (array) $sorts as $key => $direction ) {
					if ( $key == $context->field->ID ) {
						$sorting['key']       = $context->field->ID;
						$sorting['direction'] = strtolower( $direction );
						break;
					}
				}
			} elseif ( $sorts == $context->field->ID ) {
					$sorting['key']       = $context->field->ID;
					$sorting['direction'] = strtolower( Utils::_GET( 'dir', '' ) );
			}
		} else {
			foreach ( (array) $context->view->settings->get( 'sort_field', array() ) as $i => $sort_field ) {
				if ( $sort_field == $context->field->ID ) {
					$sorting['key']       = $sort_field;
					$sorting['direction'] = strtolower( Utils::get( $directions, $i, '' ) );
					break; // Only get the first sort
				}
			}
		}

		$class = 'gv-sort';

		$sort_args = array(
			sprintf( 'sort[%s]', $context->field->ID ),
			'asc',
		);

		// If we are already sorting by the current field...
		if ( ! empty( $sorting['key'] ) && (string) $context->field->ID === (string) $sorting['key'] ) {

		    switch ( $sorting['direction'] ) {
		        // No sort
                case '':
	                $sort_args[1] = 'asc';
	                $class       .= ' gv-icon-caret-up-down';
                    break;
                case 'desc':
	                $sort_args[1] = '';
	                $class       .= ' gv-icon-sort-asc';
	                break;
                case 'asc':
                default:
                    $sort_args[1] = 'desc';
                    $class       .= ' gv-icon-sort-desc';
                    break;
            }
		} else {
			$class .= ' gv-icon-caret-up-down';
		}

		$url           = remove_query_arg( array( 'pagenum' ) );
		$url           = remove_query_arg( 'sort', $url );
		$multisort_url = self::_get_multisort_url( $url, $sort_args, $context->field->ID );

    	$url = add_query_arg( $sort_args[0], $sort_args[1], $url );

		$return = '<a href="' . esc_url_raw( $url ) . '"';

		if ( ! empty( $multisort_url ) ) {
			$return .= ' data-multisort-href="' . esc_url_raw( $multisort_url ) . '"';
		}

		$return .= ' class="' . $class . '" ></a>&nbsp;' . $column_label;

		return $return;
	}

	/**
     * Get the multi-sort URL used in the sorting links
     *
     * @todo Consider moving to Utils?
     *
     * @since 2.3
     *
     * @see add_columns_sort_links
	 * @param string     $url Single-sort URL
	 * @param array      $sort_args Single sorting for rules, in [ field_id, dir ] format
     * @param string|int $field_id ID of the current field being displayed
     *
     * @return string Multisort URL, if there are multiple sorts. Otherwise, existing $url
	 */
	public static function _get_multisort_url( $url, $sort_args, $field_id ) {

		$sorts = Utils::_GET( 'sort' );

		if ( ! is_array( $sorts ) ) {
            return $url;
		}

        $multisort_url = $url;

		// If the field has already been sorted by, add the field to the URL
        if ( ! in_array( $field_id, $keys = array_keys( $sorts ) ) ) {
            if ( count( $keys ) ) {
                $multisort_url = add_query_arg( sprintf( 'sort[%s]', end( $keys ) ), $sorts[ end( $keys ) ], $multisort_url );
                $multisort_url = add_query_arg( $sort_args[0], $sort_args[1], $multisort_url );
            } else {
                $multisort_url = add_query_arg( $sort_args[0], $sort_args[1], $multisort_url );
            }
        }
        // Otherwise, we are just updating the sort order
        else {

            // Pass empty value to unset
            if ( '' === $sort_args[1] ) {
	            unset( $sorts[ $field_id ] );
            } else {
	            $sorts[ $field_id ] = $sort_args[1];
            }

            $multisort_url = add_query_arg( array( 'sort' => $sorts ), $multisort_url );
        }

		return $multisort_url;
	}

	/**
	 * Output the table column names.
	 *
	 * @return void
	 */
	public function the_columns() {
		$fields = $this->view->fields->by_position( 'directory_table-columns' );

		foreach ( $fields->by_visible( $this->view )->all() as $field ) {
			$context = Template_Context::from_template( $this, compact( 'field' ) );

			$args = array(
				'field'        => is_numeric( $field->ID ) ? $field->as_configuration() : null,
				'hide_empty'   => false,
				'zone_id'      => 'directory_table-columns',
				'markup'       => '<th id="{{ field_id }}" class="{{ class }}" style="{{width:style}}" data-label="{{label_value:data-label}}">{{label}}</th>',
				'label_markup' => '<span class="gv-field-label">{{ label }}</span>',
				'label'        => self::get_field_column_label( $field, $context ),
			);

			echo \gravityview_field_output( $args, $context );
		}
	}

	/**
     * Returns the label for a column, with support for all deprecated filters
     *
     * @since 2.1
     *
	 * @param \GV\Field            $field
	 * @param \GV\Template_Context $context
	 */
	protected static function get_field_column_label( $field, $context = null ) {

		$form = $field->form_id ? GF_Form::by_id( $field->form_id ) : $context->view->form;

		/**
		 * @deprecated Here for back-compatibility.
		 */
		$column_label = apply_filters( 'gravityview_render_after_label', $field->get_label( $context->view, $form ), $field->as_configuration() );
		$column_label = apply_filters( 'gravityview/template/field_label', $column_label, $field->as_configuration(), ( $form && $form->form ) ? $form->form : null, null );

		/**
		 * Override the field label.
		 *
		 * @since 2.0
		 *
		 * @param string               $column_label The label to override.
		 * @param \GV\Template_Context $context      The context. Does not have entry set here.
		 */
		$column_label = apply_filters( 'gravityview/template/field/label', $column_label, $context );

		return $column_label;
    }

	/**
	 * Output the entry row.
	 *
	 * @param \GV\Entry $entry The entry to be rendered.
	 * @param array     $attributes The attributes for the <tr> tag
	 *
	 * @return void
	 */
	public function the_entry( \GV\Entry $entry, $attributes ) {

		$fields = $this->view->fields->by_position( 'directory_table-columns' )->by_visible( $this->view );

		$context = Template_Context::from_template( $this, compact( 'entry', 'fields' ) );

		/**
		 * Push legacy entry context.
		 */
		\GV\Mocks\Legacy_Context::load(
            array(
				'entry' => $entry,
            )
        );

		/**
		 * Modify the fields displayed in a table.
		 *
		 * @param array $fields The fields. Refer to \GV\Field_Collection::from_configuration() for structure.
		 * @param \GravityView_View $gravityview_view The GravityView_View object.
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

		$context = Template_Context::from_template( $this, compact( 'entry', 'fields' ) );

		/**
		 * Filter the row attributes for the row in table view.
		 *
		 * @since 2.0
		 *
		 * @param array                $attributes The HTML attributes.
		 * @param \GV\Template_Context $context    The context.
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
				 * Fires while rendering each entry row, before the cells. Can be used to insert additional table cells.
				 *
				 * @since 2.0
				 *
				 * @param \GV\Template_Context $context The context.
				 */
				do_action( 'gravityview/template/table/cells/before', $context );

				/**
				 * while rendering each entry in the loop. Can be used to insert additional table cells.
				 *
				 * @deprecated Use `gravityview/template/table/cells/before`
				 * @since 1.0.7
				 *
				 * @param \GravityView_View $gravityview_view Current GravityView_View object
				 */
				do_action( 'gravityview_table_cells_before', \GravityView_View::getInstance() );

                foreach ( $fields->all() as $field ) {
					if ( isset( $this->view->unions[ $entry['form_id'] ] ) ) {
						if ( isset( $this->view->unions[ $entry['form_id'] ][ $field->ID ] ) ) {
							$field = $this->view->unions[ $entry['form_id'] ][ $field->ID ];
						} elseif ( ! $field instanceof Internal_Field ) {
								$field = Internal_Field::from_configuration( array( 'id' => 'custom' ) );
						}
					}
					$this->the_field( $field, $entry );
				}

				/**
				 * Fires while rendering each entry row, after the cells. Can be used to insert additional table cells.
				 *
				 * @since 2.0
				 *
				 * @param \GV\Template_Context $context The context.
				 */
				do_action( 'gravityview/template/table/cells/after', $context );

				/**
				 * while rendering each entry in the loop. Can be used to insert additional table cells.
				 *
				 * @deprecated Use `gravityview/template/table/cells/after`
				 * @since 1.0.7
				 *
				 * @param \GravityView_View $gravityview_view Current GravityView_View object
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
		$form         = $this->view->form;
		$single_entry = $entry;

		/**
		 * Push legacy entry context.
		 */
		\GV\Mocks\Legacy_Context::load(
            array(
				'field' => $field,
            )
        );

		if ( $entry->is_multi() ) {
			$single_entry = $entry->from_field( $field );
			$form         = GF_Form::by_id( $field->form_id );
		}

		$renderer = new Field_Renderer();
		$source   = is_numeric( $field->ID ) ? $form : new Internal_Source();

		$value = $renderer->render( $field, $this->view, $source, $entry, $this->request );

		$context        = Template_Context::from_template( $this, compact( 'field' ) );
		$context->entry = $single_entry;

		$args = array(
			'entry'      => $entry->as_entry(),
			'field'      => is_numeric( $field->ID ) ? $field->as_configuration() : null,
			'value'      => $value,
			'hide_empty' => false,
			'zone_id'    => 'directory_table-columns',
            'label'      => self::get_field_column_label( $field, $context ),
			'markup'     => '<td id="{{ field_id }}" class="{{ class }}" data-label="{{label_value:data-label}}">{{ value }}</td>',
            'form'       => $form,
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
		 * Fires inside the `tbody` of the table, before any rows are rendered.
		 *
		 * @since 2.0
		 *
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/body/before', $context );

		/**
		* Inside the `tbody`, before any rows are rendered. Can be used to insert additional rows.
		 *
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
		 * Fires inside the `tbody` of the table, after all rows are rendered.
		 *
		 * @since 2.0
		 *
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/body/after', $context );

		/**
		* Inside the `tbody`, after any rows are rendered. Can be used to insert additional rows.
		 *
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
		 * Fires inside the `tr` element of the table, before cell rendering begins.
		 *
		 * @since 2.0
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/tr/before', $context );

		/**
		 * Fires while rendering each entry in the loop. Can be used to insert additional table rows.
		 *
		 * @since 1.0.7
		 * @deprecated Use `gravityview/template/table/tr/before`
		 * @param \GravityView_View $gravityview_view Current GravityView_View object.
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
		 * Fires inside the `tr` element of the table, after cell rendering completes.
		 *
		 * @since 2.0
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/tr/after', $context );

		/**
		 * Fires while rendering each entry in the loop. Can be used to insert additional table cells.
		 *
		 * @since 1.0.7
		 * @deprecated Use `gravityview/template/table/tr/after`
		 * @param \GravityView_View $gravityview_view Current GravityView_View object.
		 */
		do_action( 'gravityview_table_tr_after', \GravityView_View::getInstance() /** ugh! */ );
	}

	/**
	 * `gravityview_entry_class` and `gravityview/template/table/entry/class` filters.
	 *
	 * Modify of the class of a row.
	 *
	 * @param string                           $class The class.
	 * @param \GV\Entry                        $entry The entry.
	 * @param \GV\Template_Context The context.
	 *
	 * @return string The classes.
	 */
	public static function entry_class( $class, $entry, $context ) {
		/**
		 * Modify the class applied to the entry row.
		 *
		 * @param string $class Existing class.
		 * @param array $entry Current entry being displayed
		 * @param \GravityView_View $this Current GravityView_View object
		 * @deprecated Use `gravityview/template/table/entry/class`
		 * @return string The modified class.
		 */
		$class = apply_filters( 'gravityview_entry_class', $class, $entry->as_entry(), \GravityView_View::getInstance() );

		/**
		 * Modify the class applied to the entry row.
		 *
		 * @since 2.0.6.1
		 *
		 * @param string               $class   The existing class.
		 * @param \GV\Template_Context $context The context.
		 *
		 * @return string The modified class.
		 */
		return apply_filters( 'gravityview/template/table/entry/class', $class, Template_Context::from_template( $context->template, compact( 'entry' ) ) );
	}
}
