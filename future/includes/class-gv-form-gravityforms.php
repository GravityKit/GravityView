<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The Gravity Forms Form class implementation.
 *
 * Accessible as an array for back-compatibility.
 */
class GF_Form extends Form implements \ArrayAccess {

	/**
	 * @var string The identifier of the backend used for this form.
	 * @api
	 * @since 2.0
	 */
	public static $backend = self::BACKEND_GRAVITYFORMS;

	/**
	 * Initialization.
	 */
	private function __construct() {
		if ( ! class_exists( 'GFAPI' ) ) {
			gravityview()->log->error( 'Gravity Forms plugin is not active.' );
		}
	}

	/**
	 * Construct a \GV\GF_Form instance by ID.
	 *
	 * @param int|string $form_id The internal form ID.
	 *
	 * @api
	 * @since 2.0
	 * @return \GV\GF_Form|null An instance of this form or null if not found.
	 */
	public static function by_id( $form_id ) {

		$form = wp_cache_get( 'gf_form_' . $form_id, 'gravityview' );

		if ( ! $form ) {
			$form = \GFAPI::get_form( $form_id );
		}

		if ( ! $form ) {
			return null;
		}

		wp_cache_set( 'gf_form_' . $form_id, $form, 'gravityview' );

		$self = new self();
		$self->form = $form;

		$self->ID = intval( $self->form['id'] );

		return $self;
	}

	/**
	 * Construct a \GV\Form instance from a Gravity Forms form array.
	 *
	 * @since 2.0.7
	 *
	 * @param array $form The form array
	 *
	 * @return \GV\GF_Form|null An instance of this form or null if not found.
	 */
	public static function from_form( $form ) {
		if ( empty( $form['id'] ) ) {
			return null;
		}

		$self = new self();
		$self->form = $form;
		$self->ID = $self->form['id'];

		return $self;
	}

	/**
	 * Return the query class for this View.
	 *
	 * @return string The class name.
	 */
	public function get_query_class( $view ) {
		/**
		 * @filter `gravityview/query/class`
		 * @param[in,out] string The query class. Default: GF_Query.
		 * @param \GV\View $this The View.
		 */
		$query_class = apply_filters( 'gravityview/query/class', '\GF_Query', $view );
		return $query_class;
	}

	/**
	 * Get all entries for this form.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @param \GV\View $view The View context.
	 *
	 * @return \GV\Entry_Collection The \GV\Entry_Collection
	 */
	public function get_entries( $view ) {
		$entries = new \GV\Entry_Collection();

		$form = &$this;

		if ( gravityview()->plugin->supports( Plugin::FEATURE_GFQUERY ) ) { // @todo switch to GFAPI:: once they start supporting nested and joins stuff
			$entries->add_fetch_callback( function( $filters, $sorts, $offset ) use ( $view, &$form ) {
				$atts = $view->settings->as_atts();

				$entries = new \GV\Entry_Collection();

				$search_criteria = array();
				$sorting = array();
				$paging = array();

				/** Apply the filters */
				foreach ( $filters as $filter ) {
					$search_criteria = $filter::merge_search_criteria( $search_criteria, $filter->as_search_criteria() );
				}

				/** Apply the sorts */
				foreach ( $sorts as $sort ) {
					/** Gravity Forms does not have multi-sorting, so just overwrite. */
					$sorting = array(
						'key' => $sort->field->ID,
						'direction' => $sort->direction,
						'is_numeric' => $sort->mode == Entry_Sort::NUMERIC,
					);
				}

				/** The offset and limit */
				if ( ! empty( $offset->limit ) ) {
					$paging['page_size'] = $offset->limit;
				}

				if ( ! empty( $offset->offset ) ) {
					$paging['offset'] = $offset->offset;
				}
				
				$query_class = $form->get_query_class( $view );

				/** @var \GF_Query $query */
				$query = new $query_class( $form->ID, $search_criteria, $sorting, $paging );

				/**
				 * Apply multisort.
				 */
				if ( is_array( $sort_fields = \GV\Utils::get( $atts, 'sort_field' ) ) && ! empty( $sort_fields ) ) {
					$view_setting_sort_field_ids = \GV\Utils::get( $atts, 'sort_field', array() );
					$view_setting_sort_directions = \GV\Utils::get( $atts, 'sort_direction', array() );

					$has_sort_query_param = ! empty( $_GET['sort'] ) && is_array( $_GET['sort'] );

					if( $has_sort_query_param ) {
						$has_sort_query_param = array_filter( array_values( $_GET['sort'] ) );
					}

					if ( $view->settings->get( 'sort_columns' ) && $has_sort_query_param ) {
						$sort_field_ids = array_keys( $_GET['sort'] );
						$sort_directions = array_values( $_GET['sort'] );
					} else {
						$sort_field_ids = $view_setting_sort_field_ids;
						$sort_directions = $view_setting_sort_directions;
					}

					$skip_first = false;

					foreach ( (array) $sort_field_ids as $key => $sort_field_id ) {

						if ( ! $skip_first && ! $has_sort_query_param ) {
							$skip_first = true; // Skip the first one, it's already in the query
							continue;
						}

						$sort_field_id = \GravityView_frontend::_override_sorting_id_by_field_type( $sort_field_id, $form->ID );
						$sort_direction = strtoupper( \GV\Utils::get( $sort_directions, $key, 'ASC' ) );

						if ( ! empty( $sort_field_id ) ) {
							$order = new \GF_Query_Column( $sort_field_id, $form->ID );
							if ( \GVCommon::is_field_numeric( $form->ID, $sort_field_id ) ) {
								$order = \GF_Query_Call::CAST( $order, defined( 'GF_Query::TYPE_DECIMAL' ) ? \GF_Query::TYPE_DECIMAL : \GF_Query::TYPE_SIGNED );
							}

							$query->order( $order, $sort_direction );
						}
					}
				}

				/**
				 * Merge time subfield sorts.
				 */
				add_filter( 'gform_gf_query_sql', $gf_query_timesort_sql_callback = function( $sql ) use ( &$query ) {
					$q = $query->_introspect();
					$orders = array();

					$merged_time = false;

					foreach ( $q['order'] as $oid => $order ) {
						if ( $order[0] instanceof \GF_Query_Column ) {
							$column = $order[0];
						} else if ( $order[0] instanceof \GF_Query_Call ) {
							if ( count( $order[0]->columns ) != 1 || ! $order[0]->columns[0] instanceof \GF_Query_Column ) {
								$orders[ $oid ] = $order;
								continue; // Need something that resembles a single sort
							}
							$column = $order[0]->columns[0];
						}

						if ( ( ! $field = \GFAPI::get_field( $column->source, $column->field_id ) ) || $field->type !== 'time' ) {
							$orders[ $oid ] = $order;
							continue; // Not a time field
						}

						if ( ! class_exists( '\GV\Mocks\GF_Query_Call_TIMESORT' ) ) {
							require_once gravityview()->plugin->dir( 'future/_mocks.timesort.php' );
						}

						$orders[ $oid ] = array(
							new \GV\Mocks\GF_Query_Call_TIMESORT( 'timesort', array( $column, $sql ) ),
							$order[1] // Mock it!
						);

						$merged_time = true;
					}

					if ( $merged_time ) {
						/**
						 * ORDER again.
						 */
						if ( ! empty( $orders ) && $_orders = $query->_order_generate( $orders ) ) {
							$sql['order'] = 'ORDER BY ' . implode( ', ', $_orders );
						}
					}

					return $sql;
				} );

				/**
				 * Any joins?
				 */
				if ( gravityview()->plugin->supports( Plugin::FEATURE_JOINS ) && count( $view->joins ) ) {

					$is_admin_and_can_view = $view->settings->get( 'admin_show_all_statuses' ) && \GVCommon::has_cap( 'gravityview_moderate_entries', $view->ID );

					foreach ( $view->joins as $join ) {
						$query = $join->as_query_join( $query );

						if ( $view->settings->get( 'multiple_forms_disable_null_joins' ) ) {

							// Disable NULL outputs
							$condition = new \GF_Query_Condition(
								new \GF_Query_Column( $join->join_on_column->ID, $join->join_on->ID ),
								\GF_Query_Condition::NEQ,
								new \GF_Query_Literal( '' )
							);

							$query_parameters = $query->_introspect();

							$query->where( \GF_Query_Condition::_and( $query_parameters['where'], $condition ) );
						}

						/**
						 * This is a temporary stub filter, until GF_Query supports NULL conditions.
						 * Do not use! This filter will be removed.
						 */
						if ( defined( 'GF_Query_Condition::NULL' ) ) {
							$is_null_condition_native = true;
						} else {
							$is_null_condition_class = apply_filters( 'gravityview/query/is_null_condition', null );
							$is_null_condition_native = false;
						}

						// Filter to active entries only
						$condition = new \GF_Query_Condition(
							new \GF_Query_Column( 'status', $join->join_on->ID ),
							\GF_Query_Condition::EQ,
							new \GF_Query_Literal( 'active' )
						);

						if ( $is_null_condition_native ) {
							$condition = \GF_Query_Condition::_or( $condition, new \GF_Query_Condition(
								new \GF_Query_Column( 'status', $join->join_on->ID ),
								\GF_Query_Condition::IS,
								\GF_Query_Condition::NULL
							) );
						} else if ( ! is_null( $is_null_condition_class ) ) {
							$condition = \GF_Query_Condition::_or( $condition, new $is_null_condition_class(
								new \GF_Query_Column( 'status', $join->join_on->ID )
							) );
						}

						$q = $query->_introspect();
						$query->where( \GF_Query_Condition::_and( $q['where'], $condition ) );

						if ( $view->settings->get( 'show_only_approved' ) && ! $is_admin_and_can_view ) {

							// Show only approved joined entries
							$condition = new \GF_Query_Condition(
								new \GF_Query_Column( \GravityView_Entry_Approval::meta_key, $join->join_on->ID ),
								\GF_Query_Condition::EQ,
								new \GF_Query_Literal( \GravityView_Entry_Approval_Status::APPROVED )
							);

							if ( $is_null_condition_native ) {
								$condition = \GF_Query_Condition::_or( $condition, new \GF_Query_Condition(
									new \GF_Query_Column( \GravityView_Entry_Approval::meta_key, $join->join_on->ID ),
									\GF_Query_Condition::IS,
									\GF_Query_Condition::NULL
								) );
							} else if ( ! is_null( $is_null_condition_class ) ) {
								$condition = \GF_Query_Condition::_or( $condition, new $is_null_condition_class(
									new \GF_Query_Column( \GravityView_Entry_Approval::meta_key, $join->join_on->ID )
								) );
							}

							$query_parameters = $query->_introspect();

							$query->where( \GF_Query_Condition::_and( $query_parameters['where'], $condition ) );
						}
					}
				/**
				 * Unions?
				 */
				} else if ( gravityview()->plugin->supports( Plugin::FEATURE_UNIONS ) && count( $view->unions ) ) {
					$query_parameters = $query->_introspect();

					$unions_sql = array();

					/**
					 * @param \GF_Query_Condition $condition
					 * @param array $fields
					 * @param $recurse
					 *
					 * @return \GF_Query_Condition
					 */
					$where_union_substitute = function( $condition, $fields, $recurse ) {
						if ( $condition->expressions ) {
							$conditions = array();

							foreach ( $condition->expressions as $_condition ) {
								$conditions[] = $recurse( $_condition, $fields, $recurse );
							}

							return call_user_func_array(
								array( '\GF_Query_Condition', $condition->operator == 'AND' ? '_and' : '_or' ),
								$conditions
							);
						}

						if ( ! ( $condition->left && $condition->left instanceof \GF_Query_Column ) || ( ! $condition->left->is_entry_column() && ! $condition->left->is_meta_column() ) ) {
							return new \GF_Query_Condition(
								new \GF_Query_Column( $fields[ $condition->left->field_id ]->ID ),
								$condition->operator,
								$condition->right
							);
						}

						return $condition;
					};

					foreach ( $view->unions as $form_id => $fields ) {

						// Build a new query for every unioned form

						/** @var \GF_Query|\GF_Patched_Query $q */
						$q = new $query_class( $form_id );

						// Copy the WHERE clauses but substitute the field_ids to the respective ones
						$q->where( $where_union_substitute( $query_parameters['where'], $fields, $where_union_substitute ) );

						// Copy the ORDER clause and substitute the field_ids to the respective ones
						foreach ( $query_parameters['order'] as $order ) {
							list( $column, $_order ) = $order;

							if ( $column && $column instanceof \GF_Query_Column ) {
								if ( ! $column->is_entry_column() && ! $column->is_meta_column() ) {
									$column = new \GF_Query_Column( $fields[ $column->field_id ]->ID );
								}

								$q->order( $column, $_order );
							}
						}

						add_filter( 'gform_gf_query_sql', $gf_query_sql_callback = function( $sql ) use ( &$unions_sql ) {
							// Remove SQL_CALC_FOUND_ROWS as it's not needed in UNION clauses
							$select = 'UNION ALL ' . str_replace( 'SQL_CALC_FOUND_ROWS ', '', $sql['select'] );

							// Record the SQL
							$unions_sql[] = array(
								// Remove columns, we'll rebuild them
								'select'  => preg_replace( '#DISTINCT (.*)#', 'DISTINCT ', $select ),
								'from'    => $sql['from'],
								'join'    => $sql['join'],
								'where'   => $sql['where'],
								// Remove order and limit
							);

							// Return empty query, no need to call the database
							return array();
						} );

						do_action_ref_array( 'gravityview/view/query', array( &$q, $view, gravityview()->request ) );

						$q->get(); // Launch

						remove_filter( 'gform_gf_query_sql', $gf_query_sql_callback );
					}

					add_filter( 'gform_gf_query_sql', $gf_query_sql_callback = function( $sql ) use ( $unions_sql ) {
						// Remove SQL_CALC_FOUND_ROWS as it's not needed in UNION clauses
						$sql['select'] = str_replace( 'SQL_CALC_FOUND_ROWS ', '', $sql['select'] );

						// Remove columns, we'll rebuild them
						preg_match( '#DISTINCT (`[motc]\d+`.`.*?`)#', $sql['select'], $select_match );
						$sql['select'] = preg_replace( '#DISTINCT (.*)#', 'DISTINCT ', $sql['select'] );

						$unions = array();

						// Transform selected columns to shared alias names
						$column_to_alias = function( $column ) {
							$column = str_replace( '`', '', $column );
							return '`' . str_replace( '.', '_', $column ) . '`';
						};

						// Add all the order columns into the selects, so we can order by the whole union group
						preg_match_all( '#(`[motc]\d+`.`.*?`)#', $sql['order'], $order_matches );
						
						$columns = array(
							sprintf( '%s AS %s', $select_match[1], $column_to_alias( $select_match[1] ) )
						);

						foreach ( array_slice( $order_matches, 1 ) as $match ) {
							$columns[] = sprintf( '%s AS %s', $match[0], $column_to_alias( $match[0] ) );

							// Rewrite the order columns to the shared aliases
							$sql['order'] = str_replace( $match[0], $column_to_alias( $match[0] ), $sql['order'] );
						}

						$columns = array_unique( $columns );

						// Add the columns to every UNION
						foreach ( $unions_sql as $union_sql ) {
							$union_sql['select'] .= implode( ', ', $columns );
							$unions []= implode( ' ', $union_sql );
						}

						// Add the columns to the main SELECT, but only grab the entry id column
						$sql['select'] = 'SELECT SQL_CALC_FOUND_ROWS t1_id FROM (' . $sql['select'] . implode( ', ', $columns );
						$sql['order'] = implode( ' ', $unions ) . ') AS u ' . $sql['order'];

						return $sql;
					} );
				}

				/**
				 * @action `gravityview/view/query` Override the \GF_Query before the get() call.
				 * @param \GF_Query $query The current query object reference
				 * @param \GV\View $view The current view object
				 * @param \GV\Request $request The request object
				 */
				do_action_ref_array( 'gravityview/view/query', array( &$query, $view, gravityview()->request ) );

				gravityview()->log->debug( 'GF_Query parameters: ', array( 'data' => Utils::gf_query_debug( $query ) ) );

				/**
				 * Map from Gravity Forms entries arrays to an Entry_Collection.
				 */
				if ( count( $view->joins ) ) {
					foreach ( $query->get() as $entry ) {
						$entries->add(
							Multi_Entry::from_entries( array_map( '\GV\GF_Entry::from_entry', $entry ) )
						);
					}
				} else {
					array_map( array( $entries, 'add' ), array_map( '\GV\GF_Entry::from_entry', $query->get() ) );
				}

				if ( isset( $gf_query_sql_callback ) ) {
					remove_action( 'gform_gf_query_sql', $gf_query_sql_callback );
				}

				if ( isset( $gf_query_timesort_sql_callback ) ) {
					remove_action( 'gform_gf_query_sql', $gf_query_timesort_sql_callback );
				}

				return $entries;
			} );

			$entries->add_count_callback( function( $filters ) use ( $entries ) {
				$query = null;

				add_action( 'gravityview/view/query', $q_callback = function( &$q ) use ( &$query ) {
					$query = $q;
				} );

				foreach ( $filters as $filter ) {
					$entries = $entries->filter( $filter );
				}
				$entries->fetch(); // Gravity Forms requires a fetch before total can be had

				remove_action( 'gravityview/view/query', $q_callback );

				if ( ! is_object( $query ) ) {
					return 0;
				}

				return $query->total_found;
			} );
		} else {
			/** Add the fetcher lazy callback. */
			$entries->add_fetch_callback( function( $filters, $sorts, $offset ) use ( $form ) {
				$entries = new \GV\Entry_Collection();

				$search_criteria = array();
				$sorting = array();
				$paging = array();

				/** Apply the filters */
				foreach ( $filters as $filter ) {
					$search_criteria = $filter::merge_search_criteria( $search_criteria, $filter->as_search_criteria() );
				}

				/** Apply the sorts */
				foreach ( $sorts as $sort ) {
					/** Gravity Forms does not have multi-sorting, so just overwrite. */
					$sorting = array(
						'key' => $sort->field->ID,
						'direction' => $sort->direction,
						'is_numeric' => $sort->mode == Entry_Sort::NUMERIC,
					);
				}

				/** The offset and limit */
				if ( ! empty( $offset->limit ) ) {
					$paging['page_size'] = $offset->limit;
				}

				if ( ! empty( $offset->offset ) ) {
					$paging['offset'] = $offset->offset;
				}

				foreach ( \GFAPI::get_entries( $form->ID, $search_criteria, $sorting, $paging ) as $entry ) {
					$entries->add( \GV\GF_Entry::from_entry( $entry ) );
				}

				return $entries;
			} );

			/** Add the counter lazy callback. */
			$entries->add_count_callback( function( $filters ) use ( $form ) {
				$search_criteria = array();
				$sorting = array();

				/** Apply the filters */
				/** @var \GV\GF_Entry_Filter|\GV\Entry_Filter $filter */
				foreach ( $filters as $filter ) {
					$search_criteria = $filter::merge_search_criteria( $search_criteria, $filter->as_search_criteria() );
				}

				return \GFAPI::count_entries( $form->ID, $search_criteria );
			} );
		}

		return $entries;
	}

	/**
	 * Get a \GV\Field by Form and Field ID for this data source.
	 *
	 * @param \GV\GF_Form $form The Gravity Form form ID.
	 * @param int $field_id The Gravity Form field ID for the $form_id.
	 *
	 * @return \GV\Field|null The requested field or null if not found.
	 */
	public static function get_field( /** varargs */ ) {
		$args = func_get_args();

		if ( ! is_array( $args ) || count( $args ) != 2 ) {
			gravityview()->log->error( '{source} expects 2 arguments for ::get_field ($form, $field_id)', array( 'source' => __CLASS__ ) );
			return null;
		}

		/** Unwrap the arguments. */
		list( $form, $field_id ) = $args;

		/** Wrap it up into a \GV\Field. */
		return GF_Field::by_id( $form, $field_id );
	}

	/**
	 * Get an array of GV Fields for this data source
	 *
	 * @return \GV\Field[]|array Empty array if no fields
	 */
	public function get_fields() {
		$fields = array();
		foreach ( $this['fields'] as $field ) {
			foreach ( empty( $field['inputs'] ) ? array( $field['id'] ) : wp_list_pluck( $field['inputs'], 'id' ) as $id ) {
				if ( is_numeric( $id ) ) {
					$fields[ $id ] = self::get_field( $this, $id );
				} else {
					$fields[ $id ] = Internal_Field::by_id( $id );
				}
			}
		}

		return array_filter( $fields );
	}

	/**
	 * Proxies.
	 *
	 * @param string $key The property to get.
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		switch ( $key ) {
			case 'fields':
				return $this->get_fields();
			default:
				return parent::__get( $key );
		}
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 * @return bool Whether the offset exists or not.
	 */
	public function offsetExists( $offset ) {
		return isset( $this->form[$offset] );
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * Maps the old keys to the new data;
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 *
	 * @return mixed The value of the requested form data.
	 */
	public function offsetGet( $offset ) {
		return $this->form[$offset];
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value ) {
		gravityview()->log->error( 'The underlying Gravity Forms form is immutable. This is a \GV\Form object and should not be accessed as an array.' );
	}

	/**
	 * ArrayAccess compatibility layer with a Gravity Forms form array.
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		gravityview()->log->error( 'The underlying Gravity Forms form is immutable. This is a \GV\Form object and should not be accessed as an array.' );
	}
}
