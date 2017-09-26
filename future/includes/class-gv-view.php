<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The default GravityView View class.
 *
 * Houses all base View functionality.
 *
 * Can be accessed as an array for old compatibility's sake
 *  in line with the elements inside the \GravityView_View_Data::$views array.
 */
class View implements \ArrayAccess {

	/**
	 * @var \WP_Post The backing post instance.
	 */
	private $post;

	/**
	 * @var \GV\View_Settings The settings.
	 *
	 * @api
	 * @since future
	 */
	public $settings;

	/**
	 * @var \GV\Form The backing form for this view.
	 *
	 * Contains the form that is sourced for entries in this view.
	 *
	 * @api
	 * @since future
	 */
	public $form;

	/**
	 * @var \GV\Field_Collection The fields for this view.
	 *
	 * Contains all the fields that are attached to this view.
	 *
	 * @api
	 * @since future
	 */
	public $fields;

	/**
	 * @var \GV\Join[] The joins for all sources in this view.
	 *
	 * @api
	 * @since future
	 */
	public $joins;

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->settings = new View_Settings();
		$this->fields = new Field_Collection();
	}

	/**
	 * Register the gravityview WordPress Custom Post Type.
	 *
	 * @internal
	 * @return void
	 */
	public static function register_post_type() {

		/** Register only once */
		if ( post_type_exists( 'gravityview' ) ) {
			return;
		}

		/**
		 * @filter `gravityview_is_hierarchical` Make GravityView Views hierarchical by returning TRUE
		 * This will allow for Views to be nested with Parents and also allows for menu order to be set in the Page Attributes metabox
		 * @since 1.13
		 * @param boolean $is_hierarchical Default: false
		 */
		$is_hierarchical = (bool)apply_filters( 'gravityview_is_hierarchical', false );

		$supports = array( 'title', 'revisions' );

		if ( $is_hierarchical ) {
			$supports[] = 'page-attributes';
		}

		/**
		 * @filter  `gravityview_post_type_supports` Modify post type support values for `gravityview` post type
		 * @see add_post_type_support()
		 * @since 1.15.2
		 * @param array $supports Array of features associated with a functional area of the edit screen. Default: 'title', 'revisions'. If $is_hierarchical, also 'page-attributes'
		 * @param[in] boolean $is_hierarchical Do Views support parent/child relationships? See `gravityview_is_hierarchical` filter.
		 */
		$supports = apply_filters( 'gravityview_post_type_support', $supports, $is_hierarchical );

		/** Register Custom Post Type - gravityview */
		$labels = array(
			'name'                => _x( 'Views', 'Post Type General Name', 'gravityview' ),
			'singular_name'       => _x( 'View', 'Post Type Singular Name', 'gravityview' ),
			'menu_name'           => _x( 'Views', 'Menu name', 'gravityview' ),
			'parent_item_colon'   => __( 'Parent View:', 'gravityview' ),
			'all_items'           => __( 'All Views', 'gravityview' ),
			'view_item'           => _x( 'View', 'View Item', 'gravityview' ),
			'add_new_item'        => __( 'Add New View', 'gravityview' ),
			'add_new'             => __( 'New View', 'gravityview' ),
			'edit_item'           => __( 'Edit View', 'gravityview' ),
			'update_item'         => __( 'Update View', 'gravityview' ),
			'search_items'        => __( 'Search Views', 'gravityview' ),
			'not_found'           => \GravityView_Admin::no_views_text(),
			'not_found_in_trash'  => __( 'No Views found in Trash', 'gravityview' ),
			'filter_items_list'     => __( 'Filter Views list', 'gravityview' ),
			'items_list_navigation' => __( 'Views list navigation', 'gravityview' ),
			'items_list'            => __( 'Views list', 'gravityview' ),
			'view_items'            => __( 'See Views', 'gravityview' ),
			'attributes'            => __( 'View Attributes', 'gravityview' ),
		);
		$args = array(
			'label'               => __( 'view', 'gravityview' ),
			'description'         => __( 'Create views based on a Gravity Forms form', 'gravityview' ),
			'labels'              => $labels,
			'supports'            => $supports,
			'hierarchical'        => $is_hierarchical,
			/**
			 * @filter `gravityview_direct_access` Should Views be directly accessible, or only visible using the shortcode?
			 * @see https://codex.wordpress.org/Function_Reference/register_post_type#public
			 * @since 1.15.2
			 * @param[in,out] boolean `true`: allow Views to be accessible directly. `false`: Only allow Views to be embedded via shortcode. Default: `true`
			 * @param int $view_id The ID of the View currently being requested. `0` for general setting
			 */
			'public'              => apply_filters( 'gravityview_direct_access', gravityview()->plugin->is_compatible(), 0 ),
			'show_ui'             => gravityview()->plugin->is_compatible(),
			'show_in_menu'        => gravityview()->plugin->is_compatible(),
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 17,
			'menu_icon'           => '',
			'can_export'          => true,
			/**
			 * @filter `gravityview_has_archive` Enable Custom Post Type archive?
			 * @since 1.7.3
			 * @param boolean False: don't have frontend archive; True: yes, have archive. Default: false
			 */
			'has_archive'         => apply_filters( 'gravityview_has_archive', false ),
			'exclude_from_search' => true,
			'rewrite'             => array(
				/**
				 * @filter `gravityview_slug` Modify the url part for a View.
				 * @see http://docs.gravityview.co/article/62-changing-the-view-slug
				 * @param string $slug The slug shown in the URL
				 */
				'slug' => apply_filters( 'gravityview_slug', 'view' ),

				/**
				 * @filter `gravityview/post_type/with_front` Should the permalink structure
				 *  be prepended with the front base.
				 *  (example: if your permalink structure is /blog/, then your links will be: false->/view/, true->/blog/view/).
				 *  Defaults to true.
				 * @see https://codex.wordpress.org/Function_Reference/register_post_type
				 * @since future
				 * @param bool $with_front
				 */
				'with_front' => apply_filters( 'gravityview/post_type/with_front', true ),
			),
			'capability_type'     => 'gravityview',
			'map_meta_cap'        => true,
		);

		register_post_type( 'gravityview', $args );
	}

	/**
	 * A renderer filter for the View post type content.
	 *
	 * @param string $content Should be empty, as we don't store anything there.
	 *
	 * @return string $content The view content as output by the renderers.
	 */
	public static function content( $content ) {
		$request = gravityview()->request;

		/**
		 * This is not a View. Bail.
		 *
		 * Shortcodes and oEmbeds and whatnot will be handled
		 *  elsewhere.
		 */
		if ( ! $view = $request->is_view() ) {
			return $content;
		}

		/**
		 * This View is password protected. Nothing to do here.
		 * WordPress outputs the form automagically inside `get_the_content`.
		 */
		if ( post_password_required( $view->ID ) ) {
			gravityview()->log->notice( 'Post password is required for View #{view_id}', array( 'view_id' => $view->ID ) );
			return __( 'You are not allowed to view this content.', 'gravityview' );
		}

		if ( ! $view->form ) {
			gravityview()->log->notice( 'View #{id} has no form attached to it.', array( 'id' => $view->ID ) );

			/**
			 * This View has no data source. There's nothing to show really.
			 * ...apart from a nice message if the user can do anything about it.
			 */
			if ( \GVCommon::has_cap( array( 'edit_gravityviews', 'edit_gravityview' ), $view->ID ) ) {
				return __( sprintf( 'This View is not configured properly. Start by <a href="%s">selecting a form</a>.', esc_url( get_edit_post_link( $view->ID, false ) ) ), 'gravityview' );
			}

			return $content;
		}

		/**
		 * Is this View directly accessible via a post URL?
		 *
		 * @see https://codex.wordpress.org/Function_Reference/register_post_type#public
		 */

		/**
		 * @filter `gravityview_direct_access` Should Views be directly accessible, or only visible using the shortcode?
		 * @deprecated
		 * @param[in,out] boolean `true`: allow Views to be accessible directly. `false`: Only allow Views to be embedded. Default: `true`
		 * @param int $view_id The ID of the View currently being requested. `0` for general setting
		 */
		$direct_access = apply_filters( 'gravityview_direct_access', true, $view->ID );

		/**
		 * @filter `gravityview/request/output/direct` Should this View be directly accessbile?
		 * @since future
		 * @param[in,out] boolean Accessible or not. Default: accessbile.
		 * @param \GV\View $view The View we're trying to directly render here.
		 * @param \GV\Request $request The current request.
		 */
		if ( ! apply_filters( 'gravityview/view/output/direct', $direct_access, $view, $request ) ) {
			return __( 'You are not allowed to view this content.', 'gravityview' );
		}

		/**
		 * Is this View an embed-only View? If so, don't allow rendering here,
		 *  as this is a direct request.
		 */
		if ( $view->settings->get( 'embed_only' ) && ! \GVCommon::has_cap( 'read_private_gravityviews' ) ) {
			return __( 'You are not allowed to view this content.', 'gravityview' );
		}

		/** Private, pending, draft, etc. */
		$public_states = get_post_stati( array( 'public' => true ) );
		if ( ! in_array( $view->post_status, $public_states ) && ! \GVCommon::has_cap( 'read_gravityview', $view->ID ) ) {
			gravityview()->log->notice( 'The current user cannot access this View #{view_id}', array( 'view_id' => $view->ID ) );
			return __( 'You are not allowed to view this content.', 'gravityview' );
		}

		/**
		 * Editing a single entry.
		 */
		if ( $entry = $request->is_edit_entry() ) {
			if ( $entry['status'] != 'active' ) {
				gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $entry->ID ) );
				return __( 'You are not allowed to view this content.', 'gravityview' );
			}

			if ( apply_filters( 'gravityview_custom_entry_slug', false ) && $entry->slug != get_query_var( \GV\Entry::get_endpoint_name() ) ) {
				gravityview()->log->error( 'Entry ID #{entry_id} was accessed by a bad slug', array( 'entry_id' => $entry->ID ) );
				return __( 'You are not allowed to view this content.', 'gravityview' );
			}

			if ( $view->settings->get( 'show_only_approved' ) ) {
				if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $entry->ID, \GravityView_Entry_Approval::meta_key ) )  ) {
					gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $entry->ID ) );
					return __( 'You are not allowed to view this content.', 'gravityview' );
				}
			}

			$renderer = new Edit_Entry_Renderer();
			return $renderer->render( $entry, $view, $request );

		/**
		 * Viewing a single entry.
		 */
		} else if ( $entry = $request->is_entry() ) {
			if ( $entry['status'] != 'active' ) {
				gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $entry->ID ) );
				return __( 'You are not allowed to view this content.', 'gravityview' );
			}

			if ( apply_filters( 'gravityview_custom_entry_slug', false ) && $entry->slug != get_query_var( \GV\Entry::get_endpoint_name() ) ) {
				gravityview()->log->error( 'Entry ID #{entry_id} was accessed by a bad slug', array( 'entry_id' => $entry->ID ) );
				return __( 'You are not allowed to view this content.', 'gravityview' );
			}

			if ( $view->settings->get( 'show_only_approved' ) ) {
				if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $entry->ID, \GravityView_Entry_Approval::meta_key ) )  ) {
					gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $entry->ID ) );
					return __( 'You are not allowed to view this content.', 'gravityview' );
				}
			}

			$renderer = new Entry_Renderer();
			return $renderer->render( $entry, $view, $request );

		/**
		 * Plain old View.
		 */
		} else {
			$renderer = new View_Renderer();
			return $renderer->render( $view, $request );
		}
		
		return $content;
	}


	/**
	 * Construct a \GV\View instance from a \WP_Post.
	 *
	 * @param \WP_Post $post The \WP_Post instance to wrap.
	 *
	 * @api
	 * @since future
	 * @return \GV\View|null An instance around this \WP_Post if valid, null otherwise.
	 */
	public static function from_post( $post ) {
		if ( ! $post || get_post_type( $post ) != 'gravityview' ) {
			gravityview()->log->error( 'Only gravityview post types can be \GV\View instances.' );
			return null;
		}

		$view = new self();
		$view->post = $post;

		/** Get connected form. */
		$view->form = GF_Form::by_id( $view->_gravityview_form_id );
		if ( ! $view->form ) {
			gravityview()->log->error( 'View #{view_id} tried attaching non-existent Form #{form_id} to it.', array(
				'view_id' => $view->ID,
				'form_id' => $view->_gravityview_form_id ? : 0,
			) );
		} else {
			/** And the connected joins. */
			foreach( (array)get_post_meta( $view->ID, '_gravityview_form_joins', true ) as $_join ) {
				if ( count( $_join ) != 4 ) {
					continue;
				}
				list( $join, $join_column, $join_on, $join_on_column ) = $_join;

				$join = GF_Form::by_id( $join );
				$join_on = GF_Form::by_id( $join_on );

				$join_column = is_numeric( $join_column ) ? GF_Field::by_id( $join, $join_column ) : Internal_Field( $join_column );
				$join_on_column = is_numeric( $join_on_column ) ? GF_Field::by_id( $join_on, $join_on_column ) : Internal_Field( $join_on_column );

				$view->joins []= new Join( $join, $join_column, $join_on, $join_on_column );
			}
		}

		/**
		 * @filter `gravityview/configuration/fields` Filter the View fields' configuration array.
		 * @since 1.6.5
		 *
		 * @param array $fields Multi-array of fields with first level being the field zones.
		 * @param int $view_id The View the fields are being pulled for.
		 */
		$configuration = apply_filters( 'gravityview/configuration/fields', (array)$view->_gravityview_directory_fields, $view->ID );

		/**
		 * @filter `gravityview/view/configuration/fields` Filter the View fields' configuration array.
		 * @since future
		 *
		 * @param array $fields Multi-array of fields with first level being the field zones.
		 * @param \GV\View $view The View the fields are being pulled for.
		 */
		$configuration = apply_filters( 'gravityview/view/fields/configuration', $configuration, $view );

		/**
		 * @filter `gravityview/view/fields` Filter the Field Collection for this View.
		 * @since future
		 *
		 * @param \GV\Field_Collection $fields A collection of fields.
		 * @param \GV\View $view The View the fields are being pulled for.
		 */
		$view->fields = apply_filters( 'gravityview/view/fields', Field_Collection::from_configuration( $configuration ), $view );

		/** View configuration. */
		$view->settings->update( gravityview_get_template_settings( $view->ID ) );

		/** Add the template name into the settings. */
		$view->settings->update( array( 'template' => gravityview_get_template_id( $view->ID ) ) );

		/** View basics. */
		$view->settings->update( array(
			'id' => $view->ID,
		) );

		/**
		 * @deprecated
		 *
		 * The data here has been moved to various keys in a \GV\View instance.
		 * As a compatibilty layer we allow array access over any \GV\View instance with these keys.
		 *
		 * This data is immutable (for now).
		 *
		 * @see \GV\View::offsetGet() for internal mappings.
		 */
		$view->_data = array(
			'widgets' => gravityview_get_directory_widgets( $view->ID ),
		);

		return $view;
	}

	/**
	 * Construct a \GV\View instance from a post ID.
	 *
	 * @param int|string $post_id The post ID.
	 *
	 * @api
	 * @since future
	 * @return \GV\View|null An instance around this \WP_Post or null if not found.
	 */
	public static function by_id( $post_id ) {
		if ( ! $post_id || ! $post = get_post( $post_id ) ) {
			return null;
		}
		return self::from_post( $post );
	}

	/**
	 * Determines if a view exists to begin with.
	 *
	 * @param int|\WP_Post|null $view_id The WordPress post ID, a \WP_Post object or null for global $post;
	 *
	 * @api
	 * @since future
	 * @return bool Whether the post exists or not.
	 */
	public static function exists( $view ) {
		return get_post_type( $view ) == 'gravityview';
	}

	/**
	 * ArrayAccess compatibility layer with GravityView_View_Data::$views
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 * @return bool Whether the offset exists or not, limited to GravityView_View_Data::$views element keys.
	 */
	public function offsetExists( $offset ) {
		$data_keys = array( 'id', 'view_id', 'form_id', 'template_id', 'atts', 'fields', 'widgets', 'form' );
		return in_array( $offset, $data_keys );
	}

	/**
	 * ArrayAccess compatibility layer with GravityView_View_Data::$views
	 *
	 * Maps the old keys to the new data;
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 *
	 * @return mixed The value of the requested view data key limited to GravityView_View_Data::$views element keys.
	 */
	public function offsetGet( $offset ) {
		
		gravityview()->log->notice( 'This is a \GV\View object should not be accessed as an array.' );

		if ( ! isset( $this[$offset] ) ) {
			return null;
		}

		switch ( $offset ) {
			case 'id':
			case 'view_id':
				return $this->ID;
			case 'form':
				return $this->form;
			case 'form_id':
				return $this->form ? $this->form->ID : null;
			case 'atts':
				return $this->settings->as_atts();
			case 'template_id':
				return $this->settings->get( 'template' );
			default:
				/** @todo move the rest out and get rid of _data completely! */
				return $this->_data[$offset];
		}
	}

	/**
	 * ArrayAccess compatibility layer with GravityView_View_Data::$views
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value ) {
		gravityview()->log->error( 'The old view data is no longer mutable. This is a \GV\View object should not be accessed as an array.' );
	}

	/**
	 * ArrayAccess compatibility layer with GravityView_View_Data::$views
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		gravityview()->log->error( 'The old view data is no longer mutable. This is a \GV\View object should not be accessed as an array.' );
	}

	/**
	 * Be compatible with the old data object.
	 *
	 * Some external code expects an array (doing things like foreach on this, or array_keys)
	 *  so let's return an array in the old format for such cases. Do not use unless using
	 *  for back-compatibility.
	 *
	 * @internal
	 * @deprecated
	 * @since future
	 * @return array
	 */
	public function as_data() {
		return array_merge(
			array( 'id' => $this->ID ),
			array( 'view_id' => $this->ID ),
			array( 'form_id' => $this->form ? $this->form->ID : null ),
			array( 'form' => $this->form ? gravityview_get_form( $this->form->ID ) : null ),
			array( 'atts' => $this->settings->as_atts() ),
			array( 'fields' => $this->fields->by_visible()->as_configuration() ),
			array( 'template_id' => $this->settings->get( 'template' ) ),
			$this->_data
		);
	}

	/** 
	 * Retrieve the entries for the current view and request.
	 *
	 * @param \GV\Request The request. Usued for now.
	 *
	 * @return \GV\Entry_Collection The entries.
	 */
	public function get_entries( $request ) {
		if ( ! $this->form ) {
			$entries = new \GV\Entry_Collection();
		} else {
			/**
			 * @todo: Stop using _frontend and use something like $request->get_search_criteria() instead
			 */
			$parameters = \GravityView_frontend::get_view_entries_parameters( $this->settings->as_atts(), $this->form->ID );

			/** @todo: Get the page from the request instead! */
			$page = ( ( $parameters['paging']['offset'] - $this->settings->get( 'offset' ) ) / $parameters['paging']['page_size'] ) + 1;

			if ( class_exists( '\GV\Query' ) ) {
				/**
				 * New \GV\Query stuff :)
				 */
				$query = new \GV\Query();
				$query->from( $this->form )
					->limit( $parameters['paging']['page_size'] )
					->page( $page );

				/**
				 * @todo Provide a search_criteria converter for this!
				 */
				if ( ! empty( $parameters['search_criteria']['field_filters'] ) ) {
					foreach( $parameters['search_criteria']['field_filters'] as $filter ) {
						$query->where(
								/**
								 * @todo Use the lightweight API.
								 */
								new Query\Condition(
										GF_Field::by_id( $this->form, $filter['key'] ),
										Query\Condition::EQ,
										new Query\Literal( $filter['value'] )
								)
						);
					}
				}

				/**
				 * The joins!
				 */
				if ( count( $this->joins ) ) {
					foreach ( $this->joins as $join ) {
							$query = $join->as_query_join( $query );
					}
				}

				$entries = $query->get();
			} else {
				$entries = $this->form->entries
					->filter( \GV\GF_Entry_Filter::from_search_criteria( $parameters['search_criteria'] ) )
					->offset( $this->settings->get( 'offset' ) )
					->limit( $parameters['paging']['page_size'] )
					->page( $page );
				if ( ! empty( $parameters['sorting'] ) ) {
					$field = new \GV\Field();
					$field->ID = $parameters['sorting']['key'];
					$direction = strtolower( $parameters['sorting']['direction'] ) == 'asc' ? \GV\Entry_Sort::ASC : \GV\Entry_Sort::DESC;
					$entries = $entries->sort( new \GV\Entry_Sort( $field, $direction ) );
				}
			}
		}

		/**
		 * @filter `gravityview/view/entries` Modify the entry fetching filters, sorts, offsets, limits.
		 * @param \GV\Entry_Collection $entries The entries for this view.
		 * @param \GV\View $view The view.
		 * @param \GV\Request $request The request.
		 */
		return apply_filters( 'gravityview/view/entries', $entries, $this, $request );
	}

	public function __get( $key ) {

		if ( $this->post ) {
			$post = $this->post->filter('raw');
			return $post->{$key};
		}

		return isset( $this->{$key} ) ? $this->{$key} : null;
	}
}
