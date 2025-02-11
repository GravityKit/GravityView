<?php

/**
 * Register and render the admin metaboxes for GravityView
 */
class GravityView_Admin_Metaboxes {

	/**
	 * Group identifier for View Settings tab
	 * @since TODO
	 */
	const GROUP_VIEW_SETTINGS = 'template_settings';

	/**
	 * Group identifier for Multiple Entries tab
	 * @since TODO
	 */
	const GROUP_MULTIPLE_ENTRIES = 'multiple_entries';

	/**
	 * Group identifier for Single Entry tab
	 * @since TODO
	 */
	const GROUP_SINGLE_ENTRY = 'single_entry';

	/**
	 * Group identifier for Edit Entry tab
	 * @since TODO
	 */
	const GROUP_EDIT_ENTRY = 'edit_entry';

	/**
	 * Group identifier for Delete Entry tab
	 * @since TODO
	 */
	const GROUP_DELETE_ENTRY = 'delete_entry';

	/**
	 * Group identifier for Filter & Sort tab
	 * @since TODO
	 */
	const GROUP_SORT_FILTER = 'sort_filter';

	/**
	 * Group identifier for Permissions tab
	 * @since TODO
	 */
	const GROUP_PERMISSIONS = 'permissions';

	/**
	 * Group identifier for Advanced/Custom Code tab
	 * @since TODO
	 */
	const GROUP_ADVANCED = 'advanced';

	/**
	 * Directory path to the metaboxes folder
	 * 
	 * @var string Path to the metaboxes directory, with trailing slash
	 * @static
	 */
	static $metaboxes_dir;
	/**
	 * @var int The post ID of the current View
	 */
	var $post_id = 0;

	/**
	 *
	 */
	function __construct() {
		self::$metaboxes_dir = GRAVITYVIEW_DIR . 'includes/admin/metaboxes/';

		include_once self::$metaboxes_dir . 'class-gravityview-metabox-tab.php';

		include_once self::$metaboxes_dir . 'class-gravityview-metabox-tabs.php';

		$this->initialize();
	}

	/**
	 * Add WordPress hooks
	 *
	 * @since 1.7.2
	 */
	function initialize() {

		add_action( 'add_meta_boxes', array( $this, 'register_metaboxes' ) );

		add_action( 'add_meta_boxes_gravityview', array( $this, 'update_priority' ) );

		// information box
		add_action( 'post_submitbox_misc_actions', array( $this, 'render_direct_access_status' ), 9 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'render_shortcode_hint' ) );
	}

	/**
	 * GravityView wants to have the top (`normal`) metaboxes all to itself, so we move all plugin/theme metaboxes down to `advanced`
	 *
	 * @since 1.15.2
	 */
	function update_priority() {
		global $wp_meta_boxes;

		if ( ! empty( $wp_meta_boxes['gravityview'] ) ) {
			foreach ( array( 'high', 'core', 'low' ) as $position ) {
				if ( isset( $wp_meta_boxes['gravityview']['normal'][ $position ] ) ) {
					foreach ( $wp_meta_boxes['gravityview']['normal'][ $position ] as $key => $meta_box ) {
						if ( ! preg_match( '/^gravityview_/ism', $key ) ) {
							$wp_meta_boxes['gravityview']['advanced'][ $position ][ $key ] = $meta_box;
							unset( $wp_meta_boxes['gravityview']['normal'][ $position ][ $key ] );
						}
					}
				}
			}
		}
	}

	function register_metaboxes() {
		global $post;

		// On Comment Edit, for example, $post isn't set.
		if ( empty( $post ) || ! is_object( $post ) || ! isset( $post->ID ) ) {
			return;
		}

		// select data source for this view
		add_meta_box( 'gravityview_select_form', $this->get_data_source_header( $post->ID ), array( $this, 'render_data_source_metabox' ), 'gravityview', 'normal', 'high' );

		// select view type/template
		add_meta_box( 'gravityview_select_template', __( 'Choose a View Type', 'gk-gravityview' ), array( $this, 'render_select_template_metabox' ), 'gravityview', 'normal', 'high' );

		// View Configuration box
		add_meta_box( 'gravityview_view_config', __( 'Layout', 'gk-gravityview' ), array( $this, 'render_view_configuration_metabox' ), 'gravityview', 'normal', 'high' );

		$this->add_settings_metabox_tabs();

		// Other Settings box
		add_meta_box( 'gravityview_settings', __( 'Settings', 'gk-gravityview' ), array( $this, 'settings_metabox_render' ), 'gravityview', 'normal', 'core' );
	}

	/**
	 * Render the View Settings metabox
	 *
	 * @since 1.8
	 * @param WP_Post $post
	 */
	function settings_metabox_render( $post ) {

		/**
		 * Before rendering GravityView metaboxes.
		 *
		 * @since 1.8
		 * @param WP_Post $post
		 */
		do_action( 'gravityview/metaboxes/before_render', $post );

		$metaboxes = GravityView_Metabox_Tabs::get_all();

		include self::$metaboxes_dir . 'views/gravityview-navigation.php';
		include self::$metaboxes_dir . 'views/gravityview-content.php';

		/**
		 * After rendering GravityView metaboxes.
		 *
		 * @since 1.8
		 * @param WP_Post $post
		 */
		do_action( 'gravityview/metaboxes/after_render', $post );
	}

	/**
	 * Returns the default settings metabox tabs.
	 *
	 * @since TODO
	 * @static
	 * 
	 * @return array Metabox tabs, filtered by `gravityview/metaboxes/default` filter. {
	 *   @type string $id            The tab ID.
	 *   @type string $title         The tab title.
	 *   @type string $file          The file to include, relative to $metaboxes_dir.
	 *   @type string $icon-class    The dashicon class.
	 *   @type string $callback      The callback function.
	 *   @type string $callback_args The callback arguments.
	 * }
	 */
	static public function get_settings_metabox_tabs() {
		$metaboxes = array(
			array(
				'id'            => self::GROUP_VIEW_SETTINGS,
				'title'         => esc_html__( 'View Settings', 'gk-gravityview' ),
				'file'          => 'view-settings.php',
				'icon-class'    => 'dashicons-admin-generic',
				'callback'      => '',
				'callback_args' => '',
			),
			array(
				'id'            => self::GROUP_MULTIPLE_ENTRIES,
				'title'         => esc_html__( 'Multiple Entries', 'gk-gravityview' ),
				'file'          => 'multiple-entries.php',
				'icon-class'    => 'dashicons-admin-page',
				'callback'      => '',
				'callback_args' => '',
			),
			array(
				'id'            => self::GROUP_SINGLE_ENTRY,
				'title'         => esc_html__( 'Single Entry', 'gk-gravityview' ),
				'file'          => 'single-entry.php',
				'icon-class'    => 'dashicons-media-default',
				'callback'      => '',
				'callback_args' => '',
			),
			array(
				'id'            => self::GROUP_EDIT_ENTRY,
				'title'         => esc_html__( 'Edit Entry', 'gk-gravityview' ),
				'file'          => 'edit-entry.php',
				'icon-class'    => 'dashicons-welcome-write-blog',
				'callback'      => '',
				'callback_args' => '',
			),
			array(
				'id'            => self::GROUP_DELETE_ENTRY,
				'title'         => esc_html__( 'Delete Entry', 'gk-gravityview' ),
				'file'          => 'delete-entry.php',
				'icon-class'    => 'dashicons-trash',
				'callback'      => '',
				'callback_args' => '',
			),
			array(
				'id'            => self::GROUP_SORT_FILTER,
				'title'         => esc_html( __( 'Filter &amp; Sort', 'gk-gravityview' ) ),
				'file'          => 'sort-filter.php',
				'icon-class'    => 'dashicons-sort',
				'callback'      => '',
				'callback_args' => '',
			),
			array(
				'id'            => self::GROUP_PERMISSIONS,
				'title'         => esc_html__( 'Permissions', 'gk-gravityview' ),
				'file'          => 'permissions.php',
				'icon-class'    => 'dashicons-lock',
				'callback'      => '',
				'callback_args' => '',
			),
			array(
				'id'            => self::GROUP_ADVANCED,
				'title'         => esc_html__( 'Custom Code', 'gk-gravityview' ),
				'file'          => 'custom-code.php',
				'icon-class'    => 'dashicons-editor-code',
				'callback'      => '',
				'callback_args' => '',
			),
		);

		/**
		 * Modify the default settings metabox tabs.
		 *
		 * @param array $metaboxes
		 * @since 1.8
		 */
		$metaboxes = apply_filters( 'gravityview/metaboxes/default', $metaboxes );

		return $metaboxes;
	}

	/**
	 * Add default tabs to the Settings metabox
	 *
	 * @since 1.8
	 */
	private function add_settings_metabox_tabs() {
		$metaboxes = self::get_settings_metabox_tabs();

		foreach ( $metaboxes as $m ) {
			$tab = new GravityView_Metabox_Tab( $m['id'], $m['title'], $m['file'], $m['icon-class'], $m['callback'], $m['callback_args'] );
			GravityView_Metabox_Tabs::add( $tab );
		}

		unset( $tab );
	}

	/**
	 * Generate the title for Data Source, which includes the Action Links once configured.
	 *
	 * @since 1.8
	 *
	 * @param int $post_id ID of the current post
	 *
	 * @return string "Data Source", plus links if any
	 */
	private function get_data_source_header( $post_id ) {
		/**
		 * This method is running before GravityView's been fully set up; likely being called by another plugin.
		 *
		 * @see https://github.com/gravityview/GravityView/issues/1684
		 */
		if ( ! class_exists( 'GravityView_Admin_Views' ) ) {
			return __( 'Data Source', 'gk-gravityview' );
		}

		$current_form = gravityview_get_form( gravityview_get_form_id( $post_id ) );

		$links = GravityView_Admin_Views::get_connected_form_links( $current_form, false );

		if ( ! empty( $links ) ) {
			$links = '<span class="alignright gv-form-links">' . $links . '</span>';
		}

		$output = $links;

		if ( ! $current_form ) {
			// Starting from GF 2.6, GF's form_admin.js script requires window.form and window.gf_vars objects to be set when any element has a .merge-tag-support class.
			// Since we don't yet have a form when creating a new View, we need to mock those objects.
			$_id        = isset( $_GET['id'] ) ? $_GET['id'] : null;
			$_GET['id'] = - 1; // This is needed for GFCommon::gf_vars() to return the mergeTags property.

			if ( function_exists( 'error_reporting' ) ) {
				// Store the original error reporting level.
				$original_error_reporting = error_reporting();

				// Turn off warnings.
				error_reporting( $original_error_reporting & ~E_WARNING );
			}

			$output .= sprintf(
				'<script type="text/javascript">var form = %s; %s</script>',
				'{fields: []}',
				@GFCommon::gf_vars( false ) // Need to silence errors because the form doesn't exist and GF doesn't expect that.
			);

			if ( function_exists( 'error_reporting' ) ) {
				error_reporting( $original_error_reporting );
			}

			$_GET['id'] = $_id;
		}

		return __( 'Data Source', 'gk-gravityview' ) . $output;
	}

	/**
	 * Render html for 'select form' metabox
	 *
	 * @param object $post
	 * @return void
	 */
	public function render_data_source_metabox( $post ) {

		include self::$metaboxes_dir . 'views/data-source.php';
	}

	/**
	 * Render html for 'select template' metabox
	 *
	 * @param object $post
	 * @return void
	 */
	public function render_select_template_metabox( $post ) {

		include self::$metaboxes_dir . 'views/select-template.php';
	}

	/**
	 * Generate the script tags necessary for the Gravity Forms Merge Tag picker to work.
	 *
	 * @param  int $curr_form Form ID
	 * @return null|string     Merge tags html; NULL if $curr_form isn't defined.
	 */
	public static function render_merge_tags_scripts( $curr_form ) {

		if ( empty( $curr_form ) ) {
			return null;
		}

		$form = gravityview_get_form( $curr_form );

		$get_id_backup = isset( $_GET['id'] ) ? $_GET['id'] : null;

		if ( isset( $form['id'] ) ) {
			$form_script = 'var form = ' . GFCommon::json_encode( $form ) . ';';

			// The `gf_vars()` method needs a $_GET[id] variable set with the form ID.
			$_GET['id'] = $form['id'];

		} else {
			$form_script = 'var form = new Form();';
		}

		$output = '<script type="text/javascript" data-gv-merge-tags="1">' . $form_script . "\n" . GFCommon::gf_vars( false ) . '</script>';

		// Restore previous $_GET setting
		$_GET['id'] = $get_id_backup;

		return $output;
	}

	/**
	 * Render html for 'View Configuration' metabox
	 *
	 * @param mixed $post
	 * @return void
	 */
	function render_view_configuration_metabox( $post ) {

		// Use nonce for verification
		wp_nonce_field( 'gravityview_view_configuration', 'gravityview_view_configuration_nonce' );

		// Selected Form
		$curr_form = gravityview_get_form_id( $post->ID );

		$view = \GV\View::from_post( $post );

		/**
		 * Selected templates
		 *
		 * @deprecated $curr_template since $ver$
		 *             Use $directory_entries_template instead.
		 */
		$curr_template              = gravityview_get_directory_entries_template_id( $post->ID );
		$directory_entries_template = gravityview_get_directory_entries_template_id( $post->ID );
		$single_entry_template      = gravityview_get_single_entry_template_id( $post->ID );

		echo self::render_merge_tags_scripts( $curr_form );

		include self::$metaboxes_dir . 'views/view-configuration.php';
	}

	/**
	 * Render html View General Settings
	 *
	 * @param object $post
	 * @return void
	 */
	function render_view_settings_metabox( $post ) {

		// View template settings
		$current_settings = gravityview_get_template_settings( $post->ID );

		include self::$metaboxes_dir . 'views/view-settings.php';
	}

	/**
	 * Render shortcode hint in the Publish metabox
	 *
	 * @return void
	 */
	function render_shortcode_hint() {
		global $post;

		// Only show this on GravityView post types.
		if ( false === gravityview()->request->is_admin( '', null ) ) {
			return;
		}

		// If the View hasn't been configured yet, don't show embed shortcode
		if ( ! gravityview_get_directory_fields( $post->ID ) && ! gravityview_get_directory_widgets( $post->ID ) ) {
			return;
		}

		include self::$metaboxes_dir . 'views/shortcode-hint.php';
	}

	/**
	 * Render Direct Access setting in the Publish metabox.
	 *
	 * @since TODO
	 *
	 * @return void
	 */
	function render_direct_access_status() {
		global $post;

		// Only show this on GravityView post types.
		if ( false === gravityview()->request->is_admin( '', null ) ) {
			return;
		}

		// If the View hasn't been configured yet, don't show embed shortcode
		if ( ! gravityview_get_directory_fields( $post->ID ) && ! gravityview_get_directory_widgets( $post->ID ) ) {
			return;
		}

		include self::$metaboxes_dir . 'views/direct-access-status.php';
	}
}

new GravityView_Admin_Metaboxes();
