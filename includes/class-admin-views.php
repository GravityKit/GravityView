<?php
/**
 * Renders all the metaboxes on Add New / Edit View post type.
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class GravityView_Admin_Views {



	function __construct() {

		add_action( 'save_post', array( $this, 'save_postdata' ) );

		// set the blacklist field types across the entire plugin
		add_filter( 'gravityview_blacklist_field_types', array( $this, 'default_field_blacklist' ), 10, 2 );

		// Tooltips
		add_filter( 'gform_tooltips', array( $this, 'tooltips') );

		// adding styles and scripts
		add_action( 'admin_enqueue_scripts', array( 'GravityView_Admin_Views', 'add_scripts_and_styles'), 999 );
		add_filter( 'gform_noconflict_styles', array( $this, 'register_no_conflict') );
		add_filter( 'gform_noconflict_scripts', array( $this, 'register_no_conflict') );
		add_filter( 'gravityview_noconflict_styles', array( $this, 'register_no_conflict') );
		add_filter( 'gravityview_noconflict_scripts', array( $this, 'register_no_conflict') );

		add_action( 'gravityview_render_directory_active_areas', array( $this, 'render_directory_active_areas'), 10, 4 );
		add_action( 'gravityview_render_widgets_active_areas', array( $this, 'render_widgets_active_areas'), 10, 3 );
		add_action( 'gravityview_render_available_fields', array( $this, 'render_available_fields'), 10, 2 );
		add_action( 'gravityview_render_available_widgets', array( $this, 'render_available_widgets') );
		add_action( 'gravityview_render_active_areas', array( $this, 'render_active_areas'), 10, 5 );

		// @todo check if this hook is needed..
		//add_action( 'gravityview_render_field_options', array( $this, 'render_field_options'), 10, 9 );

		// Add Connected Form column
		add_filter('manage_gravityview_posts_columns' , array( $this, 'add_post_type_columns' ) );

		add_filter( 'gform_toolbar_menu', array( 'GravityView_Admin_Views', 'gform_toolbar_menu' ), 10, 2 );

		add_action( 'manage_gravityview_posts_custom_column', array( $this, 'add_custom_column_content'), 10, 2 );

		add_action( 'restrict_manage_posts', array( $this, 'add_view_dropdown' ) );

		add_action( 'pre_get_posts', array( $this, 'filter_pre_get_posts_by_gravityview_form_id' ) );

	}

	/**
	 * @since 1.15
	 * @param WP_Query $query
	 */
	public function filter_pre_get_posts_by_gravityview_form_id( &$query ) {
		global $pagenow;

		if ( !is_admin() ) {
			return;
		}

		$form_id = isset( $_GET['gravityview_form_id'] ) ? (int) $_GET['gravityview_form_id'] : false;

		if( 'edit.php' !== $pagenow || ! $form_id || ! isset( $query->query_vars[ 'post_type' ] ) ) {
			return;
		}

		if ( $query->query_vars[ 'post_type' ] == 'gravityview' ) {
			$query->set( 'meta_query', array(
				array(
					'key' => '_gravityview_form_id',
					'value' => $form_id,
				)
			) );
		}
	}

	function add_view_dropdown() {
		$current_screen = get_current_screen();

		if( 'gravityview' !== $current_screen->post_type ) {
			return;
		}

		$forms = gravityview_get_forms();
		$current_form = rgget( 'gravityview_form_id' );
		// If there are no forms to select, show no forms.
		if( !empty( $forms ) ) { ?>
			<select name="gravityview_form_id" id="gravityview_form_id">
				<option value="" <?php selected( '', $current_form, true ); ?>><?php esc_html_e( 'All forms', 'gravityview' ); ?></option>
				<?php foreach( $forms as $form ) { ?>
					<option value="<?php echo $form['id']; ?>" <?php selected( $form['id'], $current_form, true ); ?>><?php echo esc_html( $form['title'] ); ?></option>
				<?php } ?>
			</select>
		<?php }
	}


	/**
	 * @deprecated since 1.2
	 * Start using GravityView_Render_Settings::render_setting_row
	 */
	public static function render_setting_row( $key = '', $current_settings = array(), $override_input = null, $name = 'template_settings[%s]', $id = 'gravityview_se_%s' ) {
		_deprecated_function( 'GravityView_Admin_Views::render_setting_row', '1.1.7', 'GravityView_Render_Settings::render_setting_row' );
		GravityView_Render_Settings::render_setting_row( $key, $current_settings, $override_input, $name , $id );
	}

	/**
	 * @deprecated since 1.2
	 * Start using GravityView_Render_Settings::render_field_option
	 */
	public static function render_field_option( $name = '', $option, $curr_value = NULL ) {
		_deprecated_function( 'GravityView_Admin_Views::render_field_option', '1.1.7', 'GravityView_Render_Settings::render_field_option' );
		return GravityView_Render_Settings::render_field_option( $name, $option, $curr_value );
	}


	/**
	 * Add a GravityView menu to the Form Toolbar with connected views
	 * @param  array  $menu_items Menu items, as set in GFForms::top_toolbar()
	 * @param  int $id         ID of the current Gravity form
	 * @return array            Modified array
	 */
	static function gform_toolbar_menu( $menu_items = array(), $id = NULL ) {

		$connected_views = gravityview_get_connected_views( $id );

		if( empty( $connected_views ) ) {

		    $menu_items['gravityview'] = array(
				'label'          => esc_attr__( 'Create a View', 'gravityview' ),
				'icon'           => '<i class="fa fa-lg gv-icon-astronaut-head gv-icon"></i>',
				'title'          => esc_attr__( 'Create a View using this form as a data source', 'gravityview' ),
				'url'            => admin_url( 'post-new.php?post_type=gravityview&form_id=' . $id ),
				'menu_class'     => 'gv_connected_forms gf_form_toolbar_settings',
				'priority'       => 0,
				'capabilities'   => array( 'edit_gravityviews' ),
			);

			return $menu_items;
		}

		$sub_menu_items = array();
		foreach ( (array)$connected_views as $view ) {

			if( ! GVCommon::has_cap( 'edit_gravityview', $view->ID ) ) {
				continue;
			}

			$label = empty( $view->post_title ) ? sprintf( __('No Title (View #%d)', 'gravityview' ), $view->ID ) : $view->post_title;

			$sub_menu_items[] = array(
				'label' => esc_attr( $label ),
				'url' => admin_url( 'post.php?action=edit&post='.$view->ID ),
			);
		}

		// If there were no items added, then let's create the parent menu
		if( $sub_menu_items ) {

		    $sub_menu_items[] = array(
			    'label' => esc_attr__( 'Create a View', 'gravityview' ),
                'link_class' => 'gv-create-view',
			    'title' => esc_attr__( 'Create a View using this form as a data source', 'gravityview' ),
			    'url'   => admin_url( 'post-new.php?post_type=gravityview&form_id=' . $id ),
			    'capabilities'   => array( 'edit_gravityviews' ),
            );

			// Make sure Gravity Forms uses the submenu; if there's only one item, it uses a link instead of a dropdown
			$sub_menu_items[] = array(
				'url' => '#',
				'label' => '',
				'menu_class' => 'hidden',
				'capabilities' => '',
			);

			$menu_items['gravityview'] = array(
				'label'          => __( 'Connected Views', 'gravityview' ),
				'icon'           => '<i class="fa fa-lg gv-icon-astronaut-head gv-icon"></i>',
				'title'          => __( 'GravityView Views using this form as a data source', 'gravityview' ),
				'url'            => '#',
				'onclick'        => 'return false;',
				'menu_class'     => 'gv_connected_forms gf_form_toolbar_settings',
				'sub_menu_items' => $sub_menu_items,
				'priority'       => 0,
				'capabilities'   => array( 'edit_gravityviews' ),
			);
		}

		return $menu_items;
	}

	/**
	 * List the field types without presentation properties (on a View context)
	 *
	 * @param array $array Existing field types to add to a blacklist
	 * @param string|null $context Context for the blacklist. Default: NULL.
	 * @access public
	 * @return array Default blacklist fields merged with existing blacklist fields
	 */
	function default_field_blacklist( $array = array(), $context = NULL ) {

		$add = array( 'captcha', 'page' );

		// Don't allowing editing the following values:
		if( $context === 'edit' ) {
			$add[] = 'post_id';
		}

		$return = array_merge( $array, $add );

		return $return;
	}

	/**
	 * Add tooltip text for use throughout the UI
	 * @param  array       $tooltips Array of Gravity Forms tooltips
	 * @return array                Modified tooltips array
	 */
	public function tooltips( $tooltips = array() ) {

		$gv_tooltips = array();

		// Generate tooltips for View settings
		$default_args = defined( 'GRAVITYVIEW_FUTURE_CORE_LOADED' ) ? \GV\View_Settings::defaults( true ) : GravityView_View_Data::get_default_args( true );

		foreach ( $default_args as $key => $arg ) {

			// If an arg has `tooltip` defined, but it's false, don't display a tooltip
			if( isset( $arg['tooltip'] ) && empty( $arg['tooltip'] ) ) { continue; }

			// By default, use `tooltip` if defined.
			$tooltip = empty( $arg['tooltip'] ) ? NULL : $arg['tooltip'];

			// Otherwise, use the description as a tooltip.
			if( empty( $tooltip ) && !empty( $arg['desc'] ) ) {
				$tooltip = $arg['desc'];
			}

			// If there's no tooltip set, continue
			if( empty( $tooltip ) ) {
				continue;
			}

			// Add the tooltip
			$gv_tooltips[ 'gv_'.$key ] = array(
				'title'	=> $arg['label'],
				'value'	=> $tooltip,
			);

		}

		$gv_tooltips['gv_css_merge_tags'] = array(
			'title' => __('CSS Merge Tags', 'gravityview'),
			'value' => sprintf( __( 'Developers: The CSS classes will be sanitized using the %ssanitize_title_with_dashes()%s function.', 'gravityview'), '<code>', '</code>' )
		);

		/**
		 * @filter `gravityview_tooltips` The tooltips GravityView adds to the Gravity Forms tooltip array
		 * @param array $gv_tooltips Associative array with unique keys containing array of `title` and `value` keys, as expected by `gform_tooltips` filter
		 */
		$gv_tooltips = apply_filters( 'gravityview_tooltips', $gv_tooltips );

		foreach ( $gv_tooltips as $key => $tooltip ) {

			$title = empty( $tooltip['title'] ) ? '' : '<h6>'.esc_html( $tooltip['title'] ) .'</h6>';

			$tooltips[ $key ] = $title . wpautop( esc_html( $tooltip['value'] ) );
		}

		return $tooltips;
	}

	/**
	 * Add the Data Source information
	 *
	 * @param null $column_name
	 * @param $post_id
	 *
	 * @return void
	 */
	public function add_custom_column_content( $column_name = NULL, $post_id )	{

		$output = '';

		switch ( $column_name ) {
			case 'gv_template':

				$template_id = gravityview_get_template_id( $post_id );

				// All Views should have a connected form. If it doesn't, that's not right.
				if ( empty( $template_id ) ) {
					do_action( 'gravityview_log_error', sprintf( __METHOD__ . ' View ID %s does not have a connected template.', $post_id ) );
					break;
				}

				$templates = gravityview_get_registered_templates();

				$template = isset( $templates[ $template_id ] ) ? $templates[ $template_id ] : false;

				// Generate backup if label doesn't exist: `example_name` => `Example Name`
				$template_id_pretty = ucwords( implode( ' ', explode( '_', $template_id ) ) );

				$output = $template ? $template['label'] : $template_id_pretty;

				break;

			case 'gv_connected_form':

				$form_id = gravityview_get_form_id( $post_id );

				// All Views should have a connected form. If it doesn't, that's not right.
				if ( empty( $form_id ) ) {
					do_action( 'gravityview_log_error', sprintf( '[add_data_source_column_content] View ID %s does not have a connected GF form.', $post_id ) );
					$output = __( 'Not connected.', 'gravityview' );
					break;
				}

				$form = gravityview_get_form( $form_id );

				if ( ! $form ) {
					do_action( 'gravityview_log_error', sprintf( '[add_data_source_column_content] Connected form not found: Form #%d', $form_id ) );

					$output = __( 'The connected form can not be found; it may no longer exist.', 'gravityview' );
				} else {
					$output = self::get_connected_form_links( $form );
				}

				break;
		}

		echo $output;
	}


	/**
	 * Get HTML links relating to a connected form, like Edit, Entries, Settings, Preview
	 * @param  array|int $form Gravity Forms forms array, or the form ID
	 * @param  boolean $include_form_link Whether to include the bold name of the form in the output
	 * @return string          HTML links
	 */
	static public function get_connected_form_links( $form, $include_form_link = true ) {

		// Either the form is empty or the form ID is 0, not yet set.
		if( empty( $form ) ) {
			return '';
		}

		// The $form is passed as the form ID
		if( !is_array( $form ) ) {
			$form = gravityview_get_form( $form );
		}

		$form_id = $form['id'];
		$links = array();

		if( GVCommon::has_cap( 'gravityforms_edit_forms' ) ) {
			$form_url = admin_url( sprintf( 'admin.php?page=gf_edit_forms&amp;id=%d', $form_id ) );
			$form_link = '<strong class="gv-form-title">'.gravityview_get_link( $form_url, $form['title'], 'class=row-title' ).'</strong>';
			$links[] = '<span>'.gravityview_get_link( $form_url, __('Edit Form', 'gravityview') ).'</span>';
		} else {
			$form_link = '<strong class="gv-form-title">'. esc_html( $form['title'] ). '</strong>';
		}

		if( GVCommon::has_cap( 'gravityforms_view_entries' ) ) {
			$entries_url = admin_url( sprintf( 'admin.php?page=gf_entries&amp;id=%d', $form_id ) );
			$links[] = '<span>'.gravityview_get_link( $entries_url, __('Entries', 'gravityview') ).'</span>';
		}

		if( GVCommon::has_cap( array( 'gravityforms_edit_settings', 'gravityview_view_settings' ) ) ) {
			$settings_url = admin_url( sprintf( 'admin.php?page=gf_edit_forms&amp;view=settings&amp;id=%d', $form_id ) );
			$links[] = '<span>'.gravityview_get_link( $settings_url, __('Settings', 'gravityview'), 'title='.__('Edit settings for this form', 'gravityview') ).'</span>';
		}

		if( GVCommon::has_cap( array("gravityforms_edit_forms", "gravityforms_create_form", "gravityforms_preview_forms") ) ) {
			$preview_url = site_url( sprintf( '?gf_page=preview&amp;id=%d', $form_id ) );
			$links[] = '<span>'.gravityview_get_link( $preview_url, __('Preview Form', 'gravityview'), 'title='.__('Preview this form', 'gravityview') ).'</span>';
		}

		$output = '';

		if( !empty( $include_form_link ) ) {
			$output .= $form_link;
		}

		/**
		 * @filter `gravityview_connected_form_links` Modify the links shown in the Connected Form links
		 * @since 1.6
		 * @param array $links Links to show
		 * @param array $form Gravity Forms form array
		 */
		$links = apply_filters( 'gravityview_connected_form_links', $links, $form );

		$output .= '<div class="row-actions">'. implode( ' | ', $links ) .'</div>';

		return $output;
	}

	/**
	 * Add the Data Source column to the Views page
	 * @param  array      $columns Columns array
	 */
	public function add_post_type_columns( $columns ) {

		// Get the date column and save it for later to add back in.
		// This adds it after the Data Source column.
		// This way, we don't need to do array_slice, array_merge, etc.
		$date = $columns['date'];
		unset( $columns['date'] );

		$data_source_required_caps = array(
			'gravityforms_edit_forms',
			'gravityforms_view_entries',
			'gravityforms_edit_settings',
			'gravityview_view_settings',
			'gravityforms_create_form',
			'gravityforms_preview_forms',
		);

		if( GVCommon::has_cap( $data_source_required_caps ) ) {
			$columns['gv_connected_form'] = __( 'Data Source', 'gravityview' );
		}

		$columns['gv_template'] = _x( 'Template', 'Column title that shows what template is being used for Views', 'gravityview' );

		// Add the date back in.
		$columns['date'] = $date;

		return $columns;
	}

	/**
	 * Save View configuration
	 *
	 * @access public
	 * @param int $post_id Currently saved Post ID
	 * @return void
	 */
	function save_postdata( $post_id ) {

		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
			return;
		}

		// validate post_type
		if ( ! isset( $_POST['post_type'] ) || 'gravityview' != $_POST['post_type'] ) {
			return;
		}

		// validate user can edit and save View
		if ( ! GVCommon::has_cap( 'edit_gravityview', $post_id ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ' - Current user does not have the capability to edit View #' . $post_id, wp_get_current_user() );
			return;
		}

		do_action( 'gravityview_log_debug', '[save_postdata] Saving View post type.', $_POST );

		$statii = array();

		// check if this is a start fresh View
		if ( isset( $_POST['gravityview_select_form_nonce'] ) && wp_verify_nonce( $_POST['gravityview_select_form_nonce'], 'gravityview_select_form' ) ) {

			$form_id = !empty( $_POST['gravityview_form_id'] ) ? $_POST['gravityview_form_id'] : '';
			// save form id
			$statii['form_id'] = update_post_meta( $post_id, '_gravityview_form_id', $form_id );

		}

		if( false === GVCommon::has_cap( 'gravityforms_create_form' ) && empty( $statii['form_id'] ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ' - Current user does not have the capability to create a new Form.', wp_get_current_user() );
			return;
		}

		// Was this a start fresh?
		if ( ! empty( $_POST['gravityview_form_id_start_fresh'] ) ) {
			$statii['start_fresh'] = add_post_meta( $post_id, '_gravityview_start_fresh', 1 );
		} else {
			$statii['start_fresh'] = delete_post_meta( $post_id, '_gravityview_start_fresh' );
		}

		// Check if we have a template id
		if ( isset( $_POST['gravityview_select_template_nonce'] ) && wp_verify_nonce( $_POST['gravityview_select_template_nonce'], 'gravityview_select_template' ) ) {

			$template_id = !empty( $_POST['gravityview_directory_template'] ) ? $_POST['gravityview_directory_template'] : '';

			// now save template id
			$statii['directory_template'] = update_post_meta( $post_id, '_gravityview_directory_template', $template_id );
		}


		// save View Configuration metabox
		if ( isset( $_POST['gravityview_view_configuration_nonce'] ) && wp_verify_nonce( $_POST['gravityview_view_configuration_nonce'], 'gravityview_view_configuration' ) ) {

			// template settings
			if( empty( $_POST['template_settings'] ) ) {
				$_POST['template_settings'] = array();
			}
			$statii['template_settings'] = update_post_meta( $post_id, '_gravityview_template_settings', $_POST['template_settings'] );

			$fields = array();

			// Directory&single Visible Fields
			if( !empty( $preset_fields ) ) {

				$fields = $preset_fields;

			} elseif( !empty( $_POST['gv_fields'] ) ) {
				$fields = _gravityview_process_posted_fields();
			}

			$statii['directory_fields'] = update_post_meta( $post_id, '_gravityview_directory_fields', $fields );

			// Directory Visible Widgets
			if( empty( $_POST['widgets'] ) ) {
				$_POST['widgets'] = array();
			}
			$statii['directory_widgets'] = gravityview_set_directory_widgets( $post_id, $_POST['widgets'] );

		} // end save view configuration

		/**
		 * @action `gravityview_view_saved` After a View has been saved in the admin
		 * @param int $post_id ID of the View that has been saved
		 * @param array $statii Array of statuses of the post meta saving processes. If saving worked, each key should be mapped to a value of the post ID (`directory_widgets` => `124`). If failed (or didn't change), the value will be false.
		 * @since 1.17.2
		 */
		do_action('gravityview_view_saved', $post_id, $statii );

		do_action('gravityview_log_debug', '[save_postdata] Update Post Meta Statuses (also returns false if nothing changed)', array_map( 'intval', $statii ) );
	}

	/**
	 * @deprecated 1.1.6
	 */
	function render_label() {
		_deprecated_function( 'GravityView_Admin_Views::render_label()', '1.1.6', 'Use the GravityView_Admin_View_Field class instead.' );
	}

	/**
	 * Render html for displaying available fields based on a Form ID
	 * $blacklist_field_types - contains the field types which are not proper to be shown in a directory.
	 *
     * @see GravityView_Ajax::get_available_fields_html() Triggers `gravityview_render_available_fields` action
	 * @access public
     *
	 * @param int $form Gravity Forms Form ID (default: '')
	 * @param string $context (default: 'single')
     *
	 * @return void
	 */
	function render_available_fields( $form = 0, $context = 'single' ) {

		/**
		 * @filter  `gravityview_blacklist_field_types` Modify the types of fields that shouldn't be shown in a View.
		 * @param[in,out] array $blacklist_field_types Array of field types to block for this context.
		 * @param[in] string $context View context ('single', 'directory', or 'edit')
		 */
		$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array(), $context );

		if ( ! is_array( $blacklist_field_types ) ) {

		    do_action( 'gravityview_log_error', __METHOD__ . ': $blacklist_field_types is not an array', print_r( $blacklist_field_types, true ) );

			$blacklist_field_types = array();
		}

		$fields = $this->get_available_fields( $form, $context );

		$output = '';

		if( !empty( $fields ) ) {

			foreach( $fields as $id => $details ) {

				if( in_array( $details['type'], (array) $blacklist_field_types ) ) {
					continue;
				}

				// Edit mode only allows editing the parent fields, not single inputs.
				if( $context === 'edit' && !empty( $details['parent'] ) ) {
					continue;
				}

				$output .= new GravityView_Admin_View_Field( $details['label'], $id, $details );

			} // End foreach
		}

		echo $output;

		// For the EDIT view we only want to allow the form fields.
		if( $context === 'edit' ) {
			return;
		}

		$this->render_additional_fields( $form, $context );
	}

	/**
	 * Render html for displaying additional fields based on a Form ID
	 *
	 * @access public
	 * @param int $form Gravity Forms Form ID (default: '')
	 * @param string $context (default: 'single')
	 * @return void
	 */
	public function render_additional_fields( $form = 0, $context = 'single' ) {

		/**
		 * @filter `gravityview_additional_fields` non-standard Fields to show at the bottom of the field picker
		 * @param array $additional_fields Associative array of field arrays, with `label_text`, `desc`, `field_id`, `label_type`, `input_type`, `field_options`, and `settings_html` keys
		 */
		$additional_fields = apply_filters( 'gravityview_additional_fields', array(
			array(
				'label_text' => __( '+ Add All Fields', 'gravityview' ),
				'desc' => __('Add all the available fields at once.', 'gravityview'),
				'field_id' => 'all-fields',
				'label_type' => 'field',
				'input_type' => NULL,
				'field_options' => NULL,
				'settings_html'	=> NULL,
			)
		));

		if( !empty( $additional_fields )) {
			foreach ( (array)$additional_fields as $item ) {

				// Prevent items from not having index set
				$item = wp_parse_args( $item, array(
					'label_text' => NULL,
					'field_id' => NULL,
					'label_type' => NULL,
					'input_type' => NULL,
					'field_options' => NULL,
					'settings_html'	=> NULL,
				));

				// Backward compat.
				if( !empty( $item['field_options'] ) ) {
					// Use settings_html from now on.
					$item['settings_html'] = $item['field_options'];
				}

				// Render a label for each of them
				echo new GravityView_Admin_View_Field( $item['label_text'], $item['field_id'], $item );

			}
		}

	}

	/**
	 * Retrieve the default fields id, label and type
	 * @param  string|array $form form_ID or form object
	 * @param  string $zone   Either 'single', 'directory', 'header', 'footer'
	 * @return array
	 */
	function get_entry_default_fields($form, $zone) {

		$entry_default_fields = array();

		if( in_array( $zone, array( 'directory', 'single' ) ) ) {

			$entry_default_fields = array(
				'id' => array(
					'label' => __('Entry ID', 'gravityview'),
					'type' => 'id',
					'desc'	=> __('The unique ID of the entry.', 'gravityview'),
				),
				'date_created' => array(
					'label' => __('Entry Date', 'gravityview'),
					'desc'	=> __('The date the entry was created.', 'gravityview'),
					'type' => 'date_created',
				),
				'source_url' => array(
					'label' => __('Source URL', 'gravityview'),
					'type' => 'source_url',
					'desc'	=> __('The URL of the page where the form was submitted.', 'gravityview'),
				),
				'ip' => array(
					'label' => __('User IP', 'gravityview'),
					'type' => 'ip',
					'desc'	=> __('The IP Address of the user who created the entry.', 'gravityview'),
				),
				'created_by' => array(
					'label' => __('User', 'gravityview'),
					'type' => 'created_by',
					'desc'	=> __('Details of the logged-in user who created the entry (if any).', 'gravityview'),
				),

				/**
				 * @since 1.7.2
				 */
			    'other_entries' => array(
				    'label'	=> __('Other Entries', 'gravityview'),
				    'type'	=> 'other_entries',
				    'desc'	=> __('Display other entries created by the entry creator.', 'gravityview'),
			    ),
	        );

			if( 'single' !== $zone) {

				$entry_default_fields['entry_link'] = array(
					'label' => __('Link to Entry', 'gravityview'),
					'desc'	=> __('A dedicated link to the single entry with customizable text.', 'gravityview'),
					'type' => 'entry_link',
				);
			}

		} // if not zone directory or single

		/**
		 * @since  1.2
		 */
		$entry_default_fields['custom']	= array(
			'label'	=> __('Custom Content', 'gravityview'),
			'type'	=> 'custom',
			'desc'	=> __('Insert custom text or HTML.', 'gravityview'),
		);

		/**
		 * @filter `gravityview_entry_default_fields` Modify the default fields for each zone and context
		 * @param array $entry_default_fields Array of fields shown by default
		 * @param  string|array $form form_ID or form object
		 * @param  string $zone   Either 'single', 'directory', 'header', 'footer'
		 */
		return apply_filters( 'gravityview_entry_default_fields', $entry_default_fields, $form, $zone);
	}

	/**
	 * Calculate the available fields
	 * @param  string|array $form form_ID or form object
	 * @param  string $zone   Either 'single', 'directory', 'header', 'footer'
	 * @return array         fields
	 */
	function get_available_fields( $form = '', $zone = NULL ) {

		if( empty( $form ) ) {
			do_action( 'gravityview_log_error', '[get_available_fields] $form is empty' );
			return array();
		}

		// get form fields
		$fields = gravityview_get_form_fields( $form, true );

		// get meta fields ( only if form was already created )
		if( !is_array( $form ) ) {
			$meta_fields = gravityview_get_entry_meta( $form );
		} else {
			$meta_fields = array();
		}

		// get default fields
		$default_fields = $this->get_entry_default_fields( $form, $zone );

		//merge without loosing the keys
		$fields = $fields + $meta_fields + $default_fields;

		return $fields;
	}


	/**
	 * Render html for displaying available widgets
	 * @return string html
	 */
	function render_available_widgets() {

		$widgets = $this->get_registered_widgets();

		if( !empty( $widgets ) ) {

			foreach( $widgets as $id => $details ) {

				echo new GravityView_Admin_View_Widget( $details['label'], $id, $details );

			}
		}

	}

	/**
	 * Get the list of registered widgets. Each item is used to instantiate a GravityView_Admin_View_Widget object
	 * @since 1.13.1
	 * @return array
	 */
	function get_registered_widgets() {
		/**
		 * @filter `gravityview_register_directory_widgets` Get the list of registered widgets. Each item is used to instantiate a GravityView_Admin_View_Widget object
		 * @param array $registered_widgets Empty array
		 */
		$registered_widgets = apply_filters( 'gravityview_register_directory_widgets', array() );

		return $registered_widgets;
	}

	/**
	 * Generic function to render rows and columns of active areas for widgets & fields
	 * @param  string $template_id The current slug of the selected View template
	 * @param  string $type   Either 'widget' or 'field'
	 * @param  string $zone   Either 'single', 'directory', 'header', 'footer'
	 * @param  array $rows    The layout structure: rows, columns and areas
	 * @param  array $values  Saved objects
	 * @return void
	 */
	function render_active_areas( $template_id, $type, $zone, $rows, $values ) {
		global $post;

		if( $type === 'widget' ) {
			$button_label = __( 'Add Widget', 'gravityview' );
		} else {
			$button_label = __( 'Add Field', 'gravityview' );
		}

		$available_items = array();

		// if saved values, get available fields to label everyone
		if( !empty( $values ) && ( !empty( $post->ID ) || !empty( $_POST['template_id'] ) ) ) {

			if( !empty( $_POST['template_id'] ) ) {
				$form = GravityView_Ajax::pre_get_form_fields( $_POST['template_id'] );
			} else {
				$form = gravityview_get_form_id( $post->ID );
			}

			if( 'field' === $type ) {
				$available_items = $this->get_available_fields( $form, $zone );
			} else {
				$available_items = $this->get_registered_widgets();
			}

		}

		foreach( $rows as $row ) :
			foreach( $row as $col => $areas ) :
				$column = ($col == '2-2') ? '1-2' : $col; ?>

				<div class="gv-grid-col-<?php echo esc_attr( $column ); ?>">

					<?php foreach( $areas as $area ) : 	?>

						<div class="gv-droppable-area">
							<div class="active-drop active-drop-<?php echo esc_attr( $type ); ?>" data-areaid="<?php echo esc_attr( $zone .'_'. $area['areaid'] ); ?>">

								<?php // render saved fields

								if( !empty( $values[ $zone .'_'. $area['areaid'] ] ) ) {

									foreach( $values[ $zone .'_'. $area['areaid'] ] as $uniqid => $field ) {

										$input_type = NULL;
										$original_item = isset( $available_items[ $field['id'] ] ) ? $available_items[ $field['id'] ] : false ;

										if( !$original_item ) {

											do_action('gravityview_log_error', 'An item was not available when rendering the output; maybe it was added by a plugin that is now de-activated.', array('available_items' => $available_items, 'field' => $field ));

											$original_item = $field;
										} else {

											$input_type = isset( $original_item['type'] ) ? $original_item['type'] : NULL;

										}

										// Field options dialog box
										$field_options = GravityView_Render_Settings::render_field_options( $type, $template_id, $field['id'], $original_item['label'], $zone .'_'. $area['areaid'], $input_type, $uniqid, $field, $zone, $original_item );

										$item = array(
											'input_type' => $input_type,
											'settings_html' => $field_options,
											'label_type' => $type
										);

										// Merge the values with the current item to pass things like widget descriptions and original field names
										if( $original_item ) {
											$item = wp_parse_args( $item, $original_item );
										}

										switch( $type ) {
											case 'widget':
												echo new GravityView_Admin_View_Widget( $item['label'], $field['id'], $item, $field );
												break;
											default:
												echo new GravityView_Admin_View_Field( $item['label'], $field['id'], $item, $field );
										}


										//endif;

									}

								} // End if zone is not empty ?>

								<span class="drop-message"><?php echo sprintf(esc_attr__('"+ %s" or drag existing %ss here.', 'gravityview'), $button_label, $type ); ?></span>
							</div>
							<div class="gv-droppable-area-action">
								<a href="#" class="gv-add-field button-secondary" title="" data-objecttype="<?php echo esc_attr( $type ); ?>" data-areaid="<?php echo esc_attr( $zone .'_'. $area['areaid'] ); ?>" data-context="<?php echo esc_attr( $zone ); ?>"><?php echo '+ '.esc_html( $button_label ); ?></a>
								<p class="gv-droppable-area-title"><strong><?php echo esc_html( $area['title'] ); ?></strong><?php if( !empty( $area['subtitle'] ) ) { ?><span class="gv-droppable-area-subtitle"> &ndash; <?php echo esc_html( $area['subtitle'] ); ?></span><?php } ?></p>
							</div>
						</div>

					<?php endforeach; ?>

				</div>
			<?php endforeach;
		endforeach;
	}

	/**
	 * Render the widget active areas
	 * @param  string $zone    Either 'header' or 'footer'
	 * @param  string $post_id Current Post ID (view)
	 * @return string          html
	 */
	function render_widgets_active_areas( $template_id = '', $zone, $post_id = '' ) {

		$default_widget_areas = GravityView_Plugin::get_default_widget_areas();

		$widgets = array();
		if( !empty( $post_id ) ) {
			$widgets = gravityview_get_directory_widgets( $post_id );
		}

		ob_start();
		?>

		<div class="gv-grid gv-grid-pad gv-grid-border" id="directory-<?php echo $zone; ?>-widgets">
			<?php $this->render_active_areas( $template_id, 'widget', $zone, $default_widget_areas, $widgets ); ?>
		</div>

		<?php
		$output = ob_get_clean();

		echo $output;

		return $output;
	}

	/**
	 * Render the Template Active Areas and configured active fields for a given template id and post id
	 *
	 * @access public
	 * @param string $template_id (default: '') Template ID, like `default_list`, `default_table`, `preset_business_data`, etc. {@see GravityView_Template::__construct()}
	 * @param string $post_id (default: '')
	 * @param string $context (default: 'single')
	 * @return string HTML of the active areas
	 */
	function render_directory_active_areas( $template_id = '', $context = 'single', $post_id = '', $echo = false ) {

		if( empty( $template_id ) ) {
			do_action( 'gravityview_log_debug', '[render_directory_active_areas] $template_id is empty' );
			return '';
		}

		/**
		 * @filter `gravityview_template_active_areas` 
		 * @see GravityView_Template::assign_active_areas()
		 * @param array $template_areas Empty array, to be filled in by the template class
		 * @param string $template_id Template ID, like `default_list`, `default_table`, `preset_business_data`, etc. {@see GravityView_Template::__construct()}
		 * @param string $context Current View context: `directory`, `single`, or `edit` (default: 'single')
		 */
		$template_areas = apply_filters( 'gravityview_template_active_areas', array(), $template_id, $context );

		if( empty( $template_areas ) ) {

			do_action( 'gravityview_log_debug', '[render_directory_active_areas] No areas defined. Maybe template %s is disabled.', $template_id );
			$output = '<div>';
			$output .= '<h2 class="description" style="font-size: 16px; margin:0">'. sprintf( esc_html__( 'This View is configured using the %s View type, which is disabled.', 'gravityview' ), '<em>'.$template_id.'</em>' ) .'</h2>';
			$output .= '<p class="description" style="font-size: 14px; margin:0 0 1em 0;padding:0">'.esc_html__('The data is not lost; re-activate the associated plugin and the configuration will re-appear.', 'gravityview').'</p>';
			$output .= '</div>';
		} else {

			$fields = '';
			if ( ! empty( $post_id ) ) {
				$fields = gravityview_get_directory_fields( $post_id );
			}

			ob_start();
			$this->render_active_areas( $template_id, 'field', $context, $template_areas, $fields );
			$output = ob_get_clean();

		}

		if( $echo ) {
			echo $output;
		}

		return $output;
	}

	/**
	 * Enqueue scripts and styles at Views editor
	 *
	 * @access public
	 * @param mixed $hook
	 * @return void
	 */
	static function add_scripts_and_styles( $hook ) {
		global $plugin_page, $pagenow;

		$is_widgets_page = ( $pagenow === 'widgets.php' );

		// Add the GV font (with the Astronaut)
		wp_enqueue_style( 'gravityview_global', plugins_url('assets/css/admin-global.css', GRAVITYVIEW_FILE), array(), GravityView_Plugin::version );

		wp_register_script( 'gravityview-jquery-cookie', plugins_url('assets/lib/jquery.cookie/jquery.cookie.min.js', GRAVITYVIEW_FILE), array( 'jquery' ), GravityView_Plugin::version, true );

		// Don't process any scripts below here if it's not a GravityView page.
		if( ! gravityview_is_admin_page( $hook, 'single' ) && ! $is_widgets_page ) {
		    return;
		}

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'gravityview_views_datepicker', plugins_url('assets/css/admin-datepicker.css', GRAVITYVIEW_FILE), GravityView_Plugin::version );

        $script_debug = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

        //enqueue scripts
        wp_enqueue_script( 'gravityview_views_scripts', plugins_url( 'assets/js/admin-views' . $script_debug . '.js', GRAVITYVIEW_FILE ), array( 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'jquery-ui-tooltip', 'jquery-ui-dialog', 'gravityview-jquery-cookie', 'jquery-ui-datepicker', 'underscore' ), GravityView_Plugin::version );

        wp_localize_script('gravityview_views_scripts', 'gvGlobals', array(
            'cookiepath' => COOKIEPATH,
            'passed_form_id' => (bool) rgget( 'form_id' ),
            'nonce' => wp_create_nonce( 'gravityview_ajaxviews' ),
            'label_viewname' => __( 'Enter View name here', 'gravityview' ),
            'label_close' => __( 'Close', 'gravityview' ),
            'label_cancel' => __( 'Cancel', 'gravityview' ),
            'label_continue' => __( 'Continue', 'gravityview' ),
            'label_ok' => __( 'Ok', 'gravityview' ),
            'label_publisherror' => __( 'Error while creating the View for you. Check the settings or contact GravityView support.', 'gravityview' ),
            'loading_text' => esc_html__( 'Loading&hellip;', 'gravityview' ),
            'loading_error' => esc_html__( 'There was an error loading dynamic content.', 'gravityview' ),
            'field_loaderror' => __( 'Error while adding the field. Please try again or contact GravityView support.', 'gravityview' ),
            'remove_all_fields' => __( 'Would you like to remove all fields in this zone? (You are seeing this message because you were holding down the ALT key)', 'gravityview' ),
        ));

        wp_enqueue_style( 'gravityview_views_styles', plugins_url( 'assets/css/admin-views.css', GRAVITYVIEW_FILE ), array('dashicons', 'wp-jquery-ui-dialog' ), GravityView_Plugin::version );

        // Enqueue scripts needed for merge tags
        self::enqueue_gravity_forms_scripts();
	}

	/**
	 * Enqueue Gravity Forms scripts, needed for Merge Tags
     *
     * @since 1.0.5-beta
     *
     * @return void
	 */
	static function enqueue_gravity_forms_scripts() {
		GFForms::register_scripts();

		$scripts = array(
		    'sack',
		    'gform_gravityforms',
		    'gform_forms',
		    'gform_form_admin',
		    'jquery-ui-autocomplete'
		);

		if ( wp_is_mobile() ) {
		    $scripts[] = 'jquery-touch-punch';
		}

		wp_enqueue_script( $scripts );
	}

	/**
	 * Add GravityView scripts and styles to Gravity Forms and GravityView No-Conflict modes
	 *
	 * @param array $registered Existing scripts or styles that have been registered (array of the handles)
	 *
	 * @return array
	 */
	function register_no_conflict( $registered ) {

		$allowed_dependencies = array();

		$filter = current_filter();

		if ( preg_match( '/script/ism', $filter ) ) {

			$allowed_dependencies = array(
				'jquery-ui-core',
				'jquery-ui-dialog',
				'jquery-ui-tabs',
				'jquery-ui-draggable',
				'jquery-ui-droppable',
				'jquery-ui-sortable',
				'jquery-ui-tooltip',
				'gravityview_views_scripts',
				'gravityview-support',
				'gravityview-jquery-cookie',
				'gravityview_views_datepicker',
				'sack',
				'gform_gravityforms',
				'gform_forms',
				'gform_form_admin',
				'jquery-ui-autocomplete'
			);
			
		} elseif ( preg_match( '/style/ism', $filter ) ) {

			$allowed_dependencies = array(
				'dashicons',
				'wp-jquery-ui-dialog',
				'gravityview_views_styles',
				'gravityview_global',
				'gravityview_views_datepicker'
			);
		}

		return array_merge( $registered, $allowed_dependencies );
	}


}

new GravityView_Admin_Views;
