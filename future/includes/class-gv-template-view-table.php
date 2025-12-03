<?php
namespace GV;

use GravityView_Field_Repeater;

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
	 * @param Field                $field
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
		 * @param string $column_label The label to override.
		 * @param \GV\Template_Context $context The context. Does not have entry set here.
		 */
		$column_label = apply_filters( 'gravityview/template/field/label', $column_label, $context );

		return $column_label;
    }

	/**
	 * Output the entry row.
	 *
	 * @param Entry $entry      The entry to be rendered.
	 * @param array $attributes The attributes for the <tr> tag
	 *
	 * @return void
	 */
	public function the_entry( Entry $entry, $attributes ) {

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
		 * @param \GV\Field_Collection $fields The fields.
		 * @param \GV\Template_Context $context The context.
		 * @since 2.0
		 */
		$fields = apply_filters( 'gravityview/template/table/fields', $fields, $context );

		$context = Template_Context::from_template( $this, compact( 'entry', 'fields' ) );

		/**
		 * Filter the row attributes for the row in table view.
		 *
		 * @param array $attributes The HTML attributes.
		 * @param \GV\Template_Context $context The context.
		 *
		 * @since 2.0
		 */
		$attributes = apply_filters( 'gravityview/template/table/entry/row/attributes', $attributes, $context );

		/** Glue the attributes together. */
		foreach ( $attributes as $attribute => $value ) {
			$attributes[ $attribute ] = sprintf( "$attribute=\"%s\"", esc_attr( $value ) );
		}

		foreach ( $this->get_entry_rows( $entry, $fields ) as $r => $row ) {
			$row_attributes = $attributes;
			if ( ! isset( $attributes['data-row'] ) ) {
				$row_attributes[] = sprintf( 'data-row="%d"', $r );
			}

			$row_attributes = implode( ' ', $row_attributes );

			// Retrieve the entry and fields for this row only.
			$entry = $row->entry;
			$fields = $row->fields;

			// Update the context.
			$context = Template_Context::from_template( $this, compact( 'entry', 'fields' ) );
			?>
			<tr<?php echo $row_attributes ? " $row_attributes" : ''; ?>>
				<?php

				/**
				 * while rendering each entry in the loop. Can be used to insert additional table cells.
				 *
				 * @since 2.0
				 *
				 * @param \GV\Template_Context $context The context.
				 */
				do_action( 'gravityview/template/table/cells/before', $context );

				/**
				 * while rendering each entry in the loop. Can be used to insert additional table cells.
				 *
				 * @since      1.0.7
				 *
				 * @param \GravityView_View $gravityview_view Current GravityView_View object
				 *
				 * @deprecated Use `gravityview/template/table/cells/before`
				 */
				do_action( 'gravityview_table_cells_before', \GravityView_View::getInstance() );

				foreach ( $fields->all() as $field ) {
					if ( isset( $this->view->unions[ $entry['form_id'] ] ) ) {
						if ( isset( $this->view->unions[ $entry['form_id'] ][ $field->ID ] ) ) {
							$field = $this->view->unions[ $entry['form_id'] ][ $field->ID ];
						} elseif ( ! $field instanceof Internal_Field ) {
							$field = Internal_Field::from_configuration( [ 'id' => 'custom' ] );
						}
					}

					$this->the_field( $field, $entry );
				}

				/**
				 * while rendering each entry in the loop. Can be used to insert additional table cells.
				 *
				 * @since 2.0
				 *
				 * @param \GV\Template_Context $context The context.
				 */
				do_action( 'gravityview/template/table/cells/after', $context );

				/**
				 * while rendering each entry in the loop. Can be used to insert additional table cells.
				 *
				 * @since      1.0.7
				 *
				 * @param \GravityView_View $gravityview_view Current GravityView_View object
				 *
				 * @deprecated Use `gravityview/template/table/cells/after`
				 */
				do_action( 'gravityview_table_cells_after', \GravityView_View::getInstance() );

				?>
			</tr>
			<?php
		}
	}

	/**
	 * Output a field cell.
	 *
	 * @param Field $field The field to be output.
	 * @param Entry $entry The Entry this field is for.
	 *
	 * @return void
	 */
	public function the_field( Field $field, Entry $entry ) {
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

		$rowspan = ( $field->rowspan ??= 1 ) > 1 ? sprintf( ' rowspan="%d"', $field->rowspan ) : '';

		$args = [
			'entry'      => $entry->as_entry(),
			'field'      => is_numeric( $field->ID ) ? $field->as_configuration() : null,
			'value'      => $value,
			'hide_empty' => false,
			'zone_id'    => 'directory_table-columns',
			'label'      => self::get_field_column_label( $field, $context ),
			'markup'     => '<td id="{{ field_id }}"' . $rowspan . ' class="{{ class }}" data-label="{{label_value:data-label}}">{{ value }}</td>',
			'form'       => $form,
		];

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
		 * of the table.
		 *
		 * @since 2.0
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
		 * of the table at the end.
		 *
		 * @since 2.0
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
		 * of the table when there are no results.
		 *
		 * @since 2.0
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/tr/before', $context );

		/**
		 * while rendering each entry in the loop. Can be used to insert additional table rows.
		 *
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
		 * of the table when there are no results.
		 *
		 * @since 2.0
		 * @param \GV\Template_Context $context The template context.
		 */
		do_action( 'gravityview/template/table/tr/after', $context );

		/**
		 * while rendering each entry in the loop. Can be used to insert additional table cells.
		 *
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
	 * @param Entry  $entry The entry.
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
		 * Modify the class aplied to the entry row.
		 *
		 * @param string $class The existing class.
		 * @param \GV\Template_Context The context.
		 * @return string The modified class.
		 */
		return apply_filters( 'gravityview/template/table/entry/class', $class, Template_Context::from_template( $context->template, compact( 'entry' ) ) );
	}

	/**
	 * Calculates the rows based on the visible fields and the available entry data.
	 *
	 * @param Entry            $entry  The entry.
	 * @param Field_Collection $fields The fields.
	 *
	 * @return object{fields:Field_Collection, entry: Entry}[] Rows consisting of fields and the data for that row only.
	 */
	protected function get_entry_rows( Entry $entry, Field_Collection $fields ): array {
		// The default result is the normal Field collection and Entry.
		$default_row = [ (object) compact( 'fields', 'entry' ) ];
		if ( ! $entry instanceof GF_Entry ) {
			return $default_row;
		}

		$form_id = $entry['form_id'] ?? 0;

		// Todo: This needs to be a more generic function somewhere else called: "get_ancestor_mapping()".
		$ancestor_mapping = GravityView_Field_Repeater::get_repeater_field_ids( $form_id );

		// Step 1: Find active ancestors (direct parent repeaters of visible fields).
		$active_ancestors    = [];

		foreach ( $fields->all() as $field ) {
			$ancestors = $ancestor_mapping[ $field->ID ] ?? [];
			if ( ! $ancestors ) {
				continue;
			}
			$direct_parent                       = end( $ancestors );
			$active_ancestors[ $direct_parent ]  = true;

			// Keep track of the parent on the field itself.
			$field->parent_id = $direct_parent;
		}

		if ( ! $active_ancestors ) {
			return $default_row;
		}

		$active_ancestors = array_keys( $active_ancestors );

		// Step 2: Calculate generation for each active ancestor (count of active ancestors it has).
		$ancestor_generation = [];
		foreach ( $active_ancestors as $ancestor ) {
			$ancestor_parents                = $ancestor_mapping[ $ancestor ] ?? [];
			$active_count                    = count( array_intersect( $ancestor_parents, $active_ancestors ) );
			$ancestor_generation[ $ancestor ] = $active_count;
		}

		// Step 3: Group fields by visible level (generation + 1, since level 0 is base).
		$fields_by_level = [];
		foreach ( $fields->all() as $field ) {
			$level = isset( $field->parent_id ) ? $ancestor_generation[ $field->parent_id ] + 1 : 0;

			$fields_by_level[ $level ][ $field->ID ] = $field;
		}

		ksort( $fields_by_level );

		// Step 4: Find root repeaters (generation 0) and sort by generation.
		$repeaters_by_generation = [];
		foreach ( $ancestor_generation as $repeater => $generation ) {
			$repeaters_by_generation[ $generation ][] = $repeater;
		}

		ksort( $repeaters_by_generation );

		// Step 5: Flatten entry data into rows with cells with row-spans.
		$data      = $entry->as_entry();

		// First, calculate the total row count for base field row-spans.
		$total_row_count = $this->count_descendant_rows( $data, $repeaters_by_generation );

		// Prepare base fields (level 0) with full rowspan.
		$base_cells = [];
		foreach ( $fields_by_level[0] ?? [] as $field_id => $field ) {
			// Track rowspan on field.
			$field->rowspan = $total_row_count;

			$value                   = $data[ $field_id ] ?? '';
			$base_cells[ $field_id ] = [
				'rowspan' => $total_row_count,
				'value'   => $value,
			];
		}

		$flat_rows = [];
		$this->flatten_entry_data(
			$data,
			$repeaters_by_generation,
			$ancestor_mapping,
			$fields_by_level,
			0,
			$base_cells,
			$flat_rows,
		);

		$flat_rows[0] = $base_cells + $flat_rows[0];

		// If no rows were generated, return the default.
		if ( ! $flat_rows ) {
			return $default_row;
		}

		// Step 6: Convert flat rows to row objects with Field_Collection and Entry.
		$rows = [];
		foreach ( $flat_rows as $row_data ) {
			$row_fields = new Field_Collection();

			foreach ( $fields->all() as $field ) {
				$field_id = $field->ID;

				// Check if this field should be rendered on this row.
				if ( ! isset( $row_data[ $field_id ] ) ) {
					continue;
				}

				$cell_info    = $row_data[ $field_id ];
				$field_config = $field->as_configuration();

				$field = Field::from_configuration( $field_config );
				$field->rowspan = $cell_info['rowspan'] ?? 0;

				$row_fields->add( $field );
			}

			// Create entry with row-specific data.
			$row_entry_data = $data;
			foreach ( $row_data as $field_id => $field_data ) {
				$row_entry_data[ $field_id ] = $field_data['value'] ?? '';
			}

			$row_entry = GF_Entry::from_entry( $row_entry_data );

			$rows[] = (object) [
				'fields' => $row_fields,
				'entry'  => $row_entry,
			];
		}

		return $rows;
	}

	/**
	 * Recursively flattens entry data into rows with rowspan information.
	 *
	 * @since $ver$
	 *
	 * @param array $data               The entry data at the current level.
	 * @param array $repeaters_by_level Repeaters grouped by generation.
	 * @param array $ancestor_mapping   Field ID to ancestor repeater IDs mapping.
	 * @param array $fields_by_level    Fields grouped by visible level.
	 * @param int   $level              The current generation being processed.
	 * @param array $flat_rows          The output array of flattened rows (by reference).
	 */
	private function flatten_entry_data(
		array $data,
		array $repeaters_by_level,
		array $ancestor_mapping,
		array $fields_by_level,
		int $level,
		array $inherited_values,
		array &$flat_rows
	): void {
		$level_fields      = $fields_by_level[ $level + 1 ] ?? [];
		$current_repeaters = $repeaters_by_level[ $level ] ?? [];
		$next_repeaters    = $repeaters_by_level[ $level + 1 ] ?? [];

		// If no repeaters at this level, we're done recursing.
		if ( ! $current_repeaters ) {
			return;
		}

		foreach ( $current_repeaters as $repeater_id ) {
			$repeater_data = $data[ $repeater_id ] ?? [];

			// Ensure repeater_data is an array of items.
			if ( ! is_array( $repeater_data ) || ! $repeater_data ) {
				$repeater_data = [ [] ]; // Single empty item to generate one row.
			}

			foreach ( $repeater_data as $i => $item_data ) {
				if ( ! is_array( $item_data ) ) {
					$item_data = [];
				}

				// Calculate rowspan for this item by recursing into deeper levels.
				$item_row_count = 1;

				if ( $next_repeaters ) {
					$item_row_count = max(
						1,
						$this->count_descendant_rows(
							$item_data,
							$repeaters_by_level,
							$level + 1,
						)
					);
				}

				// Collect field values and cells for this item.
				$item_cells  = [];

				// Add fields at the current level.
				foreach ( $level_fields as $field_id => $field ) {
					if ( $repeater_id !== ( $field->parent_id ?? null ) ) {
						continue;
					}

					$value                    = $item_data[ $field_id ] ?? '';
					$item_cells[ $field_id ]  = [
						'rowspan' => $item_row_count,
						'value'   => $value,
					];
				}

				if ( $next_repeaters ) {
					// Recurse into deeper levels.
					$this->flatten_entry_data(
						$item_data,
						$repeaters_by_level,
						$ancestor_mapping,
						$fields_by_level,
						$level + 1,
						$item_cells,
						$flat_rows
					);
				} else {
					if ( $i === 0 ) {
						// We use + to avoid array_merge from resetting the integer keys.
						$item_cells = $inherited_values + $item_cells;
					}
					// Leaf level - create the row.
					$flat_rows[] = $item_cells;
				}
			}
		}
	}

	/**
	 * Counts the number of descendant rows for rowspan calculation.
	 *
	 * @since $ver$
	 *
	 * @param array $data               The entry data at the current level.
	 * @param array $repeaters_by_level Repeaters grouped by level.
	 * @param int   $level              The current level being processed.
	 *
	 * @return int The number of rows in this subtree.
	 */
	protected function count_descendant_rows( array $data, array $repeaters_by_level, int $level = 0 ): int {
		$current_repeaters = $repeaters_by_level[ $level ] ?? [];

		if ( ! $current_repeaters ) {
			return 1;
		}

		$next_level     = $level + 1;
		$next_repeaters = $repeaters_by_level[ $next_level ] ?? [];

		$total_rows = 0;

		foreach ( $current_repeaters as $repeater_id ) {
			$repeater_data = $data[ $repeater_id ] ?? [];

			if ( ! is_array( $repeater_data ) || ! $repeater_data ) {
				// Empty repeater counts as 1 row.
				++$total_rows;
				continue;
			}

			foreach ( $repeater_data as $item_data ) {
				if ( ! is_array( $item_data ) ) {
					$item_data = [];
				}

				if ( $next_repeaters ) {
					$child_count = $this->count_descendant_rows( $item_data, $repeaters_by_level, $next_level );
					$total_rows  += max( 1, $child_count );
				} else {
					++$total_rows;
				}
			}
		}

		return max( 1, $total_rows );
	}
}
