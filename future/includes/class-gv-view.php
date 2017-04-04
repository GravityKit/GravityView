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
	 * @var \GV\View_Template The template attached to this view.
	 */
	public $template;

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
	 * Construct a \GV\View instance from a \WP_Post.
	 *
	 * @param $post The \WP_Post instance to wrap.
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
		}

		/**
		* @filter `gravityview/configuration/fields` Filter the View fields' configuration array
		* @since 1.6.5
		*
		* @param $fields array Multi-array of fields with first level being the field zones
		* @param $view_id int The View the fields are being pulled for
		*/
		$configuration = apply_filters( 'gravityview/configuration/fields', (array)$view->_gravityview_directory_fields, $view->ID );

		/** Get all fields. */
		$view->fields = Field_Collection::from_configuration( $configuration );

		/** The settings. */
		$view->settings->update( gravityview_get_template_settings( $view->ID ) );

		/** Set the template. */
		$view->template = new \GV\View_Template( $view->_gravityview_directory_template );

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
			/**
			 * @deprecated
			 * @see \GV\View::$ID
			 */
			// 'id' => $view->ID,

			/**
			 * @deprecated
			 * @see \GV\View::$ID
			 */
			// 'view_id' => $view->ID,

			/**
			 * @deprecated
			 * @see \GV\View::$form
			 */
			// 'form' => gravityview_get_form( $view->_gravityview_form_id ),

			/**
			 * @deprecated
			 * @see \GV\View::$form::$ID
			 */
			// 'form_id' => $view->_gravityview_form_id,

			/**
			 * @deprecated
			 * @see \GV\View::$settings
			 */
			// 'atts' => $view->settings->as_atts(),

			/**
			 * @deprecated
			 * @see \GV\View::$fields
			 */
			// 'fields' => \GravityView_View_Data::getInstance()->get_fields( $view->ID ),

			/**
			 * @deprecated
			 * @see \GV\View::$template::$ID
			 */
			// 'template_id' => gravityview_get_template_id( $view->ID ),

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
				return $this->as_atts();
			case 'template_id':
				return $this->template ? $this->template->ID : null;
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
			array( 'template_id' => $this->template? $this->template->ID : null ),
			$this->_data
		);
	}

	public function __get( $key ) {
		return $this->post->$key;
	}
}
