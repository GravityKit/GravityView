<?php

namespace GV;

use GF_Query_Column;
use GF_Query_Condition;
use GF_Query_Literal;
use GravityKit\GravityView\Foundation\Helpers\Arr;
use GF_Query;
use GravityKitFoundation;
use GravityView_Compatibility;
use GravityView_Cache;
use GravityView_frontend;
use GVCommon;

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
	 * @var string GravityView custom post type.
	 * */
	const POST_TYPE = 'gravityview';

	/**
	 * @var \WP_Post The backing post instance.
	 */
	private $post;

	/**
	 * @var \GV\View_Settings The settings.
	 *
	 * @api
	 * @since 2.0
	 */
	public $settings;

	/**
	 * @var \GV\Widget_Collection The widgets attached here.
	 *
	 * @api
	 * @since 2.0
	 */
	public $widgets;

	/**
	 * @var \GV\GF_Form|\GV\Form The backing form for this view.
	 *
	 * Contains the form that is sourced for entries in this view.
	 *
	 * @api
	 * @since 2.0
	 */
	public $form;

	/**
	 * @var \GV\Field_Collection The fields for this view.
	 *
	 * Contains all the fields that are attached to this view.
	 *
	 * @api
	 * @since 2.0
	 */
	public $fields;

	/**
	 * @var string A unique anchor ID used to wrap Views.
	 *
	 * @see View_Renderer::render() Dynamically set in hooks here.
	 *
	 * @since 2.15
	 */
	private $anchor_id;

	/**
	 * @var array
	 *
	 * Internal static cache for gets, and whatnot.
	 * This is not persistent, resets across requests.

	 * @internal
	 */
	private static $cache = array();

	/**
	 * @var \GV\Join[] The joins for all sources in this view.
	 *
	 * @api
	 * @since 2.0.1
	 */
	public $joins = array();

	/**
	 * @var \GV\Field[][] The unions for all sources in this view.
	 *                    An array of fields grouped by form_id keyed by
	 *                    main field_id:
	 *
	 *                    array(
	 *                        $form_id => array(
	 *                            $field_id => $field,
	 *                            $field_id => $field,
	 *                        )
	 *                    )
	 *
	 * @api
	 * @since 2.2.2
	 */
	public $unions = array();

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->settings = new View_Settings();
		$this->fields   = new Field_Collection();
		$this->widgets  = new Widget_Collection();
	}

	/**
	 * Register the gravityview WordPress Custom Post Type.
	 *
	 * @internal
	 * @return void
	 */
	public static function register_post_type() {
		/** Register only once */
		if ( post_type_exists( self::POST_TYPE ) ) {
			return;
		}

		if ( ! gravityview()->plugin->is_compatible() ) {
			GravityView_Compatibility::override_post_pages_when_compatibility_fails();
		}

		/**
		 * Make GravityView Views hierarchical by returning TRUE.
		 * This will allow for Views to be nested with Parents and also allows for menu order to be set in the Page Attributes metabox
		 *
		 * @since 1.13
		 * @param boolean $is_hierarchical Default: false
		 */
		$is_hierarchical = (bool) apply_filters( 'gravityview_is_hierarchical', false );

		$supports = array( 'title', 'revisions' );

		if ( $is_hierarchical ) {
			$supports[] = 'page-attributes';
		}

		/**
		 * @filter  `gravityview_post_type_supports` Modify post type support values for `gravityview` post type
		 * @see add_post_type_support()
		 * @since 1.15.2
		 * @param array $supports Array of features associated with a functional area of the edit screen. Default: 'title', 'revisions'. If $is_hierarchical, also 'page-attributes'
		 * @param boolean $is_hierarchical Do Views support parent/child relationships? See `gravityview_is_hierarchical` filter.
		 */
		$supports = apply_filters( 'gravityview_post_type_support', $supports, $is_hierarchical );

		/** Register Custom Post Type - gravityview */
		$labels = array(
			'name'                   => _x( 'Views', 'Post Type General Name', 'gk-gravityview' ),
			'singular_name'          => _x( 'View', 'Post Type Singular Name', 'gk-gravityview' ),
			'menu_name'              => _x( 'Views', 'Menu name', 'gk-gravityview' ),
			'parent_item_colon'      => __( 'Parent View:', 'gk-gravityview' ),
			'all_items'              => __( 'All Views', 'gk-gravityview' ),
			'view_item'              => _x( 'View', 'View Item', 'gk-gravityview' ),
			'add_new_item'           => __( 'Add New View', 'gk-gravityview' ),
			'add_new'                => __( 'New View', 'gk-gravityview' ),
			'edit_item'              => __( 'Edit View', 'gk-gravityview' ),
			'update_item'            => __( 'Update View', 'gk-gravityview' ),
			'search_items'           => __( 'Search Views', 'gk-gravityview' ),
			'not_found'              => \GravityView_Admin::no_views_text(),
			'not_found_in_trash'     => __( 'No Views found in Trash', 'gk-gravityview' ),
			'filter_items_list'      => __( 'Filter Views list', 'gk-gravityview' ),
			'items_list_navigation'  => __( 'Views list navigation', 'gk-gravityview' ),
			'items_list'             => __( 'Views list', 'gk-gravityview' ),
			'view_items'             => __( 'See Views', 'gk-gravityview' ),
			'attributes'             => __( 'View Attributes', 'gk-gravityview' ),
			'item_updated'           => __( 'View updated.', 'gk-gravityview' ),
			'item_published'         => __( 'View published.', 'gk-gravityview' ),
			'item_reverted_to_draft' => __( 'View reverted to draft.', 'gk-gravityview' ),
			'item_scheduled'         => __( 'View scheduled.', 'gk-gravityview' ),
		);

		$args = array(
			'label'               => __( 'view', 'gk-gravityview' ),
			'description'         => __( 'Create views based on a Gravity Forms form', 'gk-gravityview' ),
			'labels'              => $labels,
			'supports'            => $supports,
			'hierarchical'        => $is_hierarchical,
			/**
			 * Should Views be directly accessible, or only visible using the shortcode?
			 *
			 * @see https://codex.wordpress.org/Function_Reference/register_post_type#public
			 * @since 1.15.2
			 * @param boolean `true`: allow Views to be accessible directly. `false`: Only allow Views to be embedded via shortcode. Default: `true`
			 * @param int $view_id The ID of the View currently being requested. `0` for general setting
			 */
			'public'              => apply_filters( 'gravityview_direct_access', gravityview()->plugin->is_compatible(), 0 ),
			'show_ui'             => true,
			'show_in_menu'        => false, // Menu items are added in \GV\Plugin::add_to_gravitykit_admin_menu()
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 17,
			'menu_icon'           => '',
			'can_export'          => true,
			/**
			 * Enable Custom Post Type archive?
			 *
			 * @since 1.7.3
			 * @param boolean False: don't have frontend archive; True: yes, have archive. Default: false
			 */
			'has_archive'         => apply_filters( 'gravityview_has_archive', false ),
			'exclude_from_search' => true,
			'rewrite'             => array(
				/**
				 * Modify the url part for a View.
				 *
				 * @see https://docs.gravitykit.com/article/62-changing-the-view-slug
				 * @param string $slug The slug shown in the URL
				 */
				'slug'       => apply_filters( 'gravityview_slug', 'view' ),

				/**
				 * Should the permalink structure.
				 *  be prepended with the front base.
				 *  (example: if your permalink structure is /blog/, then your links will be: false->/view/, true->/blog/view/).
				 *  Defaults to true.
				 *
				 * @see https://codex.wordpress.org/Function_Reference/register_post_type
				 * @since 2.0
				 * @param bool $with_front
				 */
				'with_front' => apply_filters( 'gravityview/post_type/with_front', true ),
			),
			'capability_type'     => 'gravityview',
			'map_meta_cap'        => true,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Add extra rewrite endpoints.
	 *
	 * @return void
	 */
	public static function add_rewrite_endpoint() {
		/**
		 * CSV.
		 */
		global $wp_rewrite;

		$slug     = apply_filters( 'gravityview_slug', 'view' );
		$slug     = ( '/' !== $wp_rewrite->front ) ? sprintf( '%s/%s', trim( $wp_rewrite->front, '/' ), $slug ) : $slug;
		$csv_rule = array( sprintf( '%s/([^/]+)/csv/?', $slug ), 'index.php?gravityview=$matches[1]&csv=1', 'top' );
		$tsv_rule = array( sprintf( '%s/([^/]+)/tsv/?', $slug ), 'index.php?gravityview=$matches[1]&tsv=1', 'top' );

		add_filter(
			'query_vars',
			function ( $query_vars ) {
				$query_vars[] = 'csv';
				$query_vars[] = 'tsv';
				return $query_vars;
			}
		);

		if ( ! isset( $wp_rewrite->extra_rules_top[ $csv_rule[0] ] ) ) {
			call_user_func_array( 'add_rewrite_rule', $csv_rule );
			call_user_func_array( 'add_rewrite_rule', $tsv_rule );
		}
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

		// Plugins may run through the content in the header. WP SEO does this for its OpenGraph functionality.
		if ( ! defined( 'DOING_GRAVITYVIEW_TESTS' ) ) {
			if ( ! did_action( 'loop_start' ) ) {
				gravityview()->log->debug( 'Not processing yet: loop_start hasn\'t run yet. Current action: {action}', array( 'action' => current_filter() ) );
				return $content;
			}

			// We don't want this filter to run infinite loop on any post content fields
			remove_filter( 'the_content', array( __CLASS__, __METHOD__ ) );
		}

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
		 * Check permissions.
		 */
		while ( $error = $view->can_render( null, $request ) ) {
			if ( ! is_wp_error( $error ) ) {
				break;
			}

			switch ( str_replace( 'gravityview/', '', $error->get_error_code() ) ) {
				case 'post_password_required':
					return get_the_password_form( $view->ID );
				case 'no_form_attached':
					gravityview()->log->error(
						'View #{view_id} cannot render: {error_code} {error_message}',
						array(
							'error_code'    => $error->get_error_code(),
							'error_message' => $error->get_error_message(),
						)
					);

					/**
					 * This View has no data source. There's nothing to show really.
					 * ...apart from a nice message if the user can do anything about it.
					 */
					if ( GVCommon::has_cap( array( 'edit_gravityviews', 'edit_gravityview' ), $view->ID ) ) {

						$title = sprintf( __( 'This View is not configured properly. Start by <a href="%s">selecting a form</a>.', 'gk-gravityview' ), esc_url( get_edit_post_link( $view->ID, false ) ) );

						$message = esc_html__( 'You can only see this message because you are able to edit this View.', 'gk-gravityview' );

						$image = sprintf( '<img alt="%s" src="%s" style="margin-top: 10px;" />', esc_attr__( 'Data Source', 'gk-gravityview' ), esc_url( plugins_url( 'assets/images/screenshots/data-source.png', GRAVITYVIEW_FILE ) ) );

						return GVCommon::generate_notice( '<h3>' . $title . '</h3>' . wpautop( $message . $image ), 'notice' );
					}
					break;
				case 'in_trash':
					return '';  // Views in trash are unreachable when accessed as a CPT, but adding this just in case. We do not give a hint that this content exists, for security purposes.
				case 'no_direct_access':
				case 'embed_only':
				case 'not_public':
				default:
					gravityview()->log->notice(
						'View #{view_id} cannot render: {error_code} {error_message}',
						array(
							'error_code'    => $error->get_error_code(),
							'error_message' => $error->get_error_message(),
						)
					);
					return __( 'You are not allowed to view this content.', 'gk-gravityview' );
			}

			return $content;
		}

		$is_admin_and_can_view = $view->settings->get( 'admin_show_all_statuses' ) && GVCommon::has_cap( 'gravityview_moderate_entries', $view->ID );

		/**
		 * Editing a single entry.
		 */
		if ( $entry = $request->is_edit_entry( $view->form ? $view->form->ID : 0 ) ) {
			if ( 'active' != $entry['status'] ) {
				gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $entry->ID ) );
				return __( 'You are not allowed to view this content.', 'gk-gravityview' );
			}

			if ( apply_filters( 'gravityview_custom_entry_slug', false ) && $entry->slug != get_query_var( \GV\Entry::get_endpoint_name() ) ) {
				gravityview()->log->error( 'Entry ID #{entry_id} was accessed by a bad slug', array( 'entry_id' => $entry->ID ) );
				return __( 'You are not allowed to view this content.', 'gk-gravityview' );
			}

			if ( $view->settings->get( 'show_only_approved' ) && ! $is_admin_and_can_view ) {
				if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $entry->ID, \GravityView_Entry_Approval::meta_key ) ) ) {
					gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $entry->ID ) );
					return __( 'You are not allowed to view this content.', 'gk-gravityview' );
				}
			}

			$renderer = new Edit_Entry_Renderer();
			return $renderer->render( $entry, $view, $request );

			/**
			 * Viewing a single entry.
			 */
		} elseif ( $entry = $request->is_entry( $view->form ? $view->form->ID : 0 ) ) {

			$entryset = $entry->is_multi() ? $entry->entries : array( $entry );

			$custom_slug = apply_filters( 'gravityview_custom_entry_slug', false );
			$ids         = explode( ',', get_query_var( \GV\Entry::get_endpoint_name() ) );

			$show_only_approved = $view->settings->get( 'show_only_approved' );

			foreach ( $entryset as $e ) {

				if ( 'active' !== $e['status'] ) {
					gravityview()->log->notice( 'Entry ID #{entry_id} is not active', array( 'entry_id' => $e->ID ) );
					return __( 'You are not allowed to view this content.', 'gk-gravityview' );
				}

				if ( $custom_slug && ! in_array( $e->slug, $ids ) ) {
					gravityview()->log->error( 'Entry ID #{entry_id} was accessed by a bad slug', array( 'entry_id' => $e->ID ) );
					return __( 'You are not allowed to view this content.', 'gk-gravityview' );
				}

				if ( $show_only_approved && ! $is_admin_and_can_view ) {
					if ( ! \GravityView_Entry_Approval_Status::is_approved( gform_get_meta( $e->ID, \GravityView_Entry_Approval::meta_key ) ) ) {
						gravityview()->log->error( 'Entry ID #{entry_id} is not approved for viewing', array( 'entry_id' => $e->ID ) );
						return __( 'You are not allowed to view this content.', 'gk-gravityview' );
					}
				}

				$error = GVCommon::check_entry_display( $e->as_entry(), $view );

				if ( is_wp_error( $error ) ) {
					gravityview()->log->error(
						'Entry ID #{entry_id} is not approved for viewing: {message}',
						array(
							'entry_id' => $e->ID,
							'message'  => $error->get_error_message(),
						)
					);
					return __( 'You are not allowed to view this content.', 'gk-gravityview' );
				}
			}

			$renderer = new Entry_Renderer();
			return $renderer->render( $entry, $view, $request );
		}

		/**
		 * Plain old View.
		 */
		$renderer = new View_Renderer();
		return $renderer->render( $view, $request );
	}

	/**
	 * Checks whether this view can be accessed or not.
	 *
	 * @param string[]    $context The context we're asking for access from.
	 *                             Can any and as many of one of:
	 *                                 edit      An edit context.
	 *                                 single    A single context.
	 *                                 cpt       The custom post type single page accessed.
	 *                                 shortcode Embedded as a shortcode.
	 *                                 oembed    Embedded as an oEmbed.
	 *                                 rest      A REST call.
	 * @param \GV\Request $request The request
	 *
	 * @return bool|\WP_Error An error if this View shouldn't be rendered here.
	 */
	public function can_render( $context = null, $request = null ) {
		if ( ! $request ) {
			$request = gravityview()->request;
		}

		if ( ! is_array( $context ) ) {
			$context = array();
		}

		/**
		 * Whether the view can be rendered or not.
		 *
		 * @param bool|\WP_Error $result  The result. Default: null.
		 * @param \GV\View       $view  The view.
		 * @param string[]       $context See \GV\View::can_render
		 * @param \GV\Request    $request The request.
		 */
		if ( ! is_null( $result = apply_filters( 'gravityview/view/can_render', null, $this, $context, $request ) ) ) {
			return $result;
		}

		if ( in_array( 'rest', $context ) ) {
			// REST
			if ( gravityview()->plugin->settings->get( 'rest_api' ) && '1' === $this->settings->get( 'rest_disable' ) ) {
				return new \WP_Error( 'gravityview/rest_disabled' );
			} elseif ( ! gravityview()->plugin->settings->get( 'rest_api' ) && '1' !== $this->settings->get( 'rest_enable' ) ) {
				return new \WP_Error( 'gravityview/rest_disabled' );
			}
		}

		if ( in_array( 'csv', $context ) ) {
			if ( '1' !== $this->settings->get( 'csv_enable' ) ) {
				return new \WP_Error( 'gravityview/csv_disabled', 'The CSV endpoint is not enabled for this View' );
			}
		}

		/**
		 * This View is password protected. Nothing to do here.
		 */
		if ( post_password_required( $this->ID ) ) {
			gravityview()->log->notice( 'Post password is required for View #{view_id}', array( 'view_id' => $this->ID ) );
			return new \WP_Error( 'gravityview/post_password_required' );
		}

		if ( ! $this->form ) {
			gravityview()->log->notice( 'View #{id} has no form attached to it.', array( 'id' => $this->ID ) );
			return new \WP_Error( 'gravityview/no_form_attached' );
		}

		if ( ! in_array( 'shortcode', $context ) ) {
			/**
			 * Is this View directly accessible via a post URL?
			 *
			 * @see https://codex.wordpress.org/Function_Reference/register_post_type#public
			 */

			/**
			 * Should Views be directly accessible, or only visible using the shortcode?
			 *
			 * @deprecated
			 * @param boolean `true`: allow Views to be accessible directly. `false`: Only allow Views to be embedded. Default: `true`
			 * @param int $view_id The ID of the View currently being requested. `0` for general setting
			 */
			$direct_access = apply_filters( 'gravityview_direct_access', true, $this->ID );

			/**
			 * Should this View be directly accessbile?
			 *
			 * @since 2.0
			 * @param boolean Accessible or not. Default: accessbile.
			 * @param \GV\View $view The View we're trying to directly render here.
			 * @param \GV\Request $request The current request.
			 */
			if ( ! apply_filters( 'gravityview/view/output/direct', $direct_access, $this, $request ) ) {
				return new \WP_Error( 'gravityview/no_direct_access' );
			}

			/**
			 * Is this View an embed-only View? If so, don't allow rendering here,
			 *  as this is a direct request.
			 */
			if ( $this->settings->get( 'embed_only' ) && ! GVCommon::has_cap( 'read_private_gravityviews' ) ) {
				return new \WP_Error( 'gravityview/embed_only' );
			}
		}

		if ( 'trash' === get_post_status( $this->ID ) ) {
			return new \WP_Error( 'gravityview/in_trash' );
		}

		/** Private, pending, draft, etc. */
		$public_states = get_post_stati( array( 'public' => true ) );
		if ( ! in_array( $this->post_status, $public_states, true ) && ! GVCommon::has_cap( 'read_gravityview', $this->ID ) ) {
			gravityview()->log->notice( 'The current user cannot access this View #{view_id}', array( 'view_id' => $this->ID ) );
			return new \WP_Error( 'gravityview/not_public' );
		}

		return true;
	}

	/**
	 * Get joins associated with a view
	 *
	 * @param \WP_Post $post GravityView CPT to get joins for
	 *
	 * @api
	 * @since 2.0.11
	 *
	 * @return \GV\Join[] Array of \GV\Join instances
	 */
	public static function get_joins( $post ) {
		$joins = array();

		if ( ! gravityview()->plugin->supports( Plugin::FEATURE_JOINS ) ) {
			return $joins;
		}

		if ( ! $post || self::POST_TYPE !== get_post_type( $post ) ) {
			gravityview()->log->error( 'Only "gravityview" post types can be \GV\View instances.' );
			return $joins;
		}

		$joins_meta = get_post_meta( $post->ID, '_gravityview_form_joins', true );

		if ( empty( $joins_meta ) ) {
			return $joins;
		}

		foreach ( $joins_meta as $meta ) {
			if ( ! is_array( $meta ) || 4 != count( $meta ) ) {
				continue;
			}

			[ $join, $join_column, $join_on, $join_on_column ] = $meta;

			$join    = GF_Form::by_id( $join );
			$join_on = GF_Form::by_id( $join_on );

			$join_column    = is_numeric( $join_column ) ? GF_Field::by_id( $join, $join_column ) : Internal_Field::by_id( $join_column );
			$join_on_column = is_numeric( $join_on_column ) ? GF_Field::by_id( $join_on, $join_on_column ) : Internal_Field::by_id( $join_on_column );

			$joins [] = new Join( $join, $join_column, $join_on, $join_on_column );
		}

		return $joins;
	}

	/**
	 * Get joined forms associated with a view
	 * In no particular order.
	 *
	 * @since 2.0.11
	 *
	 * @api
	 * @since 2.0
	 * @param int $post_id ID of the View
	 *
	 * @return \GV\GF_Form[] Array of \GV\GF_Form instances
	 */
	public static function get_joined_forms( $post_id ) {
		$forms = array();

		if ( ! gravityview()->plugin->supports( Plugin::FEATURE_JOINS ) ) {
			return $forms;
		}

		if ( ! $post_id || ! gravityview()->plugin->supports( Plugin::FEATURE_JOINS ) ) {
			return $forms;
		}

		if ( empty( $post_id ) ) {
			gravityview()->log->error( 'Cannot get joined forms; $post_id was empty' );
			return $forms;
		}

		$joins_meta = get_post_meta( $post_id, '_gravityview_form_joins', true );

		if ( empty( $joins_meta ) ) {
			return $forms;
		}

		foreach ( $joins_meta  as $meta ) {
			if ( ! is_array( $meta ) || 4 != count( $meta ) ) {
				continue;
			}

			[ $join, $join_column, $join_on, $join_on_column ] = $meta;

			if ( $form = GF_Form::by_id( $join_on ) ) {
				$forms[ $join_on ] = $form;
			}

			if ( $form = GF_Form::by_id( $join ) ) {
				$forms[ $join ] = $form;
			}
		}

		return $forms;
	}

	/**
	 * Get unions associated with a view
	 *
	 * @param \WP_Post $post GravityView CPT to get unions for
	 *
	 * @api
	 * @since 2.2.2
	 *
	 * @return \GV\Field[][] Array of unions (see self::$unions)
	 */
	public static function get_unions( $post ) {
		$unions = array();

		if ( ! $post || self::POST_TYPE !== get_post_type( $post ) ) {
			gravityview()->log->error( 'Only "gravityview" post types can be \GV\View instances.' );
			return $unions;
		}

		$fields = get_post_meta( $post->ID, '_gravityview_directory_fields', true );

		if ( empty( $fields ) ) {
			return $unions;
		}

		foreach ( $fields as $location => $_fields ) {
			if ( 0 !== strpos( $location, 'directory_' ) ) {
				continue;
			}

			foreach ( $_fields as $field ) {
				if ( ! empty( $field['unions'] ) ) {
					foreach ( $field['unions'] as $form_id => $field_id ) {
						if ( ! isset( $unions[ $form_id ] ) ) {
							$unions[ $form_id ] = array();
						}

						$unions[ $form_id ][ $field['id'] ] =
							is_numeric( $field_id ) ? \GV\GF_Field::by_id( \GV\GF_Form::by_id( $form_id ), $field_id ) : \GV\Internal_Field::by_id( $field_id );
					}
				}
			}

			break;
		}

		if ( $unions ) {
			if ( ! gravityview()->plugin->supports( Plugin::FEATURE_UNIONS ) ) {
				gravityview()->log->error( 'Cannot get unions; unions feature not supported.' );
			}
		}

		// @todo We'll probably need to backfill null unions

		return $unions;
	}

	/**
	 * Construct a \GV\View instance from a \WP_Post.
	 *
	 * @param \WP_Post $post The \WP_Post instance to wrap.
	 *
	 * @api
	 * @since 2.0
	 * @return \GV\View|null An instance around this \WP_Post if valid, null otherwise.
	 */
	public static function from_post( $post ) {

		if ( ! $post || self::POST_TYPE !== get_post_type( $post ) ) {
			gravityview()->log->error( 'Only gravityview post types can be \GV\View instances.' );
			return null;
		}

		if ( $view = Utils::get( self::$cache, "View::from_post:{$post->ID}" ) ) {
			/**
			 * Override View.
			 *
			 * @param \GV\View $view The View instance pointer.
			 * @since 2.1
			 */
			do_action_ref_array( 'gravityview/view/get', array( &$view ) );

			return $view;
		}

		$view       = new self();
		$view->post = $post;

		/** Get connected form. */
		$view->form = GF_Form::by_id( $view->_gravityview_form_id );
		global $pagenow;
		if ( ! $view->form && 'post-new.php' !== $pagenow ) {
			gravityview()->log->error(
				'View #{view_id} tried attaching non-existent Form #{form_id} to it.',
				array(
					'view_id' => $view->ID,
					'form_id' => $view->_gravityview_form_id ? : 0,
				)
			);
		}

		$view->joins = $view::get_joins( $post );

		$view->unions = $view::get_unions( $post );

		/**
		 * Filter the View fields' configuration array.
		 *
		 * @since 1.6.5
		 *
		 * @deprecated Use `gravityview/view/configuration/fields` or `gravityview/view/fields` filters.
		 *
		 * @param $fields array Multi-array of fields with first level being the field zones.
		 * @param $view_id int The View the fields are being pulled for.
		 */
		$configuration = apply_filters( 'gravityview/configuration/fields', (array) $view->_gravityview_directory_fields, $view->ID );

		/**
		 * Filter the View fields' configuration array.
		 *
		 * @since 2.0
		 *
		 * @param array $fields Multi-array of fields with first level being the field zones.
		 * @param \GV\View $view The View the fields are being pulled for.
		 */
		$configuration = apply_filters( 'gravityview/view/configuration/fields', $configuration, $view );

		/**
		 * Filter the Field Collection for this View.
		 *
		 * @since 2.0
		 *
		 * @param \GV\Field_Collection $fields A collection of fields.
		 * @param \GV\View $view The View the fields are being pulled for.
		 */
		$view->fields = apply_filters( 'gravityview/view/fields', Field_Collection::from_configuration( $configuration ), $view );

		/**
		 * Filter the View widgets' configuration array.
		 *
		 * @since 2.0
		 *
		 * @param array $fields Multi-array of widgets with first level being the field zones.
		 * @param \GV\View $view The View the widgets are being pulled for.
		 */
		$configuration = apply_filters( 'gravityview/view/configuration/widgets', (array) $view->_gravityview_directory_widgets, $view );

		/**
		 * Filter the Widget Collection for this View.
		 *
		 * @since 2.0
		 *
		 * @param \GV\Widget_Collection $widgets A collection of widgets.
		 * @param \GV\View $view The View the widgets are being pulled for.
		 */
		$view->widgets = apply_filters( 'gravityview/view/widgets', Widget_Collection::from_configuration( $configuration ), $view );

		/** View configuration. */
		$view->settings->update( gravityview_get_template_settings( $view->ID ) );

		/** Add the template names into the settings. */
		$view->settings->update( array( 'template' => gravityview_get_directory_entries_template_id( $view->ID ) ) );
		$view->settings->update( array( 'template_single_entry' => gravityview_get_single_entry_template_id( $view->ID ) ) );

		/** View basics. */
		$view->settings->update(
			array(
				'id' => $view->ID,
			)
		);

		self::$cache[ "View::from_post:{$post->ID}" ] = &$view;

		/**
		 * Override View.
		 *
		 * @param \GV\View $view The View instance pointer.
		 * @since 2.1
		 */
		do_action_ref_array( 'gravityview/view/get', array( &$view ) );

		return $view;
	}

	/**
	 * Flush the view cache.
	 *
	 * @param int $view_id The View to reset cache for. Optional. Default: resets everything.
	 *
	 * @internal
	 */
	public static function _flush_cache( $view_id = null ) {
		if ( $view_id ) {
			unset( self::$cache[ "View::from_post:$view_id" ] );
			return;
		}
		self::$cache = array();
	}

	/**
	 * Construct a \GV\View instance from a post ID.
	 *
	 * @param int|string $post_id The post ID.
	 *
	 * @api
	 * @since 2.0
	 * @return \GV\View|null An instance around this \WP_Post or null if not found.
	 */
	public static function by_id( $post_id ) {
		if ( ! $post_id || ! $post = get_post( $post_id ) ) {
			return null;
		}
		return self::from_post( $post );
	}

	/**
	 * Gets the source of the field.
	 *
	 * @since 2.33
	 *
	 * @param Field $field The field.
	 * @param View  $view The view.
	 *
	 * @return GF_Form|Internal_Source
	 */
	public static function get_source( $field, $view ) {
		if ( ! is_numeric( $field->ID ) ) {
			return new Internal_Source();
		}

		$form_id = $field->field->formId ?? null;

		// If the field's form differs from the main view form, get the form from the joined entries.
		if ( $form_id && $view->form->ID != $form_id && ! empty( $view->joins ) ) {
			foreach ( $view->joins as $join ) {
				if ( isset( $join->join_on->ID ) && $join->join_on->ID == $form_id ) {
					return $join->join_on;
				}
			}

			// Edge case where the form cannot be retrieved from the joins.
			return GF_Form::by_id( $form_id );
		}

		// Return the main view form.
		return $view->form;
	}

	/**
	 * Determines if a view exists to begin with.
	 *
	 * @param int|\WP_Post|null $view The WordPress post ID, a \WP_Post object or null for global $post;
	 *
	 * @api
	 * @since 2.0
	 * @return bool Whether the post exists or not.
	 */
	public static function exists( $view ) {
		return self::POST_TYPE == get_post_type( $view );
	}

	/**
	 * ArrayAccess compatibility layer with GravityView_View_Data::$views
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 * @return bool Whether the offset exists or not, limited to GravityView_View_Data::$views element keys.
	 */
	#[\ReturnTypeWillChange]
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
	 * @since 2.0
	 *
	 * @return mixed The value of the requested view data key limited to GravityView_View_Data::$views element keys. If offset not found, return null.
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {

		gravityview()->log->notice( 'This is a \GV\View object should not be accessed as an array.' );

		if ( ! isset( $this[ $offset ] ) ) {
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
			case 'widgets':
				return $this->widgets->as_configuration();
		}

		return null;
	}

	/**
	 * ArrayAccess compatibility layer with GravityView_View_Data::$views
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		gravityview()->log->error( 'The old view data is no longer mutable. This is a \GV\View object should not be accessed as an array.' );
	}

	/**
	 * ArrayAccess compatibility layer with GravityView_View_Data::$views
	 *
	 * @internal
	 * @deprecated
	 * @since 2.0
	 * @return void
	 */
	#[\ReturnTypeWillChange]
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
	 * @since 2.0
	 * @return array
	 */
	public function as_data() {
		return array(
			'id'          => $this->ID,
			'view_id'     => $this->ID,
			'form_id'     => $this->form ? $this->form->ID : null,
			'form'        => $this->form ? gravityview_get_form( $this->form->ID ) : null,
			'atts'        => $this->settings->as_atts(),
			'fields'      => $this->fields->by_visible( $this )->as_configuration(),
			'template_id' => $this->settings->get( 'template' ),
			'widgets'     => $this->widgets->as_configuration(),
		);
	}

	/**
	 * Retrieve the entries for the current view and request.
	 *
	 * @param \GV\Request The request. Unused for now.
	 *
	 * @return \GV\Entry_Collection The entries.
	 */
	public function get_entries( $request = null ) {
		$entries = new \GV\Entry_Collection();

		if ( ! $this->form ) {
			// Documented below.
			return apply_filters( 'gravityview/view/entries', $entries, $this, $request );
		}

		$parameters = $this->settings->as_atts();

		/**
		 * Remove multiple sorting before calling legacy filters.
		 * This allows us to fake it till we make it.
		 */
		if ( ! empty( $parameters['sort_field'] ) && is_array( $parameters['sort_field'] ) ) {
			$has_multisort            = true;
			$parameters['sort_field'] = reset( $parameters['sort_field'] );
			if ( ! empty( $parameters['sort_direction'] ) && is_array( $parameters['sort_direction'] ) ) {
				$parameters['sort_direction'] = reset( $parameters['sort_direction'] );
			}
		}

		/**
		 * @todo: Stop using _frontend and use something like $request->get_search_criteria() instead
		 */
		$parameters = GravityView_frontend::get_view_entries_parameters( $parameters, $this->form->ID );

		$parameters['context_view_id'] = $this->ID;
		$parameters                    = GVCommon::calculate_get_entries_criteria( $parameters, $this->form->ID );

		if ( ! is_array( $parameters ) ) {
			$parameters = array();
		}

		if ( ! is_array( $parameters['search_criteria'] ) ) {
			$parameters['search_criteria'] = array();
		}

		if ( ( ! isset( $parameters['search_criteria']['field_filters'] ) ) || ( ! is_array( $parameters['search_criteria']['field_filters'] ) ) ) {
			$parameters['search_criteria']['field_filters'] = array();
		}

		if ( $request instanceof REST\Request ) {
			$atts                 = $this->settings->as_atts();
			$paging_parameters    = wp_parse_args(
				$request->get_paging(),
				array(
					'paging' => array( 'page_size' => $atts['page_size'] ),
				)
			);
			$parameters['paging'] = $paging_parameters['paging'];
		}

		$page = Utils::get( $parameters['paging'], 'current_page' ) ?
			: ( ( ( $parameters['paging']['offset'] - $this->settings->get( 'offset' ) ) / \GV\Utils::get( $parameters, 'paging/page_size', 25 ) ) + 1 );

		/**
		 * Cleanup duplicate field_filter parameters to simplify the query.
		 */
		$unique_field_filters = array();
		foreach ( Utils::get( $parameters, 'search_criteria/field_filters', array() ) as $key => $filter ) {
			if ( 'mode' === $key ) {
				$unique_field_filters['mode'] = $filter;
			} elseif ( ! in_array( $filter, $unique_field_filters ) ) {
				$unique_field_filters[] = $filter;
			}
		}
		$parameters['search_criteria']['field_filters'] = $unique_field_filters;

		if ( ! empty( $parameters['search_criteria']['field_filters'] ) ) {
			gravityview()->log->notice( 'search_criteria/field_filters is not empty, third-party code may be using legacy search_criteria filters.' );
		}

		if ( gravityview()->plugin->supports( Plugin::FEATURE_GFQUERY ) ) {

			$query_class = $this->get_query_class();

			/** @type \GF_Query $query */
			$query = new $query_class( $this->form->ID, $parameters['search_criteria'], Utils::get( $parameters, 'sorting' ) );

			// Determine if we need to apply multisort: if the query is random, we don't need to.
			$has_random = false;
			foreach ( $query->_introspect()['order'] as $order ) {
				if ( isset( $order[0] ) && $order[0] instanceof \GF_Query_Call ) {
					if( 'RAND' === $order[0]->function_name ) {
						$has_random = true;
						break;
					}
				}
			}

			/**
			 * Apply multisort.
			 */
			if ( ! empty( $has_multisort ) && ! $has_random ) {
				// Clear ordering that was set when initializing the query since we're going to set it from scratch.
				( function () {
					$this->order = [];
				} )->bindTo( $query, $query )();

				$atts = $this->settings->as_atts();

				$view_setting_sort_field_ids  = \GV\Utils::get( $atts, 'sort_field', array() );

				$view_setting_sort_directions = \GV\Utils::get( $atts, 'sort_direction', array() );

				$has_sort_query_param = ! empty( $_GET['sort'] ) && is_array( $_GET['sort'] );

				if ( $has_sort_query_param ) {
					$has_sort_query_param = array_filter( array_values( $_GET['sort'] ) );
				}

				if ( $this->settings->get( 'sort_columns' ) && $has_sort_query_param ) {
					$sort_field_ids  = array_keys( $_GET['sort'] );
					$sort_directions = array_values( $_GET['sort'] );
				} else {
					$sort_field_ids  = (array) $view_setting_sort_field_ids;
					$sort_directions = (array) $view_setting_sort_directions;
				}

				$sorting_parameters = [];

				foreach ( $sort_field_ids as $key => $id ) {
					// The original field ID can be overridden for certain fields, including the Name field
					// where a single ID becomes multiple field input IDs joined by a pipe (e.g., "3.3|3.6").
					$id_overrides = explode(
						'|',
						GravityView_frontend::_override_sorting_id_by_field_type( $id, $this->form->ID )
					);

					foreach ( $id_overrides as $id_override ) {
						$sorting_parameters[] = [
							'_original_id' => $id,
							'id'           => $id_override,
							'is_numeric'   => GVCommon::is_field_numeric( $this->form->ID, $id ),
							'direction'    => strtoupper( \GV\Utils::get( $sort_directions, $key, 'ASC' ) ),
						];
					}
				}

				/**
				 * Modifies the sorting parameters applied during the retrieval of View entries.
				 *
				 * @filter `gk/gravityview/view/entries/query/sorting-parameters`
				 *
				 * @since  2.28.0
				 *
				 * @param array $sorting_parameters The array of sorting parameters, including field ID, field type (direction, and casting types.
				 * @param View  $this               The View instance.
				 */
				$sorting_parameters = apply_filters( 'gk/gravityview/view/entries/query/sorting-parameters', $sorting_parameters, $this );

				foreach ( $sorting_parameters as $field ) {
					if ( empty( $field['id'] ) ) {
						continue;
					}

					if ( 'RAND' === strtoupper( $field['direction'] ) ) {
						$query->order( \GF_Query_Call::RAND() );

						continue;
					}

					$order = new GF_Query_Column( $field['id'], $this->form->ID );

					if ( 'id' !== $field['id'] && (int) $field['is_numeric'] ) {
						$order = \GF_Query_Call::CAST( $order, defined( 'GF_Query::TYPE_DECIMAL' ) ? \GF_Query::TYPE_DECIMAL : \GF_Query::TYPE_SIGNED );
					}

					$query->order( $order, $field['direction'] );
				}
			}

			/**
			 * Merge time subfield sorts.
			 */
			add_filter(
				'gform_gf_query_sql',
				$gf_query_timesort_sql_callback = function ( $sql ) use ( &$query ) {
					$q      = $query->_introspect();
					$orders = array();

					$merged_time = false;

					foreach ( $q['order'] as $oid => $order ) {

						$column = null;

						if ( $order[0] instanceof GF_Query_Column ) {
							$column = $order[0];
						} elseif ( $order[0] instanceof \GF_Query_Call ) {
							if ( 1 != count( $order[0]->columns ) || ! $order[0]->columns[0] instanceof GF_Query_Column ) {
								$orders[ $oid ] = $order;
								continue; // Need something that resembles a single sort
							}
							$column = $order[0]->columns[0];
						}

						if ( ! $column || ( ! $field = \GFAPI::get_field( $column->source, $column->field_id ) ) || 'time' !== $field->type ) {
							$orders[ $oid ] = $order;
							continue; // Not a time field
						}

						if ( ! class_exists( '\GV\Mocks\GF_Query_Call_TIMESORT' ) ) {
							require_once gravityview()->plugin->dir( 'future/_mocks.timesort.php' );
						}

						$orders[ $oid ] = array(
							new \GV\Mocks\GF_Query_Call_TIMESORT( 'timesort', array( $column, $sql ) ),
							$order[1], // Mock it!
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
				}
			);

			$query->limit( $parameters['paging']['page_size'] )
				->offset( ( ( $page - 1 ) * $parameters['paging']['page_size'] ) + $this->settings->get( 'offset' ) );

			/**
			 * Any joins?
			 */
			if ( gravityview()->plugin->supports( Plugin::FEATURE_JOINS ) && count( $this->joins ) ) {
				foreach ( $this->joins as $join ) {
					$query = $join->as_query_join( $query );

					if ( $this->settings->get( 'multiple_forms_disable_null_joins' ) ) {

						// Disable NULL outputs
						$condition = new GF_Query_Condition(
							new GF_Query_Column( $join->join_on_column->ID, $join->join_on->ID ),
							GF_Query_Condition::NEQ,
							new GF_Query_Literal( '' )
						);

						$query_parameters = $query->_introspect();

						$query->where( GF_Query_Condition::_and( $query_parameters['where'], $condition ) );
					}

					// Filter to active entries only
					$status_conditions = GF_Query_Condition::_or(
						new GF_Query_Condition(
							new GF_Query_Column( 'status', $join->join_on->ID ),
							GF_Query_Condition::EQ,
							new GF_Query_Literal( 'active' )
						),
						new GF_Query_Condition(
							new GF_Query_Column( 'status', $join->join_on->ID ),
							GF_Query_Condition::IS,
							GF_Query_Condition::NULL
						)
					);

					/**
					 * Modifies the join conditions applied during the retrieval of View entries.
					 *
					 * @filter `gk/gravityview/view/entries/join-conditions`
					 *
					 * @since 2.32.0
					 *
					 * @param GF_Query_Condition $status_conditions The GF_Query_Condition instance.
					 * @param Join $join The Join instance.
					 * @param View $this The View instance.
					 */
					$status_conditions = apply_filters( 'gk/gravityview/view/entries/join-conditions', $status_conditions, $join, $this );

					$q = $query->_introspect();
					$query->where( GF_Query_Condition::_and( $q['where'], $status_conditions ) );

					/**
					 * Applies legacy modifications to Query for is_approved settings.
					 */
					$this->apply_legacy_join_is_approved_query_conditions( $query, $join );
				}

				/**
				 * Unions?
				 */
			} elseif ( gravityview()->plugin->supports( Plugin::FEATURE_UNIONS ) && count( $this->unions ) ) {
				$query_parameters = $query->_introspect();

				$unions_sql = array();

				/**
				 * @param GF_Query_Condition $condition
				 * @param array $fields
				 * @param $recurse
				 *
				 * @return GF_Query_Condition
				 */
				$where_union_substitute = function ( $condition, $fields, $recurse ) {
					if ( $condition->expressions ) {
						$conditions = array();

						foreach ( $condition->expressions as $_condition ) {
							$conditions[] = $recurse( $_condition, $fields, $recurse );
						}

						return call_user_func_array(
							array( '\GF_Query_Condition', 'AND' == $condition->operator ? '_and' : '_or' ),
							$conditions
						);
					}

					if ( ! ( $condition->left && $condition->left instanceof GF_Query_Column ) || ( ! $condition->left->is_entry_column() && ! $condition->left->is_meta_column() ) ) {
						return new GF_Query_Condition(
							new GF_Query_Column( $fields[ $condition->left->field_id ]->ID ),
							$condition->operator,
							$condition->right
						);
					}

					return $condition;
				};

				foreach ( $this->unions as $form_id => $fields ) {

					// Build a new query for every unioned form
					$query_class = $this->get_query_class();

					/** @type \GF_Query|\GF_Patched_Query $q */
					$q = new $query_class( $form_id );

					// Copy the WHERE clauses but substitute the field_ids to the respective ones
					$q->where( $where_union_substitute( $query_parameters['where'], $fields, $where_union_substitute ) );

					// Copy the ORDER clause and substitute the field_ids to the respective ones
					foreach ( $query_parameters['order'] as $order ) {
						[ $column, $_order ] = $order;

						if ( $column && $column instanceof GF_Query_Column ) {
							if ( ! $column->is_entry_column() && ! $column->is_meta_column() ) {
								$column = new GF_Query_Column( $fields[ $column->field_id ]->ID );
							}

							$q->order( $column, $_order );
						}
					}

					add_filter(
						'gform_gf_query_sql',
						$gf_query_sql_callback = function ( $sql ) use ( &$unions_sql ) {
							// Remove SQL_CALC_FOUND_ROWS as it's not needed in UNION clauses
							$select = 'UNION ALL ' . str_replace( 'SQL_CALC_FOUND_ROWS ', '', $sql['select'] );

							// Record the SQL
							$unions_sql[] = array(
								// Remove columns, we'll rebuild them
								'select' => preg_replace( '#DISTINCT (.*)#', 'DISTINCT ', $select ),
								'from'   => $sql['from'],
								'join'   => $sql['join'],
								'where'  => $sql['where'],
							// Remove order and limit
							);

							// Return empty query, no need to call the database
							return array();
						}
					);

					do_action_ref_array( 'gravityview/view/query', array( &$q, $this, $request ) );

					$q->get(); // Launch

					remove_filter( 'gform_gf_query_sql', $gf_query_sql_callback );
				}

				add_filter(
					'gform_gf_query_sql',
					$gf_query_sql_callback = function ( $sql ) use ( $unions_sql ) {
						// Remove SQL_CALC_FOUND_ROWS as it's not needed in UNION clauses
						$sql['select'] = str_replace( 'SQL_CALC_FOUND_ROWS ', '', $sql['select'] );

						// Remove columns, we'll rebuild them
						preg_match( '#DISTINCT (`[motc]\d+`.`.*?`)#', $sql['select'], $select_match );
						$sql['select'] = preg_replace( '#DISTINCT (.*)#', 'DISTINCT ', $sql['select'] );

						$unions = array();

						// Transform selected columns to shared alias names
						$column_to_alias = function ( $column ) {
							$column = str_replace( '`', '', $column );
							return '`' . str_replace( '.', '_', $column ) . '`';
						};

						// Add all the order columns into the selects, so we can order by the whole union group
						preg_match_all( '#(`[motc]\d+`.`.*?`)#', $sql['order'], $order_matches );

						$columns = array(
							sprintf( '%s AS %s', $select_match[1], $column_to_alias( $select_match[1] ) ),
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
							$unions []            = implode( ' ', $union_sql );
						}

						// Add the columns to the main SELECT, but only grab the entry id column
						$sql['select'] = 'SELECT SQL_CALC_FOUND_ROWS t1_id FROM (' . $sql['select'] . implode( ', ', $columns );
						$sql['order']  = implode( ' ', $unions ) . ') AS u ' . $sql['order'];

						return $sql;
					}
				);
			}

			/**
			 * Override the \GF_Query before the get() call.
			 *
			 * @param \GF_Query $query The current query object reference
			 * @param \GV\View $this The current view object
			 * @param \GV\Request $request The request object
			 */
			do_action_ref_array( 'gravityview/view/query', array( &$query, $this, $request ) );

			gravityview()->log->debug( 'GF_Query parameters: ', array( 'data' => Utils::gf_query_debug( $query ) ) );

			$result = $this->run_db_query( $query );

			list ( $db_entries, $query ) = $result;

			/**
			 * Map from Gravity Forms entries arrays to an Entry_Collection.
			 */
			if ( count( $this->joins ) ) {
				foreach ( $db_entries as $entry ) {
					$entries->add(
						Multi_Entry::from_entries( array_map( '\GV\GF_Entry::from_entry', $entry ) )
					);
				}
			} else {
				array_map( array( $entries, 'add' ), array_map( '\GV\GF_Entry::from_entry', $db_entries ) );
			}

			if ( isset( $gf_query_sql_callback ) ) {
				remove_action( 'gform_gf_query_sql', $gf_query_sql_callback );
			}

			if ( isset( $gf_query_timesort_sql_callback ) ) {
				remove_action( 'gform_gf_query_sql', $gf_query_timesort_sql_callback );
			}

			/**
			 * Add total count callback.
			 */
			$entries->add_count_callback(
				function () use ( $query ) {
					return $query->total_found;
				}
			);
		} else {
			$entries = $this->form->entries
				->filter( \GV\GF_Entry_Filter::from_search_criteria( $parameters['search_criteria'] ) )
				->offset( $this->settings->get( 'offset' ) )
				->limit( $parameters['paging']['page_size'] )
				->page( $page );

			if ( ! empty( $parameters['sorting'] ) && is_array( $parameters['sorting'] && ! isset( $parameters['sorting']['key'] ) ) ) {
				// Pluck off multisort arrays
				$parameters['sorting'] = $parameters['sorting'][0];
			}

			if ( ! empty( $parameters['sorting'] ) && ! empty( $parameters['sorting']['key'] ) ) {
				$field     = new \GV\Field();
				$field->ID = $parameters['sorting']['key'];
				$direction = 'asc' == strtolower( $parameters['sorting']['direction'] ) ? \GV\Entry_Sort::ASC : \GV\Entry_Sort::DESC;
				$entries   = $entries->sort( new \GV\Entry_Sort( $field, $direction ) );
			}
		}

		/**
		 * Modify the entry fetching filters, sorts, offsets, limits.
		 *
		 * @param \GV\Entry_Collection $entries The entries for this view.
		 * @param \GV\View $view The view.
		 * @param \GV\Request $request The request.
		 */
		return apply_filters( 'gravityview/view/entries', $entries, $this, $request );
	}

	/**
	 * Queries database and conditionally caches results.
	 * First, checks if the long-lived cache is enabled and if the query is cached. If not, it checks if the short-lived cache is enabled and if the query is cached.
	 *
	 * @since 2.18.2
	 *
	 * @param GF_Query $query
	 *
	 * @return array{0: array, 1: GF_Query} Array of entries and the query object. The latter may be needed as it is modified during the query.
	 */
	private function run_db_query( GF_Query $query ) {
		$db_entries = null;

		$query_introspect =  $query->_introspect();

		// Order keys are randomly generated, so we need to make them deterministic or else the query hash will change every time.
		if ( isset( $query_introspect['order'] ) ) {
			$order_hashes = [];

			foreach ( $query_introspect['order'] as $order ) {
				$order_hashes[] = md5( serialize( $order ) );
			}

			$query_introspect['order'] = $order_hashes;
		}

		$query_hash = md5( serialize( $query_introspect ) );

		$caching_atts = [
			'view_id'         => $this->ID ?: null,
			'caching'         => $this->settings->get( 'caching' ),
			'caching_entries' => $this->settings->get( 'caching_entries' ),
			'query_hash'      => $query_hash,
		];

		$caching_atts = array_merge( $caching_atts, $this->settings->get( 'caching_atts', [] ) );

		$form_ids = [ $this->form->ID ];

		foreach ( $this->joins as $join ) {
			$form_ids[] = $join->join_on->ID;
		}

		$long_lived_cache = new GravityView_Cache( $form_ids, $caching_atts );

		if ( $long_lived_cache->use_cache() ) {
			$cached_entries = $long_lived_cache->get();

			if ( is_array( $cached_entries ) && array_key_exists( 'entries', $cached_entries ) && array_key_exists( 'total', $cached_entries ) ) {
				$query->total_found = $cached_entries['total'];

				return [
					$cached_entries['entries'],
					$query,
				];
			}

			$cached_entries = [
				'entries' => $query->get(),
				'total'   => $query->total_found,
			];

			if ( $long_lived_cache->set( $cached_entries, 'entries' ) ) {
				return [
					$cached_entries['entries'],
					$query,
				];
			}
		}

		/**
		 * Controls whether the query is cached per request. This is a short-lived cache.
		 *
		 * @filter gk/gravityview/view/entries/cache
		 *
		 * @since  2.18.2
		 *
		 * @param bool $enable_caching Default: true.
		 */
		if ( ! apply_filters( 'gk/gravityview/view/entries/cache', true ) ) {
			$db_entries = $query->get();

			return [
				$db_entries,
				$query,
			];
		}

		if ( ! Arr::get( self::$cache, $query_hash ) ) {
			$db_entries = $db_entries ?? $query->get();

			self::$cache[ $query_hash ] = array(
				$db_entries,
				$query,
			);
		}

		return self::$cache[ $query_hash ];
	}

	/**
	 * Last chance to configure the output.
	 *
	 * Used for CSV output, for example.
	 *
	 * @return void
	 */
	public static function template_redirect() {
		$is_csv = get_query_var( 'csv' );
		$is_tsv = get_query_var( 'tsv' );

		/**
		 * CSV output.
		 */
		if ( ! $is_csv && ! $is_tsv ) {
			return;
		}

		$view = gravityview()->request->is_view();

		if ( ! $view ) {
			return;
		}

		$error_csv = $view->can_render( array( 'csv' ) );

		if ( is_wp_error( $error_csv ) ) {
			gravityview()->log->error( 'Not rendering CSV or TSV: ' . $error_csv->get_error_message() );
			return;
		}

		$file_type = $is_csv ? 'csv' : 'tsv';

		/**
		 * Modify the name of the generated CSV or TSV file. Name will be sanitized using sanitize_file_name() before output.
		 *
		 * @see sanitize_file_name()
		 * @since 2.1
		 * @param string   $filename File name used when downloading a CSV or TSV. Default is "{View title}.csv" or "{View title}.tsv"
		 * @param \GV\View $view Current View being rendered
		 */
		$filename = apply_filters( 'gravityview/output/' . $file_type . '/filename', get_the_title( $view->post ), $view );

		if ( ! defined( 'DOING_GRAVITYVIEW_TESTS' ) ) {
			header( sprintf( 'Content-Disposition: attachment;filename="%s.' . $file_type . '"', sanitize_file_name( $filename ) ) );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Content-Type: text/' . $file_type );
		}

		ob_start();
		$csv_or_tsv = fopen( 'php://output', 'w' );

		/**
		 * Add da' BOM if GF uses it
		 *
		 * @see GFExport::start_export()
		 */
		if ( apply_filters( 'gform_include_bom_export_entries', true, $view->form ? $view->form->form : null ) ) {
			fputs( $csv_or_tsv, "\xef\xbb\xbf" );
		}

		if ( $view->settings->get( 'csv_nolimit' ) ) {
			$view->settings->update( array( 'page_size' => -1 ) );
		}

		$entries = $view->get_entries();

		$headers_done = false;
		$allowed      = $headers = array();

		foreach ( $view->fields->by_position( 'directory_*' )->by_visible( $view )->all() as $id => $field ) {
			$allowed[] = $field;
		}

		$renderer = new Field_Renderer();

		foreach ( $entries->all() as $entry ) {

			$return = array();

			/**
			 * Allowlist more entry fields by ID that are output in CSV requests.
			 *
			 * @param array $allowed The allowed ones, default by_visible, by_position( "context_*" ), i.e. as set in the View.
			 * @param \GV\View $view The view.
			 * @param \GV\Entry $entry WordPress representation of the item.
			 */
			$allowed_field_ids = apply_filters( 'gravityview/csv/entry/fields', wp_list_pluck( $allowed, 'ID' ), $view, $entry );

			$allowed = array_filter(
				$allowed,
				function ( $field ) use ( $allowed_field_ids ) {
					return in_array( $field->ID, $allowed_field_ids, true );
				}
			);

			foreach ( array_diff( $allowed_field_ids, wp_list_pluck( $allowed, 'ID' ) ) as $field_id ) {
				$allowed[] = is_numeric( $field_id ) ? \GV\GF_Field::by_id( $view->form, $field_id ) : \GV\Internal_Field::by_id( $field_id );
			}

			foreach ( $allowed as $field ) {
				$source = self::get_source( $field, $view );

				$return[] = $renderer->render( $field, $view, $source, $entry, gravityview()->request, '\GV\Field_CSV_Template' );

				if ( ! $headers_done ) {
					$label     = $field->get_label( $view, $source, $entry );
					$headers[] = $label ? $label : $field->ID;
				}
			}

			// If not "tsv" then use comma
			$delimiter = ( 'tsv' === $file_type ) ? "\t" : ',';

			if ( ! $headers_done ) {
				$headers_done = fputcsv( $csv_or_tsv, array_map( array( '\GV\Utils', 'strip_excel_formulas' ), array_values( $headers ) ), $delimiter );
			}

			fputcsv( $csv_or_tsv, array_map( array( '\GV\Utils', 'strip_excel_formulas' ), $return ), $delimiter );
		}

		fflush( $csv_or_tsv );

		echo rtrim( ob_get_clean() );

		if ( ! defined( 'DOING_GRAVITYVIEW_TESTS' ) ) {
			exit;
		}
	}

	/**
	 * Return the query class for this View.
	 *
	 * @return string The class name.
	 */
	public function get_query_class() {
		/**
		 * @filter `gravityview/query/class`
		 * @param string The query class. Default: GF_Query.
		 * @param \GV\View $this The View.
		 */
		$query_class = apply_filters( 'gravityview/query/class', '\GF_Query', $this );
		return $query_class;
	}

	/**
	 * Restrict View access to specific capabilities.
	 *
	 * Hooked into `map_meta_cap` WordPress filter.
	 *
	 * @since develop
	 *
	 * @param $caps    array  The output capabilities.
	 * @param $cap     string The cap that is being checked.
	 * @param $user_id int    The User ID.
	 * @param $args    array  Additional arguments to the capability.
	 *
	 * @return array   The resulting capabilities.
	 */
	public static function restrict( $caps, $cap, $user_id, $args ) {
		/**
		 * Bypass restrictions on Views that require `unfiltered_html`.
		 *
		 * @param boolean
		 *
		 * @since develop
		 * @param string $cap The capability requested.
		 * @param int $user_id The user ID.
		 * @param array $args Any additional args to map_meta_cap
		 */
		if ( ! apply_filters( 'gravityview/security/require_unfiltered_html', true, $cap, $user_id ) ) {
			return $caps;
		}

		switch ( $cap ) :
			case 'edit_gravityview':
			case 'edit_gravityviews':
			case 'edit_others_gravityviews':
			case 'edit_private_gravityviews':
			case 'edit_published_gravityviews':
				if ( ! user_can( $user_id, 'unfiltered_html' ) ) {
					if ( ! user_can( $user_id, 'gravityview_full_access' ) ) {
						return array( 'do_not_allow' );
					}
				}

				return $caps;
			case 'edit_post':
				if ( self::POST_TYPE === get_post_type( array_pop( $args ) ) ) {
					return self::restrict( $caps, 'edit_gravityview', $user_id, $args );
				}
		endswitch;

		return $caps;
	}

	/**
	 * Sets the anchor ID of a View, without the prefix.
	 *
	 * @since 2.15
	 *
	 * @param int $counter An incremental counter reflecting how many times this View has been rendered.
	 *
	 * @return void
	 */
	public function set_anchor_id( $counter = 1 ) {
		$this->anchor_id = sprintf( 'gv-view-%d-%d', $this->ID, (int) $counter );
	}

	/**
	 * Returns the anchor ID to be used in the View container HTML `id` attribute.
	 *
	 * @since 2.15
	 *
	 * @return string Unsanitized anchor ID.
	 */
	public function get_anchor_id() {
		/**
		 * Modify the anchor ID.
		 *
		 * @since 2.15
		 * @param string $anchor_id The anchor ID.
		 * @param \GV\View $this The View.
		 */
		return apply_filters( 'gravityview/view/anchor_id', $this->anchor_id, $this );
	}

	public function __get( $key ) {
		if ( $this->post ) {
			$raw_post = $this->post->filter( 'raw' );
			return $raw_post->{$key};
		}
		return isset( $this->{$key} ) ? $this->{$key} : null;
	}

	/**
	 * Return associated WP post
	 *
	 * @since 2.13.2
	 *
	 * @return \WP_Post|null
	 */
	public function get_post() {
		return $this->post ? $this->post : null;
	}

	/**
	 * On version 0.3.0 of Multiple Forms is_approved for joins is handled elsewhere, for backwards compatibility purposes
	 * the goal here is to only apply this while Multiple Forms is still compatible with older versions of GravityView.
	 *
	 * @since 2.17.2
	 *
	 * @param \GF_Query $query
	 * @param Join      $join
	 */
	protected function apply_legacy_join_is_approved_query_conditions( \GF_Query $query, Join $join ): void {
		/**
		 * Allows Multiple Forms and other plugins to deactivate this piece of functionality when loaded.
		 *
		 * @since 2.17.2
		 *
		 * @param bool      $should_apply Determines if legacy join condition should be applied.
		 * @param \GF_Query $query        Which is being dealt with.
		 * @param Join      $join         Which join we are dealing with.
		 * @param self      $view         Instance of the view we are dealing with.
		 */
		$should_apply = (bool) apply_filters( 'gravityview/view/get_entries/should_apply_legacy_join_is_approved_query_conditions', true, $query, $join, $this );
		if ( ! $should_apply ) {
			return;
		}

		if ( ! $this->settings->get( 'show_only_approved' ) ) {
			return;
		}

		$is_admin_and_can_view = $this->settings->get( 'admin_show_all_statuses' ) && GVCommon::has_cap( 'gravityview_moderate_entries', $this->ID );

		if ( $is_admin_and_can_view ) {
			return;
		}

		// Show only approved joined entries
		$condition = new GF_Query_Condition(
			new GF_Query_Column( \GravityView_Entry_Approval::meta_key, $join->join_on->ID ),
			GF_Query_Condition::EQ,
			new GF_Query_Literal( \GravityView_Entry_Approval_Status::APPROVED )
		);

		$condition = GF_Query_Condition::_or(
			$condition,
			new GF_Query_Condition(
				new GF_Query_Column( \GravityView_Entry_Approval::meta_key, $join->join_on->ID ),
				GF_Query_Condition::IS,
				GF_Query_Condition::NULL
			)
		);

		$query_parameters = $query->_introspect();

		$query->where( GF_Query_Condition::_and( $query_parameters['where'], $condition ) );
	}

	/**
	 * Calculates and returns the View's validation secret.
	 *
	 * @since 2.21
	 *
	 * @return string|null The View's secret.
	 */
	final public function get_validation_secret( bool $is_forced = false ): ?string {
		// Cannot use the setting variable because it can be overwritten from the short code.
		$settings  = get_post_meta( $this->ID, '_gravityview_template_settings', true );
		$is_secure = (bool) rgar( $settings, 'is_secure', false );

		if ( ( ! $is_secure && ! $is_forced ) || ! class_exists( GravityKitFoundation::class ) ) {
			return null;
		}

		$foundation = GravityKitFoundation::get_instance();
		$encryption = $foundation->encryption();
		$hash       = $encryption->hash( $this->ID );

		return substr( $hash, 0, 12 );
	}

	/**
	 * Returns whether the provided secret validates for this View.
	 *
	 * @since 2.21
	 *
	 * @param string $secret The provided secret.
	 *
	 * @return bool
	 */
	final public function validate_secret( string $secret ): bool {
		$view_secret = $this->get_validation_secret();
		if ( ! $view_secret ) {
			return true;
		}

		return $secret === $view_secret;
	}

	/**
	 * Returns the shortcode for this View.
	 *
	 * @since 2.21
	 * @since 2.22 Added `$atts` parameter.
	 *
	 * @param array $atts Additional attributes for the shortcode.
	 *
	 * @return string
	 */
	final public function get_shortcode( array $atts = [] ): string {
		$secret = $this->get_validation_secret();

		// ID & secret can't be overwritten from the View.
		$atts['id'] = $this->post->ID;

		if ( $secret ) {
			$atts['secret'] = $secret;
		}

		$options = [];
		foreach ( $atts as $key => $value ) {
			$options[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		return sprintf( '[gravityview %s]', implode( ' ', $options ) );
	}
}
