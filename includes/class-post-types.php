<?php
/**
 * GravityView Defining Post Types and Rewrite rules
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.9
 */

class GravityView_Post_Types {

	function __construct() {

		// Load custom post types. It's a static method.
		// Load even when invalid to allow for export
		add_action( 'init', array( 'GravityView_Post_Types', 'init_post_types' ) );

		if( GravityView_Compatibility::is_valid() ) {
			add_action( 'init', array( 'GravityView_Post_Types', 'init_rewrite' ) );
		}
	}

	/**
	 * Init plugin components such as register own custom post types
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function init_post_types() {

		/**
		 * Make GravityView Views hierarchical by returning TRUE
		 *
		 * This will allow for Views to be nested with Parents and also allows for menu order to be set in the Page Attributes metabox
		 *
		 * @since 1.13
		 */
		$is_hierarchical = (bool)apply_filters( 'gravityview_is_hierarchical', false );

		$supports = array( 'title', 'genesis-layouts', 'revisions' );

		if( $is_hierarchical ) {
			$supports[] = 'page-attributes';
		}

		//Register Custom Post Type - gravityview
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
			'not_found'           => self::no_views_text(),
			'not_found_in_trash'  => __( 'No Views found in Trash', 'gravityview' ),
		);
		$args = array(
			'label'               => __( 'view', 'gravityview' ),
			'description'         => __( 'Create views based on a Gravity Forms form', 'gravityview' ),
			'labels'              => $labels,
			'supports'            => $supports,
			'hierarchical'        => $is_hierarchical,
			'public'              => GravityView_Compatibility::is_valid(),
			'show_ui'             => GravityView_Compatibility::is_valid(),
			'show_in_menu'        => GravityView_Compatibility::is_valid(),
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 17,
			'menu_icon'           => '',
			'can_export'          => true,
			/**
			 * Enable Custom Post Type archive
			 * @since 1.7.3
			 * @param boolean False: don't have frontend archive; True: yes, have archive
			 */
			'has_archive'         => apply_filters( 'gravityview_has_archive', false ),
			'exclude_from_search' => true,
			'publicly_queryable'  => GravityView_Compatibility::is_valid(),
			'rewrite'             => array(
				/**
				 * @filter `gravityview_slug` Modify the url part for a View. [Read the doc](http://docs.gravityview.co/article/62-changing-the-view-slug)
				 * @param string $slug The slug shown in the URL
				 */
				'slug' => apply_filters( 'gravityview_slug', 'view' )
			),
			'capability_type'     => 'page',
			'map_meta_cap'        => true,
		);

		register_post_type( 'gravityview', $args );

	}

	/**
	 * Register rewrite rules to capture the single entry view
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function init_rewrite() {

		$endpoint = self::get_entry_var_name();

		//add_permastruct( "{$endpoint}", $endpoint.'/%'.$endpoint.'%/?', true);
		add_rewrite_endpoint( "{$endpoint}", EP_ALL );
	}

	/**
	 * Return the query var / end point name for the entry
	 *
	 * @access public
	 * @static
	 * @return string Default: "entry"
	 */
	public static function get_entry_var_name() {

		/**
		 * @filter `gravityview_directory_endpoint` Change the slug used for single entries
		 * @param[in,out] string $endpoint Slug to use when accessing single entry. Default: `entry`
		 */
		$endpoint = apply_filters( 'gravityview_directory_endpoint', 'entry' );

		return sanitize_title( $endpoint );
	}

	/**
	 * Get text for no views found.
	 * @todo Move somewhere appropriate.
	 * @return string HTML message with no container tags.
	 */
	static function no_views_text() {

		if( !class_exists( 'GravityView_Admin' ) ) {
			require_once( GRAVITYVIEW_DIR .'includes/class-admin.php' );
		}

		// Floaty the astronaut
		$image = GravityView_Admin::get_floaty();

		$not_found =  sprintf( esc_attr__("%sYou don't have any active views. Let&rsquo;s go %screate one%s!%s\n\nIf you feel like you're lost in space and need help getting started, check out the %sGetting Started%s page.", 'gravityview' ), '<h3>', '<a href="'.admin_url('post-new.php?post_type=gravityview').'">', '</a>', '</h3>', '<a href="'.admin_url( 'edit.php?post_type=gravityview&page=gv-getting-started' ).'">', '</a>' );

		return $image.wpautop( $not_found );
	}


}

new GravityView_Post_Types;
