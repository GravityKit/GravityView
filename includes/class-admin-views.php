<?php
/**
 * Renders all the metaboxes on Add New / Edit View post type.
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
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
		add_action( 'gravityview_render_field_pickers', array( $this, 'render_field_pickers'), 10, 2 );
		add_action( 'gravityview_render_available_fields', array( $this, 'render_available_fields'), 10, 2 );
		add_action( 'gravityview_render_available_widgets', array( $this, 'render_available_widgets') );
		add_action( 'gravityview_render_active_areas', array( $this, 'render_active_areas'), 10, 5 );

		// @todo check if this hook is needed..
		//add_action( 'gravityview_render_field_options', array( $this, 'render_field_options'), 10, 9 );

		// Add Connected Form column
		add_filter('manage_gravityview_posts_columns' , array( $this, 'add_post_type_columns' ) );

		add_filter( 'gform_toolbar_menu', array( 'GravityView_Admin_Views', 'gform_toolbar_menu' ), 10, 2 );
		add_action( 'gform_form_actions', array( 'GravityView_Admin_Views', 'gform_toolbar_menu' ), 10, 2 );

		add_action( 'manage_gravityview_posts_custom_column', array( $this, 'add_custom_column_content'), 10, 2 );

		add_action( 'restrict_manage_posts', array( $this, 'add_view_dropdown' ) );

		add_action( 'pre_get_posts', array( $this, 'filter_pre_get_posts' ) );

		add_filter( 'gravityview/support_port/localization_data', array( $this, 'suggest_support_articles' ) );
	}

	/**
     * When on the Add/Edit View screen, suggest most popular articles related to that
     *
	 * @param array $localization_data Data to be passed to the Support Port JS
	 *
	 * @return array
	 */
	function suggest_support_articles( $localization_data = array() ) {

	    if( ! gravityview()->request->is_view() ) {
	        return $localization_data;
        }

		$localization_data['suggest'] = array(
            '57ef23539033602e61d4a560',
            '54c67bb9e4b0512429885513',
            '54c67bb9e4b0512429885512',
            '54c67bbbe4b07997ea3f3f6b',
            '54d1a33ae4b086c0c0964ce9',
            '57ef253c9033602e61d4a563',
            '552355bfe4b0221aadf2572b',
            '54c67bcde4b051242988553e',
        );

		return $localization_data;
	}

	/**
	 * @since 1.15
	 * @param WP_Query $query
	 */
	public function filter_pre_get_posts( &$query ) {
		global $pagenow;

		if ( ! is_admin() ) {
			return;
		}

		if ( 'edit.php' !== $pagenow ) {
			return;
		}

		if ( ! isset( $query->query_vars['post_type'] ) ) {
			return;
		}

		if ( 'gravityview' !== $query->query_vars['post_type'] ) {
			return;
		}

		$form_id = (int) \GV\Utils::_GET( 'gravityview_form_id' );

		$meta_query = array();

		if ( $form_id ) {
			$meta_query[] = array(
					'key'   => '_gravityview_form_id',
					'value' => $form_id,
			);
		}

		$layout_id = \GV\Utils::_GET( 'gravityview_layout' );

		if ( $layout_id ) {
			$meta_query[] = array(
					'key'   => '_gravityview_directory_template',
					'value' => esc_attr( $layout_id ),
			);
		}

		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Adds dropdown selects to filter Views by connected form and layout
	 *
	 * @return void
	 */
	public function add_view_dropdown() {
		$current_screen = get_current_screen();

		if( 'gravityview' !== $current_screen->post_type ) {
			return;
		}

		$forms = gravityview_get_forms();
		$current_form = \GV\Utils::_GET( 'gravityview_form_id' );

		// If there are no forms to select, show no forms.
		if( ! empty( $forms ) ) { ?>
			<label for="gravityview_form_id" class="screen-reader-text"><?php esc_html_e( 'Filter Views by form', 'gravityview' ); ?></label>
			<select name="gravityview_form_id" id="gravityview_form_id">
				<option value="" <?php selected( '', $current_form, true ); ?>><?php esc_html_e( 'All forms', 'gravityview' ); ?></option>
				<?php foreach( $forms as $form ) { ?>
					<option value="<?php echo esc_attr( $form['id'] ); ?>" <?php selected( $form['id'], $current_form, true ); ?>><?php echo esc_html( $form['title'] ); ?></option>
				<?php } ?>
			</select>
		<?php }

		$layouts = gravityview_get_registered_templates();
		$current_layout = \GV\Utils::_GET( 'gravityview_layout' );

		// If there are no forms to select, show no forms.
		if( ! empty( $layouts ) ) { ?>
			<label for="gravityview_layout_name" class="screen-reader-text"><?php esc_html_e( 'Filter Views by layout', 'gravityview' ); ?></label>
			<select name="gravityview_layout" id="gravityview_layout_name">
				<option value="" <?php selected( '', $current_layout, true ); ?>><?php esc_html_e( 'All layouts', 'gravityview' ); ?></option>
				<optgroup label="<?php esc_html_e( 'Layouts', 'gravityview' ); ?>">
				<?php foreach( $layouts as $layout_id => $layout ) {
					if ( in_array( $layout['type'], array( 'preset', 'internal' ), true ) ) {
						continue;
					}
					?>
					<option value="<?php echo esc_attr( $layout_id ); ?>" <?php selected( $layout_id, $current_layout, true ); ?>><?php echo esc_html( $layout['label'] ); ?></option>
				<?php } ?>
				</optgroup>
				<optgroup label="<?php esc_html_e( 'Form Presets', 'gravityview' ); ?>">
				<?php foreach( $layouts as $layout_id => $layout ) {
					if ( ! in_array( $layout['type'], array( 'preset' ), true ) ) {
						continue;
					}
					?>
					<option value="<?php echo esc_attr( $layout_id ); ?>" <?php selected( $layout_id, $current_layout, true ); ?>><?php echo esc_html( $layout['label'] ); ?></option>
				<?php } ?>
				</optgroup>
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
	public static function gform_toolbar_menu( $menu_items = array(), $id = NULL ) {

		// Don't show on Trashed forms
		if ( 'trash' === rgget( 'filter' ) ) {
			return $menu_items;
		}

		$connected_views = gravityview_get_connected_views( $id, array( 'post_status' => 'any' ) );

		$priority = 0;

		if( 'form_list' === GFForms::get_page() ) {
			$priority = 790;
        }

		if( empty( $connected_views ) ) {

		    $menu_items['gravityview'] = array(
				'label'          => esc_attr__( 'Create a View', 'gravityview' ),
				'icon'           => '<i class="fa fa-lg gv-icon-astronaut-head gv-icon"></i>', // Only appears in GF pre-2.5
				'title'          => esc_attr__( 'Create a View using this form as a data source', 'gravityview' ),
				'url'            => admin_url( 'post-new.php?post_type=gravityview&form_id=' . $id ),
				'menu_class'     => 'gv_connected_forms gf_form_toolbar_settings',
				'priority'       => $priority,
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
				'icon' => '<i class="fa fa-lg gv-icon-astronaut-head gv-icon"></i>',
			);
		}

		// If there were no items added, then let's create the parent menu
		if( $sub_menu_items ) {

		    $sub_menu_items[] = array(
			    'label' => esc_attr__( 'Create a View', 'gravityview' ),
			    'icon' => '<span class="dashicons dashicons-plus"></span>',
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
				'priority'       => $priority,
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
		$default_args = \GV\View_Settings::defaults( true );

		foreach ( $default_args as $key => $arg ) {

			// If an arg has `tooltip` defined, but it's false, don't display a tooltip
			if( isset( $arg['tooltip'] ) && empty( $arg['tooltip'] ) ) { continue; }

			// By default, use `tooltip` if defined.
			$tooltip = empty( $arg['tooltip'] ) ? NULL : $arg['tooltip'];

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
		 * @deprecated Renamed to `gravityview/metaboxes/tooltips`
		 */
		$gv_tooltips = apply_filters( 'gravityview_tooltips', $gv_tooltips );

		/**
		 * @filter `gravityview/metaboxes/tooltips` The tooltips GravityView adds to the Gravity Forms tooltip array
		 * @param array $gv_tooltips Associative array with unique keys containing array of `title` and `value` keys, as expected by `gform_tooltips` filter
		 */
		$gv_tooltips = apply_filters( 'gravityview/metaboxes/tooltips', $gv_tooltips );

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
					gravityview()->log->error( 'View ID {view_id} does not have a connected template.', array( 'view_id' => $post_id ) );
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
					gravityview()->log->error( 'View ID {view_id} does not have a connected GF form.', array( 'view_id' => $post_id ) );
					$output = __( 'Not connected.', 'gravityview' );
					break;
				}

				$form = gravityview_get_form( $form_id );

				if ( ! $form ) {
					gravityview()->log->error( 'Connected form not found: Form #{form_id}', array( 'form_id' => $form_id ) );

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

		$css_class = 'row-actions';

		// Is Screen Options > View mode set to "Extended view"? If so, keep actions visible.
		if( 'excerpt' === get_user_setting( 'posts_list_mode', 'list' ) ) {
			$css_class = 'row-actions visible';
		}

		$output .= '<div class="' . $css_class . '">'. implode( ' | ', $links ) .'</div>';

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
			gravityview()->log->error( 'Current user does not have the capability to edit View {view_id}', array( 'view_id' => $post_id, 'data' => wp_get_current_user() ) );
			return;
		}

		gravityview()->log->debug( '[save_postdata] Saving View post type.', array( 'data' => $_POST ) );

		$statii = array();

		// check if this is a start fresh View
		if ( isset( $_POST['gravityview_select_form_nonce'] ) && wp_verify_nonce( $_POST['gravityview_select_form_nonce'], 'gravityview_select_form' ) ) {

			$form_id = !empty( $_POST['gravityview_form_id'] ) ? $_POST['gravityview_form_id'] : '';
			// save form id
			$statii['form_id'] = update_post_meta( $post_id, '_gravityview_form_id', $form_id );

		}

		if( false === GVCommon::has_cap( 'gravityforms_create_form' ) && empty( $statii['form_id'] ) ) {
			gravityview()->log->error( 'Current user does not have the capability to create a new Form.', array( 'data' => wp_get_current_user() ) );
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

			// guard against unloaded View configuration page
			if ( isset( $_POST['gv_fields'] ) && isset( $_POST['gv_fields_done'] ) ) {
				$fields = array();

				if ( ! empty( $_POST['gv_fields'] ) ) {
					$fields = _gravityview_process_posted_fields();
				}

				$fields = wp_slash( $fields );

				$statii['directory_fields'] = update_post_meta( $post_id, '_gravityview_directory_fields', $fields );
			}

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

		gravityview()->log->debug( '[save_postdata] Update Post Meta Statuses (also returns false if nothing changed)', array( 'data' => array_map( 'intval', $statii ) ) );
	}

	/**
	 * @deprecated 1.1.6
	 */
	function render_label() {
		_deprecated_function( 'GravityView_Admin_Views::render_label()', '1.1.6', 'Use the GravityView_Admin_View_Field class instead.' );
	}

	/**
	 * Render html for displaying available fields based on a Form ID
	 *
     * @see GravityView_Ajax::get_available_fields_html() Triggers `gravityview_render_available_fields` action
	 *
	 * @param int $form Gravity Forms Form ID (default: '')
	 * @param string $context (default: 'single')
     *
	 * @return void
	 */
	function render_available_fields( $form = 0, $context = 'single' ) {

	    // Determine if form is a preset and convert it to an array with fields
		$form = ( is_string( $form ) && preg_match( '/^preset_/', $form ) ) ? GravityView_Ajax::pre_get_form_fields( $form ) : $form;

		/**
		 * @filter  `gravityview_blacklist_field_types` Modify the types of fields that shouldn't be shown in a View.
		 * @param[in,out] array $blacklist_field_types Array of field types to block for this context.
		 * @param[in] string $context View context ('single', 'directory', or 'edit')
		 */
		$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array(), $context );

		if ( ! is_array( $blacklist_field_types ) ) {

		    gravityview()->log->error( '$blacklist_field_types is not an array', array( 'data' => print_r( $blacklist_field_types, true ) ) );

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
				if( $context === 'edit' && ! empty( $details['parent'] ) ) {
					continue;
				}

				$output .= new GravityView_Admin_View_Field( $details['label'], $id, $details, array(), $form );

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
	 * @param int $form Gravity Forms Form ID (default: '')
	 * @param string $context (default: 'single')
	 * @return void
	 */
	public function render_additional_fields( $form = 0, $context = 'single' ) {

		$additional_fields = array(
			array(
				'label_text' => __( 'Add All Form Fields', 'gravityview' ),
				'desc' => __('Insert all the form fields at once.', 'gravityview'),
				'field_id' => 'all-fields',
				'label_type' => 'field',
				'input_type' => null,
				'field_options' => null,
				'settings_html'	=> null,
				'icon' => 'dashicons-plus-alt',
			)
		);

		/**
		 * @filter `gravityview_additional_fields` non-standard Fields to show at the bottom of the field picker
		 * @param array $additional_fields Associative array of field arrays, with `label_text`, `desc`, `field_id`, `label_type`, `input_type`, `field_options`, and `settings_html` keys
		 */
		$additional_fields = apply_filters( 'gravityview_additional_fields', $additional_fields );

		foreach ( (array) $additional_fields as $item ) {

			// Prevent items from not having index set
			$item = wp_parse_args( $item, array(
				'label_text' => null,
				'field_id' => null,
				'label_type' => null,
				'input_type' => null,
				'field_options' => null,
				'settings_html'	=> null,
				'icon' => null,
			));

			// Backward compat.
			if( !empty( $item['field_options'] ) ) {
				// Use settings_html from now on.
				$item['settings_html'] = $item['field_options'];
			}

			// Render a label for each of them
			echo new GravityView_Admin_View_Field( $item['label_text'], $item['field_id'], $item, $settings = array(), $form );

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

		// if in zone directory or single
		if( in_array( $zone, array( 'directory', 'single' ), true ) ) {

			$meta_fields = GravityView_Fields::get_all( array( 'meta', 'gravityview', 'add-ons' ) );

			$entry_default_fields = array();

			foreach ( $meta_fields as $meta_field ) {
				$entry_default_fields += $meta_field->as_array();
			}
		}

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
			gravityview()->log->error( '$form is empty' );
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

		// Move Custom Content to top
		if ( isset( $fields['custom'] ) ) {
			$fields = array( 'custom' => $fields['custom'] ) + $fields;
		}

		$gv_fields = GravityView_Fields::get_all();

		foreach ( $fields as &$field ) {
			foreach ( $gv_fields as $gv_field ) {
				if ( \GV\Utils::get( $field, 'type' ) === $gv_field->name ) {
					$field['icon'] = \GV\Utils::get( $gv_field, 'icon' );
				}
			}
		}

		/**
		 * @filter `gravityview/admin/available_fields` Modify the available fields that can be used in a View.
		 * @param[in,out] array $fields The fields.
		 * @param  string|array $form form_ID or form object
		 * @param  string $zone Either 'single', 'directory', 'header', 'footer'
		 */
		return apply_filters( 'gravityview/admin/available_fields', $fields, $form, $zone );
	}


	/**
	 * Render html for displaying available widgets
	 * @return string html
	 */
	function render_available_widgets() {

		$widgets = \GV\Widget::registered();

		if ( empty( $widgets ) ) {
			return;
		}

		foreach ( $widgets as $id => $details ) {
			echo new GravityView_Admin_View_Widget( $details['label'], $id, $details );
		}

	}

	/**
	 * Get the list of registered widgets. Each item is used to instantiate a GravityView_Admin_View_Widget object
	 * @deprecated Use \GV\Widget::registered()
	 * @since 1.13.1
	 * @return array
	 */
	function get_registered_widgets() {

		_deprecated_function( __METHOD__, '2.0', '\GV\Widget::registered()' );

		return \GV\Widget::registered();
	}

	/**
	 * Generic function to render rows and columns of active areas for widgets & fields
	 * @param  string $template_id The current slug of the selected View template
	 * @param  string $type   Either 'widget' or 'field'
	 * @param  string $zone   Either 'single', 'directory', 'edit', 'header', 'footer'
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

		/**
		 * @internal Don't rely on this filter! This is for internal use and may change.
		 *
		 * @since 2.8.1
		 *
		 * @param string $button_label Text for button: "Add Widget" or "Add Field"
		 * @param array $atts {
		 *   @type string $type 'widget' or 'field'
		 *   @type string $template_id The current slug of the selected View template
		 *   @type string $zone Where is this button being shown? Either 'single', 'directory', 'edit', 'header', 'footer'
		 * }
		 */
		$button_label = apply_filters( 'gravityview/admin/add_button_label', $button_label, array( 'type' => $type, 'template_id' => $template_id, 'zone' => $zone ) );

		$available_items = array();

		$view = \GV\View::from_post( $post );
		$form_id = null;

		// if saved values, get available fields to label everyone
		if( !empty( $values ) && ( !empty( $post->ID ) || !empty( $_POST['template_id'] ) ) ) {

			if( !empty( $_POST['template_id'] ) ) {
				$form = GravityView_Ajax::pre_get_form_fields( $_POST['template_id'] );
			} else {
				$form_id = $form = gravityview_get_form_id( $post->ID );
			}

			if ( 'field' === $type ) {
				$available_items[ $form ] = $this->get_available_fields( $form, $zone );

				$joined_forms = gravityview_get_joined_forms( $post->ID );

                foreach ( $joined_forms as $form ) {
                    $available_items[ $form->ID ] = $this->get_available_fields( $form->ID, $zone );
                }
			} else {
				$available_items[ $form ] = \GV\Widget::registered();
			}
		}

		foreach( $rows as $row ) :
			foreach( $row as $col => $areas ) :
				$column = ($col == '2-2') ? '1-2' : $col; ?>

				<div class="gv-grid-col-<?php echo esc_attr( $column ); ?>">

					<?php foreach( $areas as $area ) : 	?>

						<div class="gv-droppable-area" data-areaid="<?php echo esc_attr( $zone .'_'. $area['areaid'] ); ?>" data-context="<?php echo esc_attr( $zone ); ?>">
                            <p class="gv-droppable-area-title" <?php if ( 'widget' === $type && empty( $area['subtitle'] ) ) { echo ' style="margin: 0; padding: 0;"'; } ?>>
								<strong <?php if ( 'widget' === $type ) { echo 'class="screen-reader-text"'; } ?>><?php echo esc_html( $area['title'] ); ?></strong>

								<?php if ( 'widget' !== $type ) { ?>
									<a class="clear-all-fields alignright" role="button" href="#" data-areaid="<?php echo esc_attr( $zone .'_'. $area['areaid'] ); ?>"><?php esc_html_e( 'Clear all fields', 'gravityview' ); ?></a>
								<?php } ?>

                                <?php if ( ! empty( $area['subtitle'] ) ) { ?>
									<span class="gv-droppable-area-subtitle"><span class="gf_tooltip gv_tooltip tooltip" title="<?php echo esc_attr( $area['subtitle'] ); ?>"></span></span>
								<?php } ?>
							</p>
							<div class="active-drop-container active-drop-container-<?php echo esc_attr( $type ); ?>">
							<div class="active-drop active-drop-<?php echo esc_attr( $type ); ?>" data-areaid="<?php echo esc_attr( $zone .'_'. $area['areaid'] ); ?>"><?php // render saved fields
								if( ! empty( $values[ $zone .'_'. $area['areaid'] ] ) ) {

									foreach( $values[ $zone .'_'. $area['areaid'] ] as $uniqid => $field ) {

										// Maybe has a form ID
										$form_id = empty( $field['form_id'] ) ? $form_id : $field['form_id'];

										$input_type = NULL;

										if ( $form_id ) {
											$original_item = isset( $available_items[ $form_id ] [ $field['id'] ] ) ? $available_items[ $form_id ] [ $field['id'] ] : false ;
                                        } else {
											$original_item = isset( $available_items[ $field['id'] ] ) ? $available_items[ $field['id'] ] : false ;
                                        }

										if ( !$original_item ) {
											gravityview()->log->error( 'An item was not available when rendering the output; maybe it was added by a plugin that is now de-activated.', array(' data' => array('available_items' => $available_items, 'field' => $field ) ) );

											$original_item = $field;
										} else {
											$input_type = isset( $original_item['type'] ) ? $original_item['type'] : NULL;
										}

										// Field options dialog box
										$field_options = GravityView_Render_Settings::render_field_options( $form_id, $type, $template_id, $field['id'], $original_item['label'], $zone .'_'. $area['areaid'], $input_type, $uniqid, $field, $zone, $original_item );

										$item = array(
											'input_type' => $input_type,
											'settings_html' => $field_options,
											'label_type' => $type,
										);

										// Merge the values with the current item to pass things like widget descriptions and original field names
										if ( $original_item ) {
											$item = wp_parse_args( $item, $original_item );
										}

										switch( $type ) {
											case 'widget':
												echo new GravityView_Admin_View_Widget( $item['label'], $field['id'], $item, $field );
												break;
											default:
												echo new GravityView_Admin_View_Field( $field['label'], $field['id'], $item, $field, $form_id );
										}
									}

								} // End if zone is not empty ?></div>
								<div class="gv-droppable-area-action">
									<a href="#" class="gv-add-field button button-link button-hero" title=""
									   data-objecttype="<?php echo esc_attr( $type ); ?>"
									   data-areaid="<?php echo esc_attr( $zone . '_' . $area['areaid'] ); ?>"
									   data-context="<?php echo esc_attr( $zone ); ?>"
									   data-formid="<?php echo $view ? esc_attr( $view->form ? $view->form->ID : '' ) : ''; ?>"><?php echo '<span class="dashicons dashicons-plus-alt"></span>' . esc_html( $button_label ); ?></a>
								</div>
							</div>
						</div>

					<?php endforeach; ?>

				</div>
			<?php endforeach;
		endforeach;
	}

	/**
	 * Render the widget active areas
	 *
	 * @param  string $template_id The current slug of the selected View template
	 * @param  string $zone    Either 'header' or 'footer'
	 * @param  string $post_id Current Post ID (view)
	 *
	 * @return string          html
	 */
	function render_widgets_active_areas( $template_id = '', $zone = '', $post_id = '' ) {

		$default_widget_areas = \GV\Widget::get_default_widget_areas();

		$widgets = array();
		if ( ! empty( $post_id ) ) {
			if ( 'auto-draft' === get_post_status( $post_id ) ) {
				// This is a new View, prefill the widgets
				$widgets = array(
					'header_top' => array(
						substr( md5( microtime( true ) ), 0, 13 ) => array (
							'id' => 'search_bar',
							'label' => __( 'Search Bar', 'gravityview' ),
							'search_layout' => 'horizontal',
							'search_clear' => '0',
							'search_fields' => '[{"field":"search_all","input":"input_text"}]',
							'search_mode' => 'any',
						),
					),
					'header_left' => array(
						substr( md5( microtime( true ) ), 0, 13 ) => array(
							'id' => 'page_info',
							'label' => __( 'Show Pagination Info', 'gravityview' ),
						),
					),
					'header_right' => array(
						substr( md5( microtime( true ) ), 0, 13 ) => array(
							'id' => 'page_links',
							'label' => __( 'Page Links', 'gravityview' ),
							'show_all' => '0',
						),
					),
					'footer_right' => array(
						substr( md5( microtime( true ) ), 0, 13 ) => array(
							'id' => 'page_links',
							'label' => __( 'Page Links', 'gravityview' ),
							'show_all' => '0',
						),
					),
				);

				/**
				 * @filter `gravityview/view/widgets/default` Modify the default widgets for new Views
				 * @param[in,out] array $widgets A Widget configuration array
				 * @param string $zone The widget zone that's being requested
				 * @param int $post_id The auto-draft post ID
				 */
				$widgets = apply_filters( 'gravityview/view/widgets/default', $widgets, $template_id, $zone, $post_id );
			} else {
				$widgets = gravityview_get_directory_widgets( $post_id );
			}
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
	 * Renders "Add Field" tooltips
	 *
	 * @since 2.0.11
	 *
	 * @param string $context  "directory", "single", or "edit"
	 * @param array  $form_ids (default: array) Array of form IDs
	 *
	 * @return void
	 */
	function render_field_pickers( $context = 'directory', $form_ids = array() ) {

		global $post;

		if ( $post ) {
			$source_form_id = gravityview_get_form_id( $post->ID );
			if ( $source_form_id ) {
				$form_ids[] = $source_form_id;
			}

			$joined_forms = \GV\View::get_joined_forms( $post->ID );
			foreach ( $joined_forms as $joined_form ) {
				$form_ids[] = $joined_form->ID;
			}
		}
		foreach ( array_unique( $form_ids ) as $form_id ) {
			$filter_field_id = sprintf( 'gv-field-filter-%s-%s', $context, $form_id );

			?>
            <div id="<?php echo esc_html( $context ); ?>-available-fields-<?php echo esc_attr( $form_id ); ?>" class="hide-if-js gv-tooltip">
                <button class="close" role="button" aria-label="<?php esc_html_e( 'Close', 'gravityview' ); ?>"><i class="dashicons dashicons-dismiss"></i></button>

                <div class="gv-field-filter-form">
                    <label class="screen-reader-text" for="<?php echo esc_html( $filter_field_id ); ?>"><?php esc_html_e( 'Filter Fields:', 'gravityview' ); ?></label>
                    <input type="search" class="widefat gv-field-filter" aria-controls="<?php echo $filter_field_id; ?>" id="<?php echo esc_html( $filter_field_id ); ?>" placeholder="<?php esc_html_e( 'Filter fields by name or label', 'gravityview' ); ?>" />
					<div class="button-group">
						<span role="button" class="button button-large gv-items-picker gv-items-picker--grid" data-value="grid"><i class="dashicons dashicons-grid-view "></i></span>
						<span role="button" class="button button-large gv-items-picker gv-items-picker--list active" data-value="list"><i class="dashicons dashicons-list-view"></i></span>
					</div>
                </div>

                <div id="available-fields-<?php echo $filter_field_id; ?>" aria-live="polite" role="listbox" class="gv-items-picker-container">
					<?php do_action( 'gravityview_render_available_fields', $form_id, $context ); ?>
                </div>

                <div class="gv-no-results hidden description"><?php esc_html_e( 'No fields were found matching the search.', 'gravityview' ); ?></div>
            </div>
			<?php
		}
	}

	/**
	 * Render the Template Active Areas and configured active fields for a given template id and post id
	 *
	 * @param string $template_id (default: '') Template ID, like `default_list`, `default_table`, `preset_business_data`, etc. {@see GravityView_Template::__construct()}
	 * @param string $post_id (default: '')
	 * @param string $context (default: 'single')
	 * @return string HTML of the active areas
	 */
	function render_directory_active_areas( $template_id = '', $context = 'single', $post_id = '', $echo = false ) {
		if( empty( $template_id ) ) {
			gravityview()->log->debug( '[render_directory_active_areas] {template_id} is empty', array( 'template_id' => $template_id ) );
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

			gravityview()->log->debug( '[render_directory_active_areas] No areas defined. Maybe template {template_id} is disabled.', array( 'data' => $template_id ) );
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
	 * @param mixed $hook
	 * @return void
	 */
	static function add_scripts_and_styles( $hook ) {
		global $plugin_page, $pagenow;

		$script_debug    = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$is_widgets_page = ( $pagenow === 'widgets.php' );

		// Add legacy (2.4 and older) Gravity Forms tooltip script/style
		if ( gravityview()->plugin->is_GF_25() && gravityview()->request->is_admin( '', 'single' ) ) {
			wp_dequeue_script( 'gform_tooltip_init' );
			wp_dequeue_style( 'gform_tooltip' );
			wp_enqueue_style( 'gravityview_gf_tooltip', plugins_url( 'assets/css/gf_tooltip.css', GRAVITYVIEW_FILE ), array(), \GV\Plugin::$version );
			wp_enqueue_script( 'gravityview_gf_tooltip', plugins_url( 'assets/js/gf_tooltip' . $script_debug . '.js', GRAVITYVIEW_FILE ), array(), \GV\Plugin::$version );
		}

		// Add the GV font (with the Astronaut)
        wp_enqueue_style( 'gravityview_global', plugins_url('assets/css/admin-global.css', GRAVITYVIEW_FILE), array(), \GV\Plugin::$version );
		wp_register_style( 'gravityview_views_styles', plugins_url( 'assets/css/admin-views.css', GRAVITYVIEW_FILE ), array( 'dashicons', 'wp-jquery-ui-dialog' ), \GV\Plugin::$version );

		wp_register_script( 'gravityview-jquery-cookie', plugins_url('assets/lib/jquery.cookie/jquery.cookie.min.js', GRAVITYVIEW_FILE), array( 'jquery' ), \GV\Plugin::$version, true );

		if( GFForms::get_page() === 'form_list' ) {
			wp_enqueue_style( 'gravityview_views_styles' );
			return;
        }

		// Don't process any scripts below here if it's not a GravityView page.
		if( ! gravityview()->request->is_admin( $hook, 'single' ) && ! $is_widgets_page ) {
		    return;
		}

		wp_enqueue_code_editor( array( 'type' => 'text/html' ) );

        wp_enqueue_script( 'jquery-ui-datepicker' );

        wp_enqueue_style( 'gravityview_views_datepicker', plugins_url('assets/css/admin-datepicker.css', GRAVITYVIEW_FILE), \GV\Plugin::$version );

        // Enqueue scripts
        wp_enqueue_script( 'gravityview_views_scripts', plugins_url( 'assets/js/admin-views' . $script_debug . '.js', GRAVITYVIEW_FILE ), array( 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'jquery-ui-tooltip', 'jquery-ui-dialog', 'gravityview-jquery-cookie', 'jquery-ui-datepicker', 'underscore' ), \GV\Plugin::$version );

        wp_localize_script('gravityview_views_scripts', 'gvGlobals', array(
            'cookiepath' => COOKIEPATH,
            'admin_cookiepath' => ADMIN_COOKIE_PATH,
            'passed_form_id' => (bool) \GV\Utils::_GET( 'form_id' ),
            'nonce' => wp_create_nonce( 'gravityview_ajaxviews' ),
            'label_viewname' => __( 'Enter View name here', 'gravityview' ),
            'label_reorder_search_fields' => __( 'Reorder Search Fields', 'gravityview' ),
            'label_add_search_field' => __( 'Add Search Field', 'gravityview' ),
            'label_remove_search_field' => __( 'Remove Search Field', 'gravityview' ),
            'label_close' => __( 'Close', 'gravityview' ),
            'label_cancel' => __( 'Cancel', 'gravityview' ),
            'label_continue' => __( 'Continue', 'gravityview' ),
            'label_ok' => __( 'Ok', 'gravityview' ),
            'label_publisherror' => __( 'Error while creating the View for you. Check the settings or contact GravityView support.', 'gravityview' ),
            'loading_text' => esc_html__( 'Loading&hellip;', 'gravityview' ),
            'loading_error' => esc_html__( 'There was an error loading dynamic content.', 'gravityview' ),
            'field_loaderror' => __( 'Error while adding the field. Please try again or contact GravityView support.', 'gravityview' ),
            'remove_all_fields' => __( 'Would you like to remove all fields in this zone?', 'gravityview' ),
        ));

		wp_enqueue_style( 'gravityview_views_styles' );

        // Enqueue scripts needed for merge tags
        self::enqueue_gravity_forms_scripts();

		// 2.5 changed how Merge Tags are enqueued
		if ( is_callable( array( 'GFCommon', 'output_hooks_javascript') ) ) {
			GFCommon::output_hooks_javascript();
		}
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
				'gravityview_gf_tooltip',
				'sack',
				'gform_gravityforms',
				'gform_forms',
				'gform_form_admin',
				'jquery-ui-autocomplete',
			);

		} elseif ( preg_match( '/style/ism', $filter ) ) {

			$allowed_dependencies = array(
				'dashicons',
				'wp-jquery-ui-dialog',
				'gravityview_views_styles',
				'gravityview_global',
				'gravityview_views_datepicker',
				'gravityview_gf_tooltip',
			);
		}

		return array_merge( $registered, $allowed_dependencies );
	}


}

new GravityView_Admin_Views;
