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


class GravityView_Admin_Views {

	private $post_id;

	function __construct() {

		add_action( 'save_post', array( $this, 'save_postdata' ) );

		// set the blacklist field types across the entire plugin
		add_filter( 'gravityview_blacklist_field_types', array( $this, 'default_field_blacklist' ), 10 );

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

		add_action( 'manage_gravityview_posts_custom_column', array( __CLASS__, 'add_connected_form_column_content'), 10, 2 );

	}


	/**
	 * @deprecated since 1.2
	 * Start using GravityView_Render_Settings::render_setting_row
	 */
	public static function render_setting_row( $key = '', $current_settings = array(), $override_input = null, $name = 'template_settings[%s]', $id = 'gravityview_se_%s' ) {
        _deprecated_function( 'GravityView_Admin_Views::render_setting_row', '1.1.7', 'GravityView_Render_Settings::render_setting_row' );
		return GravityView_Render_Settings::render_setting_row( $key, $current_settings, $override_input, $name , $id );
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
			return $menu_items;
		}

		// This needs to be here to trigger Gravity Forms to use the submenu;
		// If there's only submenu item, it replaces the main menu link with the submenu item.
		$sub_menu_items = array(
			array(
				'url' => '#',
				'label' => '',
				'menu_class' => 'hidden',
				'capabilities' => '',
			)
		);

		foreach ( (array)$connected_views as $view ) {
			$sub_menu_items[] = array(
				'url' => admin_url( 'post.php?action=edit&post='.$view->ID ),
				'label' => esc_attr( $view->post_title ),
				'capabilities' => current_user_can( 'edit_post', $view->ID ),
			);
		}

		$menu_items['gravityview'] = array(
			'label' 			=> __( 'Connected Views', 'gravityview' ),
			'icon' 			=> '<i class="fa fa-lg gv-icon-astronaut-head gv-icon"></i>',
			'title'				=> __('GravityView Views using this form as a data source', 'gravityview'),
			'url' 				=> '#',
			'onclick'			=> 'return false;',
			'menu_class' 		=> 'gv_connected_forms gf_form_toolbar_settings',
			'link_class' 		=> ( 1 === 1 ? '' : 'gf_toolbar_disabled' ),
			'sub_menu_items' 	=> $sub_menu_items,
			'capabilities' 		=> array(),
			'priority'			=> 0
		);

		return $menu_items;
	}

	/**
	 * List the field types without presentation properties (on a View context)
	 *
	 * @access public
	 * @return void
	 */
	function default_field_blacklist( $array = array() ) {
		return array_merge( $array, array( 'captcha', 'page' ) );
	}

	/**
	 * Add tooltip text for use throughout the UI
	 * @param  array       $tooltips Array of Gravity Forms tooltips
	 * @return array                Modified tooltips array
	 */
	public function tooltips( $tooltips = array() ) {

		$gv_tooltips = array();

		// Generate tooltips for View settings
		$default_args = GravityView_View_Data::get_default_args( true );

		foreach ( $default_args as $key => $arg ) {

			// If an arg has `tooltip` defined, but it's false, don't display a tooltip
			if( isset( $arg['tooltip'] ) && empty( $arg['tooltip'] ) ) { continue; }

			// And if there's no description to be used as a tooltip.
			if( empty( $arg['desc'] ) ) { continue; }

			// Add the tooltip
			$gv_tooltips[ 'gv_'.$key ] = array(
				'title'	=> $arg['label'],
				'value'	=> $arg['desc'],
			);

		}

		$gv_tooltips['gv_css_merge_tags'] = array(
				'title' => __('CSS Merge Tags', 'gravityview'),
				'value' => sprintf( __( 'Developers: The CSS classes will be sanitized using the %ssanitize_title_with_dashes()%s function.', 'gravityview'), '<code>', '</code>' )
		);

		$gv_tooltips = apply_filters( 'gravityview_tooltips', $gv_tooltips );

		foreach ( $gv_tooltips as $key => $tooltip ) {

			$title = empty( $tooltip['title'] ) ? '' : '<h6>'.esc_html( $tooltip['title'] ) .'</h6>';

			$tooltips[ $key ] = $title . wpautop( esc_html( $tooltip['value'] ) );
		}

		return $tooltips;
	}

	static public function add_connected_form_column_content( $column_name = NULL, $post_id )	{

		if( !empty( $column_name ) && $column_name !== 'gv_connected_form' )  { return; }

		$form_id = gravityview_get_form_id( $post_id );

		// All Views should have a connected form. If it doesn't, that's not right.
		if( empty($form_id) ) {
			do_action( 'gravityview_log_error', sprintf( '[add_connected_form_column_content] View ID %s does not have a connected GF form.', $post_id ) );
			echo __( 'Not connected.', 'gravityview' );
			return;
		}

		$form = gravityview_get_form( $form_id );

		if( !$form ) {
			do_action( 'gravityview_log_error', sprintf( '[add_connected_form_column_content] Connected form not found: Form #%d', $form_id ) );

			echo __( 'The connected form can not be found; it may no longer exist.', 'gravityview' );
		}

		echo self::get_connected_form_links( $form );

	}

	/**
	 * Get HTML links relating to a connected form, like Edit, Entries, Settings, Preview
	 * @param  array|int $form_id Gravity Forms forms array, or the form ID
	 * @param  boolean $include_form_link Whether to include the bold name of the form in the output
	 * @return string          HTML links
	 */
	static public function get_connected_form_links( $form, $include_form_link = true ) {

		// Either the form is empty or the form ID is 0, not yet set.
		if( empty( $form ) ) {
			return;
		}

		// The $form is passed as the form ID
		if( !is_array( $form ) ) {
			$form = gravityview_get_form( $form );
		}

		$form_id = $form['id'];
		$form_link = '';
		$links = array();

		if( GFCommon::current_user_can_any('gravityforms_edit_forms') ) {
			$form_url = admin_url( sprintf( 'admin.php?page=gf_edit_forms&amp;id=%d', $form_id ) );
			$form_link = sprintf( '<strong class="gv-form-title"><a href="%s" class="row-title">%s</a></strong>', $form_url , $form['title'] );
			$links[] = sprintf( '<span><a href="%s">%s</a></span>', $form_url , __('Edit Form', 'gravityview') );
		}

		if( GFCommon::current_user_can_any('gravityforms_view_entries') ) {
			$entries_url = admin_url( sprintf( 'admin.php?page=gf_entries&amp;id=%d', $form_id ) );
			$links[] = sprintf( '<span><a href="%s">%s</a></span>', $entries_url , __( 'Entries', 'gravityview' ) );
		}

		if( GFCommon::current_user_can_any('gravityforms_edit_settings') ) {
			$settings_url = admin_url( sprintf( 'admin.php?page=gf_edit_forms&amp;view=settings&amp;id=%d', $form_id ) );
			$links[] = sprintf( '<span><a title="%s" href="%s">%s</a></span>', __('Edit settings for this form', 'gravityview'), $settings_url, __('Settings', 'gravityview') );
		}

		if( GFCommon::current_user_can_any( array("gravityforms_edit_forms", "gravityforms_create_form", "gravityforms_preview_forms") ) ) {
			$preview_url = site_url( sprintf( '?gf_page=preview&amp;id=%d', $form_id ) );
			$links[] = sprintf( '<span><a title="%s" href="%s">%s</a></span>', __('Preview this form', 'gravityview'), $preview_url, __('Preview', 'gravityview') );
		}

		$output = '';

		if( !empty( $include_form_link ) ) {
			$output .= $form_link;
		}

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

		$columns['gv_connected_form'] = __('Data Source', 'gravityview');

		// Add the date back in.
		$columns['date'] = $date;

		return $columns;
	}

	/**
	 * Save View configuration
	 *
	 * @access public
	 * @param mixed $post_id
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
		// validate user can edit and save post/page
		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return;
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) )
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

			} elseif( !empty( $_POST['fields'] ) ) {

				if( !is_array( $_POST['fields'] ) ) {

					// Fields are passed as a jQuery-serialized array, created in admin-views.js in the serializeForm method
					parse_str( $_POST['fields'], $fields_holder );

					if( isset( $fields_holder['fields'] ) ) {

						// When parsed, there's a m
						$fields = $fields_holder['fields'];

					} else {

						do_action('gravityview_log_error', '[save_postdata] No `fields` key was found after parsing $fields string', $fields_holder );

					}

				} else {

					$fields = $_POST['fields'];

				}
			}

			$statii['directory_fields'] = update_post_meta( $post_id, '_gravityview_directory_fields', $fields );

			// Directory Visible Widgets
			if( empty( $_POST['widgets'] ) ) {
				$_POST['widgets'] = array();
			}
			$statii['directory_widgets'] = update_post_meta( $post_id, '_gravityview_directory_widgets', $_POST['widgets'] );

		} // end save view configuration

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
	 * @filter  gravityview_blacklist_field_types Modify the types of fields that shouldn't be shown in a View.
	 * @access public
	 * @param int $form_id Gravity Forms Form ID (default: '')
	 * @param string $context (default: 'single')
	 * @return void
	 */
	function render_available_fields( $form = '', $context = 'single' ) {

		$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array() );

		$fields = $this->get_available_fields( $form, $context );

		$output = '';

		if( !empty( $fields ) ) {

			foreach( $fields as $id => $details ) {

				if( in_array( $details['type'], $blacklist_field_types ) ) {
					continue;
				}

				$output .= new GravityView_Admin_View_Field( $details['label'], $id, $details );

			} // End foreach
		}

		echo $output;

		$this->render_additional_fields( $form, $context );
	}

	function render_additional_fields( $form, $context ) {

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
			 * @since  1.2
			 */
			'custom'	=> array(
				'label'	=> __('Custom Content', 'gravityview'),
				'type'	=> 'custom',
				'desc'	=> __('Insert custom text or HTML.', 'gravityview'),
			),
        );

		if('single' !== $zone) {

        	$entry_default_fields['entry_link'] = array(
        		'label' => __('Link to Entry', 'gravityview'),
        		'desc'	=> __('A dedicated link to the single entry with customizable text.', 'gravityview'),
        		'type' => 'entry_link',
        	);
        }

        return apply_filters( 'gravityview_entry_default_fields', $entry_default_fields, $form, $zone);
	}

	/**
	 * Calculate the available fields
	 * @param  string|array form_ID or form object
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

		// get the list of registered widgets
		$widgets = apply_filters( 'gravityview_register_directory_widgets', array() );

		if( !empty( $widgets ) ) :
			foreach( $widgets as $id => $details ) :

				echo new GravityView_Admin_View_Widget( $details['label'], $id, $details );

			endforeach;
		endif;

	}

	/**
	 * Generic function to render rows and columns of active areas for widgets & fields
	 * @param  string $type   Either 'widget' or 'field'
	 * @param  string $zone   Either 'single', 'directory', 'header', 'footer'
	 * @param  array $rows    The layout structure: rows, columns and areas
	 * @param  array $values  Saved objects
	 * @return void
	 */
	function render_active_areas( $template_id, $type, $zone, $rows, $values ) {
		global $post;

		$available_items = array();

		if( $type === 'widget' ) {
			$button_label = __( 'Add Widget', 'gravityview' );
		} elseif( $type === 'field' ) {
			$button_label = __( 'Add Field', 'gravityview' );
		}

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
				// get the list of registered widgets
				$available_items = apply_filters( 'gravityview_register_directory_widgets', array() );
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

											do_action('gravityview_log_error', 'An item was not available when rendering the output; maybe it was added by a plugn that is now de-activated.', array('available_items' => $available_items, 'field' => $field ));

											$original_item = $field;
										} else {

											$input_type = isset( $original_item['type'] ) ? $original_item['type'] : NULL;

										}

										// Field options dialog box
										$field_options = GravityView_Render_Settings::render_field_options( $type, $template_id, $field['id'], $original_item['label'], $zone .'_'. $area['areaid'], $input_type, $uniqid, $field, $zone );

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
			$widgets = get_post_meta( $post_id, '_gravityview_directory_widgets', true );

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
	 * @param string $template_id (default: '')
	 * @param string $post_id (default: '')
	 * @param string $context (default: 'single')
	 * @return void
	 */
	function render_directory_active_areas( $template_id = '', $context = 'single', $post_id = '', $echo = false ) {

		if( empty( $template_id ) ) {
			do_action( 'gravityview_log_debug', '[render_directory_active_areas] $template_id is empty' );
			return;
		}

		$output = '';

		$template_areas = apply_filters( 'gravityview_template_active_areas', array(), $template_id );

		$fields = '';
		if( !empty( $post_id ) ) {
			$fields = gravityview_get_directory_fields( $post_id );
		}

		ob_start();
		$this->render_active_areas( $template_id, 'field', $context, $template_areas, $fields );
		$output = ob_get_clean();

		if( $echo ) {
			echo $output;
		}

		return $output;
	}

<<<<<<< HEAD
	/**
	 * Render Field Options html (shown through a dialog box)
	 *
	 * @todo Move to `class-admin-label.php`
	 * @access public
	 * @param string $template_id
	 * @param string $field_id
	 * @param string $field_label
	 * @param string $area
	 * @param string $uniqid (default: '')
	 * @param string $current (default: '')
	 * @param string $context (default: 'single')
	 * @return void
	 */
	static function render_field_options( $field_type, $template_id, $field_id, $field_label, $area, $input_type = NULL, $uniqid = '', $current = '', $context = 'single' ) {

		if( empty( $uniqid ) ) {
			//generate a unique field id
			$uniqid = uniqid('', false);
		}

		// get field/widget options
		$options = self::get_default_field_options( $field_type, $template_id, $field_id, $context, $input_type );

		// two different post arrays, depending of the field type
		$name_prefix = $field_type .'s' .'['. $area .']['. $uniqid .']';

		// build output
		$output = '';
		$output .= '<input type="hidden" class="field-key" name="'. $name_prefix .'[id]" value="'. esc_attr( $field_id ) .'">';
		$output .= '<input type="hidden" class="field-label" name="'. $name_prefix .'[label]" value="'. esc_attr( $field_label ) .'">';

		// If there are no options, return what we got.
		if(empty($options)) {

			// This is here for checking if the output is empty in render_label()
			$output .= '<!-- No Options -->';

			return $output;
		}

		$output .= '<div class="gv-dialog-options" title="'. esc_attr( sprintf( __( 'Options: %s', 'gravity-view' ), $field_label ) ) .'">';
		$output .= '<ul>';

		foreach( $options as $key => $details ) {
			$value = isset( $current[ $key ] ) ? $current[ $key ] : NULL;
			$output .= '<li>'. self::render_field_option( $name_prefix . '['. $key .']' , $details, $value) .'</li>';
		}

		// close options window
		$output .= '</ul>';
		$output .= '</div>';

		return $output;

	}

	/**
	 * Get capabilities options for GravityView
	 *
	 * Parameters are only to pass to the filter.
	 *
	 * @param  string $template_id Optional. View slug
	 * @param  string $field_id    Optional. GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
	 * @param  string $context     Optional. What context are we in? Example: `single` or `directory`
	 * @param  string $input_type  Optional. (textarea, list, select, etc.)
	 * @return array Associative array, with the key being the capability and the value being the label shown.
	 */
	static public function get_cap_choices( $template_id = '', $field_id = '', $context = '', $input_type = '' ) {

		$select_cap_choices = array(
			'read' => __( 'Any Logged-In User', 'gravity-view' ),
			'publish_posts' => __( 'Author Or Higher', 'gravity-view' ),
			'gravityforms_view_entries' => __( 'Can View Gravity Forms Entries', 'gravity-view' ),
			'delete_others_posts' => __( 'Editor Or Higher', 'gravity-view' ),
			'gravityforms_edit_entries' => __( 'Can Edit Gravity Forms Entries', 'gravity-view' ),
			'manage_options' => __( 'Administrator', 'gravity-view' ),
		);

		if( is_multisite() ) {
			$select_cap_choices['manage_network'] = __('Multisite Super Admin', 'gravity-view' );
		}

		/**
		 * Modify the capabilities shown in the field dropdown
		 * @link  https://github.com/zackkatz/GravityView/wiki/How-to-modify-capabilities-shown-in-the-field-%22Only-visible-to...%22-dropdown
		 * @since  1.0.1
		 */
		$select_cap_choices = apply_filters('gravityview_field_visibility_caps', $select_cap_choices, $template_id, $field_id, $context, $input_type );

		return $select_cap_choices;
	}

	/**
	 * Get the default options for a standard field.
	 *
	 * @param  string      $field_type  Type of field options to render (`field` or `widget`)
	 * @param  string      $template_id Table slug
	 * @param  float       $field_id    GF Field ID - Example: `3`, `5.2`, `entry_link`, `created_by`
	 * @param  string      $context     What context are we in? Example: `single` or `directory`
	 * @param  string      $input_type  (textarea, list, select, etc.)
	 * @return array                   Array of field options with `label`, `value`, `type`, `default` keys
	 * @filter gravityview_template_{$field_type}_options Filter the field options by field type ( field / widget)
	 * @filter gravityview_template_{$input_type}_options Filter the field options by input type (textarea, list, select, etc.)
	 */
	static public function get_default_field_options( $field_type, $template_id, $field_id, $context, $input_type ) {

		$field_options = array();

		if( 'field' === $field_type ) {

			// Default options - fields
			$field_options = array(
				'show_label' => array(
					'type' => 'checkbox',
					'label' => __( 'Show Label', 'gravity-view' ),
					'default' => preg_match('/table/ism', $template_id), // If the view template is table, show label as default. Otherwise, don't
				),
				'custom_label' => array(
					'type' => 'text',
					'label' => __( 'Custom Label:', 'gravity-view' ),
					'default' => '',
					'merge_tags' => true,
				),
				'custom_class' => array(
					'type' => 'text',
					'label' => __( 'Custom CSS Class:', 'gravity-view' ),
					'desc' => __( 'This class will be added to the field container', 'gravity-view'),
					'default' => '',
					'merge_tags' => true,
					'tooltip' => 'gv_css_merge_tags',
				),
				'only_loggedin' => array(
					'type' => 'checkbox',
					'label' => __( 'Make visible only to logged-in users?', 'gravity-view' ),
					'default' => ''
				),
				'only_loggedin_cap' => array(
					'type' => 'select',
					'label' => __( 'Make visible for:', 'gravity-view' ),
					'choices' => self::get_cap_choices( $template_id, $field_id, $context, $input_type ),
					'class' => 'widefat',
					'default' => 'read',
				),
				'allow_edit' => array(
					'type' => 'checkbox',
					'label' => __( 'Make field editable?', 'gravity-view' ),
					'desc' => __( 'Note: Affects entire field if it has multiple inputs, i.e. selections for "First" input will also affect "Last" under the "Name" field.', 'gravity-view'),
					'default' => 1
				),
				'allow_edit_cap' => array(
					'type' => 'select',
					'label' => __( 'Make editable for:', 'gravity-view' ),
					'choices' => self::get_cap_choices( $template_id, $field_id, $context, $input_type ),
					'class' => 'widefat',
					'default' => 'read',
				),
			);

		} elseif( 'widget' === $field_type ) {

		}

		// hook to inject template specific field/widget options
		$field_options = apply_filters( "gravityview_template_{$field_type}_options", $field_options, $template_id, $field_id, $context, $input_type );

		// hook to inject template specific input type options (textarea, list, select, etc.)
		$field_options = apply_filters( "gravityview_template_{$input_type}_options", $field_options, $template_id, $field_id, $context, $input_type );

		return $field_options;
	}

	/**
	 * Handle rendering a field option form element
	 *
	 * @uses GravityView_Admin_Views::render_checkbox_option() Render <input type="checkbox">
	 * @uses GravityView_Admin_Views::render_select_option() Render <select>
	 * @uses GravityView_Admin_Views::render_text_option() Render <input type="text">
	 * @param  string      $name    Input `name` attribute
	 * @param  array      $option  Associative array of options. See the $defaults variable for available keys.
	 * @param  mixed      $current Current value of option
	 * @return string               HTML output of option
	 */
	public static function render_field_option( $name = '', $passed_option, $current = NULL ) {

		$defaults = array(
			'default' => '',
			'desc' => '',
			'value' => NULL,
			'label' => '',
			'type'	=> 'text',
			'choices' => NULL,
			'merge_tags' => true,
			'tooltip' => NULL,
		);

		$option = wp_parse_args( $passed_option, $defaults );

		extract( $option );

		// If we set a tooltip, get the HTML
		$tooltip = !empty( $option['tooltip'] ) ? ' '.gform_tooltip( $option['tooltip'] , '', true ) : NULL;

		$output = '';

		if( is_null($current) ) {
			$current = $option['default'];
		}

		$id = sanitize_html_class( $name );

		$output .= '<label for="'. $id .'" class="gv-label-'.sanitize_html_class( $option['type'] ).'">';

		if( !empty( $option['desc'] ) ) {
			$option['desc'] = '<span class="howto">'.$option['desc'].'</span>';
		}

		switch( $option['type'] ) {
			case 'checkbox':
				$output .= self::render_checkbox_option( $name, $id, $current );
				$output .= '&nbsp;'.$option['label'].$tooltip.$option['desc'];
				break;

			case 'select':
				$output .= $option['label'].$tooltip.$option['desc'].'&nbsp;';
				$output .= self::render_select_option( $name, $id, $option['choices'], $current );
				break;

			case 'textarea':
				$output .= $option['label'].$tooltip.$option['desc'];
				$output .= '<div>';
				$output .= self::render_textarea_option( $name, $id, $current, $option['merge_tags'] );
				$output .= '</div>';
				break;

			case 'text':
			default:
				$output .= $option['label'].$tooltip.$option['desc'];
				$output .= '<div>';
				$output .= self::render_text_option( $name, $id, $current, $option['merge_tags'] );
				$output .= '</div>';
				break;
		}

		$output .= '</label>';

		return $output;
	}

	/**
	 * Output a table row for view settings
	 * @param  string $key              The key of the input
	 * @param  array  $current_settings Associative array of current settings to use as input values, if set. If not set, the defaults are used.
	 * @param  [type] $override_input   [description]
	 * @param  string $name             [description]
	 * @param  string $id               [description]
	 * @return [type]                   [description]
	 */
	static function render_setting_row( $key = '', $current_settings = array(), $override_input = null, $name = 'template_settings[%s]', $id = 'gravityview_se_%s' ) {

		$name = esc_attr( sprintf( $name, $key ) );
		$id = esc_attr( sprintf( $id, $key ) );

		$setting = GravityView_View_Data::get_default_arg( $key, true );

		// If the key doesn't exist, there's something wrong.
		if( empty( $setting ) ) { return; }

		// Use default if current setting isn't set.
		$current = isset( $current_settings[ $key ] ) ? $current_settings[ $key ] : $setting['value'];

		$output = self::$setting_row_alt ? '<tr valign="top">' : '<tr valign="top" class="alt">';
		self::$setting_row_alt = self::$setting_row_alt ? false : true;

		$label = trim( esc_html( $setting['name'] ) . ' '.gform_tooltip( 'gv_'.$key, false, true ) );

		if( !empty( $override_input ) ) {
			$input = $override_input;
		} else {
			switch ($setting['type']) {
				case 'select':
					$input = GravityView_Admin_Views::render_select_option( $name, $id, $setting['options'], $current, true );
					break;
				case 'checkbox':
					$input = GravityView_Admin_Views::render_checkbox_option( $name, $id, $current, true );
					break;
				default:
					$input = GravityView_Admin_Views::render_text_option( $name, $id, $current, true, $setting );
					break;
			}
		}

		if( $setting['type'] === 'checkbox' ) {
			$output .= '<td scope="row" colspan="2">';
			$output .= '<label for="'.$id.'">';
			$output .= $input . ' ' . $label;
			$output .= '</label>';
		} else {

			// By default, show setting as full width.
			if( !empty( $setting['full_width'] ) ) {
				$output .= '<td scope="row" colspan="2"><div><label for="'.$id.'">';
				$output .= $label;
				$output .= '</label></div>'.$input.'</td>';
			} else {
				$output .= '<td scope="row"><label for="'.$id.'">';
				$output .= $label;
				$output .= '</label></td><td>'.$input.'</td>';
			}
		}

		$output .= '</tr>';

		echo $output;
	}


	/**
	 * Render the HTML for a checkbox input to be used on the field & widgets options
	 * @param  string $name , name attribute
	 * @param  string $current current value
	 * @return string         html tags
	 */
	public static function render_checkbox_option( $name = '', $id = '', $current = '' ) {

		$output  = '<input name="'. esc_attr( $name ) .'" type="hidden" value="0">';
		$output .= '<input name="'. esc_attr( $name ) .'" id="'. esc_attr( $id ) .'" type="checkbox" value="1" '. checked( $current, '1', false ) .' >';

		return $output;
	}


	/**
	 * Render the HTML for an input text to be used on the field & widgets options
	 * @param  string $name    Unique name of the field. Exampe: `fields[directory_list-title][5374ff6ab128b][custom_label]`
	 * @param  string $current [current value]
	 * @param  string $desc   Option description
	 * @param string $add_merge_tags Add merge tags to the input?
	 * @return string         [html tags]
	 */
	public static function render_text_option( $name = '', $id = '', $current = '', $add_merge_tags = NULL, $args = array() ) {

		// Show the merge tags if the field is a list view
		$is_list = ( preg_match( '/_list-/ism', $name ));

		// Or is a single entry view
		$is_single = ( preg_match( '/single_/ism', $name ));
		$show = ( $is_single || $is_list );

		$class = '';
		// and $add_merge_tags is not false
		if( $show && $add_merge_tags !== false || $add_merge_tags === 'force' ) {
			$class = 'merge-tag-support mt-position-right mt-hide_all_fields ';
		}

		$class .= !empty( $args['class'] ) ? $args['class'] : 'widefat';
		$type = !empty( $args['type'] ) ? $args['type'] : 'text';

		return '<input name="'. esc_attr( $name ) .'" id="'. esc_attr( $id ) .'" type="'.esc_attr($type).'" value="'. esc_attr( $current ) .'" class="'.esc_attr( $class ).'">';
	}

	/**
	 * Render the HTML for an textarea input to be used on the field & widgets options
	 * @param  string $name    Unique name of the field. Exampe: `fields[directory_list-title][5374ff6ab128b][custom_label]`
	 * @param  string $current [current value]
	 * @param  string $desc   Option description
	 * @param string $add_merge_tags Add merge tags to the input?
	 * @return string         [html tags]
	 */
	public static function render_textarea_option( $name = '', $id = '', $current = '', $add_merge_tags = NULL, $args = array() ) {

		// Show the merge tags if the field is a list view
		$is_list = ( preg_match( '/_list-/ism', $name ));

		// Or is a single entry view
		$is_single = ( preg_match( '/single_/ism', $name ));
		$show = ( $is_single || $is_list );

		$class = '';
		// and $add_merge_tags is not false
		if( $show && $add_merge_tags !== false || $add_merge_tags === 'force' ) {
			$class = 'merge-tag-support mt-position-right mt-hide_all_fields ';
		}

		$class .= !empty( $args['class'] ) ? 'widefat '.$args['class'] : 'widefat';
		$type = !empty( $args['type'] ) ? $args['type'] : 'text';

		return '<textarea name="'. esc_attr( $name ) .'" id="'. esc_attr( $id ) .'" class="'.esc_attr( $class ).'">'. esc_textarea( $current ) .'</textarea>';
	}

	/**
	 * Render the HTML for a select box to be used on the field & widgets options
	 * @param  string $name    [name attribute]
	 * @param  array $choices [select options]
	 * @param  string $current [current value]
	 * @return string          [html tags]
	 */
	public static function render_select_option( $name = '', $id = '', $choices, $current = '' ) {

		$output = '<select name="'. $name .'" id="'. $id .'">';
		foreach( $choices as $value => $label ) {
			$output .= '<option value="'. esc_attr( $value ) .'" '. selected( $value, $current, false ) .'>'. esc_html( $label ) .'</option>';
		}
		$output .= '</select>';

		return $output;
	}

=======
>>>>>>> a808607045d76aa5311257842c30a18ef3451758

	/**
	 * Uservoice feedback widget
	 */
	static function enqueue_uservoice_widget() {
		$script_debug = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
		wp_enqueue_script( 'gravityview-uservoice-widget', plugins_url('includes/js/uservoice'.$script_debug.'.js', GRAVITYVIEW_FILE), array(), GravityView_Plugin::version, true);
		wp_localize_script( 'gravityview-uservoice-widget', 'gvUserVoice', array(
			'email' => GravityView_Settings::getSetting( 'support-email' )
		));
	}

	/**
	 * Enqueue scripts and styles at Views editor
	 *
	 * @access public
	 * @param mixed $hook
	 * @return void
	 */
	static function add_scripts_and_styles( $hook ) {
		global $plugin_page;

		// Add the GV font (with the Astronaut)
		wp_enqueue_style( 'gravityview_global', plugins_url('includes/css/admin-global.css', GRAVITYVIEW_FILE), array(), GravityView_Plugin::version );

		wp_register_script( 'gravityview-jquery-cookie', plugins_url('includes/lib/jquery-cookie/jquery_cookie.js', GRAVITYVIEW_FILE), array( 'jquery' ), GravityView_Plugin::version, true );

		// Don't process any scripts below here if it's not a GravityView page.
		if( !gravityview_is_admin_page($hook) ) { return; }


		// Add the UserVoice widget on all GV pages
		self::enqueue_uservoice_widget();

		// Only enqueue the following on single pages
		if( gravityview_is_admin_page($hook, 'single')) {

			wp_enqueue_script( 'jquery-ui-datepicker' );
			//wp_enqueue_style( 'gravityview_views_datepicker', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css' );
			wp_enqueue_style( 'gravityview_views_datepicker', plugins_url('includes/css/admin-datepicker.css', GRAVITYVIEW_FILE), GravityView_Plugin::version );

			$script_debug = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

			//enqueue scripts
			wp_enqueue_script( 'gravityview_views_scripts', plugins_url('includes/js/admin-views'.$script_debug.'.js', GRAVITYVIEW_FILE), array( 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'jquery-ui-tooltip', 'jquery-ui-dialog', 'gravityview-jquery-cookie'  ), GravityView_Plugin::version );

			wp_localize_script('gravityview_views_scripts', 'gvGlobals', array(
				'cookiepath' => COOKIEPATH,
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

			wp_enqueue_style( 'gravityview_views_styles', plugins_url('includes/css/admin-views.css', GRAVITYVIEW_FILE), array('dashicons', 'wp-jquery-ui-dialog' ), GravityView_Plugin::version );

			self::enqueue_gravity_forms_scripts();

		} // End single page
	}

	static function enqueue_gravity_forms_scripts() {
		GFForms::register_scripts();

		$scripts = array(
		    'sack',
		    'gform_gravityforms',
		    'gform_forms',
		    'gform_form_admin',
		    'jquery-ui-autocomplete'
		);

		if ( wp_is_mobile() )
		    $scripts[] = 'jquery-touch-punch';

		foreach ($scripts as $script) {
			wp_enqueue_script( $script );
		}
	}

	function register_no_conflict( $registered ) {

		$filter = current_filter();

		if( preg_match('/script/ism', $filter ) ) {
			$allow_scripts = array( 'jquery-ui-core', 'jquery-ui-dialog', 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'jquery-ui-tooltip', 'gravityview_views_scripts', 'gravityview-uservoice-widget', 'gravityview-jquery-cookie', 'gravityview_views_datepicker',
			'sack', 'gform_gravityforms', 'gform_forms', 'gform_form_admin', 'jquery-ui-autocomplete' );
			$registered = array_merge( $registered, $allow_scripts );
		} elseif( preg_match('/style/ism', $filter ) ) {
			$allow_styles = array( 'dashicons', 'wp-jquery-ui-dialog', 'gravityview_views_styles', 'gravityview_global', 'gravityview_views_datepicker' );
			$registered = array_merge( $registered, $allow_styles );
		}

		return $registered;
	}


}

new GravityView_Admin_Views;
