<?php
/**
 * Renders all the metaboxes on Add New / Edit View post type.
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2013, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */



class GravityView_Admin_Views {

	
	function __construct() {
	
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'save_post', array( $this, 'save_postdata' ) );
		
		// adding styles and scripts
		add_action('admin_enqueue_scripts', array( $this, 'add_scripts_and_styles') );
		
		// ajax
		//get field options
		add_action( 'wp_ajax_gv_field_options', array( $this, 'get_field_options' ) );
		
		//get widget options
		add_action( 'wp_ajax_gv_widget_options', array( $this, 'get_widget_options' ) );
		
		// AJAX 
		// get available fields
		add_action( 'wp_ajax_gv_available_fields', array( $this, 'get_available_fields' ) );
		// get active areas
		add_action( 'wp_ajax_gv_get_active_areas', array( $this, 'get_active_areas' ) );

	}
	
	
	
	function register_metabox() {
		
		//select form box
		add_meta_box( 'gravityview_select_form', __( 'Select the Form', 'gravity-view' ), array( $this, 'render_select_form' ), 'gravityview', 'normal', 'high' );
		
		//View Configuration box
		add_meta_box( 'gravityview_directory_view', __( 'View Configuration', 'gravity-view' ), array( $this, 'render_view_configuration' ), 'gravityview', 'normal', 'high' );
		
		//information box
		add_meta_box( 'gravityview_shortcode_info', __( 'Shortcode Info', 'gravity-view' ), array( $this, 'render_shortcode_info' ), 'gravityview', 'side', 'default' );
		
	}
	
	
	/**
	 * Render html for 'select form' metabox
	 * 
	 * @access public
	 * @param object $post
	 * @return void
	 */
	function render_select_form( $post ) {
	
		// Use nonce for verification
		wp_nonce_field( 'gravityview_select_form', 'gravityview_select_form_nonce' );
		
		//current value
		$current = get_post_meta( $post->ID, '_gravityview_form_id', true );
		
		// input 
		echo '<label class="screen-reader-text" for="gravityview_form_id" >'. esc_html__( 'Select the Form', 'gravity-view' ) .'</label> ';
		// check for available gravity forms
		$forms = gravityview_get_forms();
		echo '<select name="gravityview_form_id" id="gravityview_form_id">';
		echo '	<option value="" '. selected( '', $current, false ) .'>-- '. esc_html__( 'list of forms', 'gravity-view' ) .' --</option>';
		foreach( $forms as $form ) {
			echo '	<option value="'. $form['id'] .'" '. selected( $form['id'], $current, false ) .'>'. $form['title'] .'</option>';
		}
		echo '</select>';
		
	}

	
	/**
	 * Render html for 'View Configuration' metabox
	 * 
	 * @access public
	 * @param mixed $post
	 * @return void
	 */
	function render_view_configuration( $post ) {
		
		// Use nonce for verification
		wp_nonce_field( 'gravityview_view_configuration', 'gravityview_view_configuration_nonce' );
		
		// Fetch available style templates
		$templates_directory = apply_filters( 'gravityview_register_directory_template', array() );
		$templates_single = apply_filters( 'gravityview_register_single_template', array() );
		
		// Selected Form
		$curr_form = get_post_meta( $post->ID, '_gravityview_form_id', true )

		
		?>
		<div id="tabs">
			<ul class="nav-tab-wrapper">
				<li><a href="#directory-view" class="nav-tab"><?php esc_html_e( 'Directory', 'gravity-view' ); ?></a></li>
				<li><a href="#single-view" class="nav-tab"><?php esc_html_e( 'Single Entry', 'gravity-view' ); ?></a></li>
			</ul>
			<div id="directory-view">
			
				<table class="form-table">
					<tr valign="top">
						<td scope="row">
							<label for="gravityview_directory_template"><?php esc_html_e( 'Directory View Template', 'gravity-view'); ?></label>
						</td>
						<td>
							<select name="gravityview_directory_template" id="gravityview_directory_template">
								<?php // get current directory template, by default, show table
								$current_template = get_post_meta( $post->ID, '_gravityview_directory_template', true );
								$current_template = empty( $current_template ) ? 'default_table' : $current_template;
								
								foreach( $templates_directory as $template ) {
									echo '<option value="'. esc_attr( $template['id'] ) .'" '. selected( $template['id'], $current_template, false ) .'>'. esc_html( $template['label'] ) .'</option>';
								} ?>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<td scope="row">
							<label for="gravityview_page_size"><?php esc_html_e( 'Number of entries to show per page', 'gravity-view'); ?></label>
						</td>
						<td>
							<?php $page_size_curr = get_post_meta( $post->ID, '_gravityview_page_size', true ); ?>
							<input name="gravityview_page_size" id="gravityview_page_size" type="number" step="1" min="1" value="<?php empty( $page_size_curr ) ? print 25 : print $page_size_curr; ?>" class="small-text">
						</td>
					</tr>
					<tr valign="top">
						<td scope="row">
							<label for="gravityview_only_approved"><?php esc_html_e( 'Show only entries approved', 'gravity-view'); ?></label>
						</td>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php esc_html_e( 'Show only entries approved', 'gravity-view'); ?></span></legend>
								<label for="gravityview_only_approved">
									<input name="gravityview_only_approved" type="checkbox" id="gravityview_only_approved" value="1" <?php checked( get_post_meta( $post->ID, '_gravityview_only_approved', true ) , 1, true ); ?>>
								</label>
							</fieldset>
						</td>
					</tr>
				</table>
				
				<hr>

				<div id="directory-fields" class="gv-section">
					<h4><?php esc_html_e( 'Fields Mapping', 'gravity-view'); ?></h4>
					
					<div id="directory-active-fields" class="gv-area">
					
						<?php echo $this->render_directory_active_areas( $current_template, $post->ID, 'directory' ); ?>

					</div>
					
					<div id="directory-available-fields">
						<fieldset class="area">
							<legend><?php esc_html_e( 'Available Fields', 'gravity-view' ); ?></legend>
							<?php echo $this->render_available_fields( $curr_form, 'directory' ); ?>
						</fieldset>
					</div>

				</div>

				<div class="clear"></div>
				<hr>
				
				<div class="gv-section">
					<?php // widgets new innterface proposal ?>
					<?php $widgets = get_post_meta( $post->ID, '_gravityview_directory_widgets', true ); ?>
					<h4><?php esc_html_e( 'Directory Header & Footer widgets', 'gravity-view'); ?></h4>
					
					<table class="form-table">
						<tr>
							<th>&nbsp;</th>
							<th><?php esc_html_e( 'Show in Header', 'gravity-view'); ?></th>
							<th><?php esc_html_e( 'Show in Footer', 'gravity-view'); ?></th>
							<th>&nbsp;</th>
						</tr>
						<?php do_action( 'gravityview_admin_view_widgets', $widgets ); ?>
					</table>
				</div>

			</div>
			
			
			
			<?php // Single View Tab ?>
			
			<div id="single-view">
				<table class="form-table">
					<tr valign="top">
						<td>
							<label for="gravityview_single_template"><?php esc_html_e( 'Single Entry Template', 'gravity-view'); ?></label>
						</td>
						<td>
							<select name="gravityview_single_template" id="gravityview_single_template">
								<?php // get current single entry template, or table by default
								$current_single_template = get_post_meta( $post->ID, '_gravityview_single_template', true );
								$current_single_template = empty( $current_single_template ) ? 'default_s_table' : $current_single_template;
								
								foreach( $templates_single as $template ) {
									echo '<option value="'. esc_attr( $template['id'] ) .'" '. selected( $template['id'], $current_single_template, false ) .'>'. esc_html( $template['label'] ) .'</option>';
								} ?>
							</select>
						</td>
					</tr>
				</table>
				
				<hr>

				<div id="single-fields" class="gv-section">
					<h4><?php esc_html_e( 'Fields Mapping', 'gravity-view'); ?></h4>
					
					<div id="single-active-fields" class="gv-area">
					
						<?php echo $this->render_directory_active_areas( $current_single_template, $post->ID, 'single' ); ?>

					</div>
					
					<div id="single-available-fields">
						<fieldset class="area">
							<legend><?php esc_html_e( 'Available Fields', 'gravity-view' ); ?></legend>
							<?php echo $this->render_available_fields( $curr_form, 'single' ); ?>
						</fieldset>
					</div>

				</div>

				<div class="clear"></div>

			</div> <?php // end single view tab ?>

		</div> <?php // end tabs ?>
		<?php
	}

	
	
	
	
	
	
	/**
	 * Render html shortcode info metabox.
	 * 
	 * @access public
	 * @param object $post
	 * @return void
	 */
	function render_shortcode_info( $post ) {
		echo '<p>';
		esc_html_e( 'To insert this view into a post or a page use the following shortcode:', 'gravity-view' );
		echo ' <code>[gravityview id="'. $post->ID .'"]</code></p>';
	}



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
	
		// save form id
		if ( isset( $_POST['gravityview_select_form_nonce'] ) && wp_verify_nonce( $_POST['gravityview_select_form_nonce'], 'gravityview_select_form' ) ) {
			update_post_meta( $post_id, '_gravityview_form_id', $_POST['gravityview_form_id'] );
		}
		
		// save View Configuration metabox
		if ( isset( $_POST['gravityview_view_configuration_nonce'] ) && wp_verify_nonce( $_POST['gravityview_view_configuration_nonce'], 'gravityview_view_configuration' ) ) {
			
			// -- directory tab --
			
			// Directory Template Id
			update_post_meta( $post_id, '_gravityview_directory_template', $_POST['gravityview_directory_template'] );
			
			// Directory number of entries per page
			if( isset( $_POST['gravityview_page_size'] ) && is_int( (int)$_POST['gravityview_page_size'] ) ) {
				update_post_meta( $post_id, '_gravityview_page_size', (int)$_POST['gravityview_page_size'] );
			} else {
				update_post_meta( $post_id, '_gravityview_page_size', 25 );
			}
			
			// Directory show only the approved entries
			if( !empty( $_POST['gravityview_only_approved'] ) ) {
				update_post_meta( $post_id, '_gravityview_only_approved', $_POST['gravityview_only_approved'] );
			} else {
				delete_post_meta( $post_id, '_gravityview_only_approved' );
			}
			
			// Directory Visible Fields
			if( empty( $_POST['fields'] ) ) {
				$_POST['fields'] = array();
			}
			update_post_meta( $post_id, '_gravityview_directory_fields', $_POST['fields'] );

			// Directory Visible Widgets
			if( empty( $_POST['widgets'] ) ) {
				$_POST['widgets'] = array();
			}
			update_post_meta( $post_id, '_gravityview_directory_widgets', $_POST['widgets'] );
			
			
			// -- single entry tab --
			update_post_meta( $post_id, '_gravityview_single_template', $_POST['gravityview_single_template'] );
			


		
		} // end save view configuration
		
	}
	
	
	/**
	 * Render html for displaying available fields based on a Form ID
	 * $blacklist_field_types - contains the field types which are not proper to be shown in a directory.
	 * 
	 * @access public
	 * @param string $form_id (default: '')
	 * @param string $context (default: 'single')
	 * @return void
	 */
	function render_available_fields( $form_id = '', $context = 'single' ) {
		
		$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array() );
		
		$fields = gravityview_get_form_fields( $form_id );
		
		$output = '';
		
		if( !empty( $fields ) ) {
			foreach( $fields as $id => $details ) {
				
				if( in_array( $details['type'], $blacklist_field_types ) ) {
					continue;
				}
			
				$output .= '<div data-fieldid="'. $id .'" class="gv-fields">';
				$output .= '<h5>'. $details['label'];
				$output .= '<span><a href="#settings" class="dashicons-admin-generic dashicons"></a>';
				$output .= '<a href="#remove" class="dashicons-dismiss dashicons"></a>';
				$output .= '</span></h5>';
				$output .= '</div>';
				
			}
		}
		
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
	function render_directory_active_areas( $template_id = '', $post_id = '', $context = 'single' ) {
		
		if( empty( $template_id ) ) {
			return;
		}
		
		$output = '';
		
		$template_areas = apply_filters( 'gravityview_template_active_areas', array(), $template_id );
		
		if( !empty( $post_id ) ) {
			$fields = get_post_meta( $post_id, '_gravityview_directory_fields', true );
			$available_fields = gravityview_get_form_fields( get_post_meta( $post_id, '_gravityview_form_id', true ) );
		}
	
		foreach( $template_areas as $area ) {
			$output .= '<fieldset class="area">';
			$output .= '<legend>'. $area['label'] .'</legend>';
	
			$output .= '<div id="'. $area['id'] .'" data-areaid="'. $area['areaid'] .'" class="active-drop">';
			
			// render saved fields
			if( !empty( $fields[ $area['areaid'] ] ) ) {
				foreach( $fields[ $area['areaid'] ] as $uniqid => $field ) {
				
					if( !empty( $available_fields[ $field['id'] ] ) ) {
						$output .= '<div data-fieldid="'. $field['id'] .'" class="gv-fields ui-draggable">';
						$output .= '<h5>'. $available_fields[ $field['id'] ]['label'];
						$output .= '<span><a href="#settings" class="dashicons-admin-generic dashicons"></a>';
						$output .= '<a href="#remove" class="dashicons-dismiss dashicons"></a>';
						$output .= '</span></h5>';
						$output .= $this->render_field_options( $field['id'], $available_fields[ $field['id'] ]['label'], $area['areaid'], $uniqid, $field, $context );
						$output .= '</div>';
					}
					
				}
			} else {
				$output .= '<span class="drop-message">'.esc_html__( 'Drop fields here', 'gravity-view' ).'</span>';
			}
			
			// close active area
			$output .= '</div>';
			$output .= '</fieldset>';
		}
	
		return $output;
		
	}
	

	
	
	
	function render_field_options( $field_id, $field_label, $area, $uniqid = '', $current = '', $context = 'single' ) {
		
		if( empty( $uniqid ) ) {
			//generate a unique field id
			$uniqid = uniqid('', false);
		}
		
		//current values
		$show_label = !empty( $current['show_label'] ) ? 1 : '';
		$show_as_link = !empty( $current['show_as_link'] ) ? 1 : '';
		$custom_class = !empty( $current['custom_class'] ) ? $current['custom_class'] : '';
		$custom_label = !empty( $current['custom_label'] ) ? $current['custom_label'] : '';
		$search_filter = !empty( $current['search_filter'] ) ? 1 : '';
		$only_loggedin = !empty( $current['only_loggedin'] ) ? 1 : '';
		$only_loggedin_cap = !empty( $current['only_loggedin_cap'] ) ? $current['only_loggedin_cap'] : 'read';
		
		$output = '';
		$output .= '<input type="hidden" class="field-key" name="fields['. $area .']['. $uniqid .'][id]" value="'. $field_id .'">';
		$output .= '<input type="hidden" class="field-label" name="fields['. $area .']['. $uniqid .'][label]" value="'. $field_label .'">';
		$output .= '<div class="gv-dialog-options" title="'. esc_attr__( 'Field Options', 'gravity-view' ) . ': '. $field_label .' ['. $field_id .']">';
		$output .= '<ul>';
		
		$output .= '<li>' . $this->render_checkbox_option( 'fields['. $area .']['. $uniqid .'][show_label]' , __( 'Show Label', 'gravity-view' ), $show_label ) . '</li>';
		$output .= '<li>' . $this->render_input_text_option( 'fields['. $area .']['. $uniqid .'][custom_label]' , __( 'Custom Label:', 'gravity-view' ), $custom_label ) . '</li>';
		$output .= '<li>' . $this->render_input_text_option( 'fields['. $area .']['. $uniqid .'][custom_class]' , __( 'Custom CSS Class:', 'gravity-view' ), $custom_class ) . '</li>';
		if( 'single' != $context ) {
			$output .= '<li>' . $this->render_checkbox_option( 'fields['. $area .']['. $uniqid .'][show_as_link]' , __( 'Link to single entry', 'gravity-view' ), $show_as_link ) . '</li>';
		}
		
		//logged-in visibility
		$select_cap_choices = array(
			array( 'label' => __( 'Any', 'gravity-view' ), 'value' => 'read' ),
			array( 'label' => __( 'Author or higher', 'gravity-view' ), 'value' => 'publish_posts' ),
			array( 'label' => __( 'Editor or higher', 'gravity-view' ), 'value' => 'delete_others_posts' ),
			array( 'label' => __( 'Administrator', 'gravity-view' ), 'value' => 'manage_options' ),
		);
		$select_cap = $this->render_selectbox_option( 'fields['. $area .']['. $uniqid .'][only_loggedin_cap]', '', $select_cap_choices, $only_loggedin_cap );
		$output .= '<li>' . $this->render_checkbox_option( 'fields['. $area .']['. $uniqid .'][only_loggedin]' , sprintf( __( 'Only visible to logged in users with %s role', 'gravity-view' ), $select_cap), $only_loggedin ) . '</li>';
		
		//todo: make a hook to insert widget related field options
		$output .= '<li>' . $this->render_checkbox_option( 'fields['. $area .']['. $uniqid .'][search_filter]' , __( 'Use this field as a search filter', 'gravity-view' ), $search_filter ) . '</li>';
		
		$output .= '</ul>';
		$output .= '</div>';
		
		
		return $output;
		
	}
	
	
	
	
	public static function render_checkbox_option( $name = '', $label = '', $current = '' ) {
		$id = sanitize_html_class( $name );
		
		$output = '';
		$output .= '<input name="'. $name .'" id="'. $id .'" type="checkbox" value="1" '. checked( $current, '1', false ) .' >';
		$output .= '<label for="'. $id .'" class="gv-label-checkbox">'. $label .'</label>';
		
		return $output;
	}
	
	
	
	
	public static function render_input_text_option( $name = '', $label = '', $current = '' ) {
		$id = sanitize_html_class( $name );
		
		$output = '';
		$output .= '<label for="'. $id .'" class="gv-label-text">'. $label .'</label>';
		$output .= '<input name="'. $name .'" id="'. $id .'" type="text" value="'. $current .'" class="all-options">';

		return $output;
	}
	
	public static function render_selectbox_option( $name = '', $label = '', $choices, $current = '' ) {
		$id = sanitize_html_class( $name );
		$output = '';
		
		if( !empty( $label ) ) {
			$output .= '<label for="'. $id .'">'. $label .'</label>';
		}
		$output .= '<select name="'. $name .'" id="'. $id .'">';
		foreach( $choices as $choice ) {
			$output .= '<option value="'. $choice['value'] .'" '. selected( $choice['value'], $current, false ) .'>'. $choice['label'] .'</option>';
		}
		$output .= '</select>';
		
		return $output;
	}
	
	
	
	
	function render_available_widgets() {
		
		$widgets = self::get_widgets_list();
	
		$output = '';
		foreach( $widgets as $id => $widget ) {
			$output .= '<div data-widgetid="'. $id .'" class="gv-widgets">';
			$output .= '<h5>'. $widget['label'] .'</h5>';
			$output .= '</div>';
		}
		
		return $output;
	}
	
	
	
	public static function get_widgets_list() {
	
		$default['page_links'] = array( 'label' => 'Page Links' );
		$default['search_filters'] = array( 'label' => 'Search Filters' );
		$default['page_info'] = array( 'label' => 'Page Info' );
		$default['date_filters'] = array( 'label' => 'Date Filters' );
		
		return $default;
	}
	
	
	
	function render_widget_options( $widget_id, $widget_label, $area, $uniqid = '', $post_id = '' ) {
		
		if( empty( $uniqid ) ) {
			//generate a unique field id
			$uniqid = uniqid('', false);
		}
		
		$output = '';
		$output .= '<input type="hidden" name="widgets['. $area .']['. $uniqid .'][id]" value="'. $widget_id .'">';
		
		return $output;
		
	}
	
	
	
	function render_active_widgets( $area, $saved_widgets, $post_id = '' ) {
		
		$available_widgets = self::get_widgets_list();
		
		if( empty( $area ) ) {
			return '';
		}
		
		$widgets = isset( $saved_widgets[ $area ] ) ? $saved_widgets[ $area ] : '';
		
		$output = '<div data-areaid="'. $area .'" class="widget-drop">';
		if( empty( $widgets ) ) {
			$output .= '<span class="drop-message">'. esc_html__( 'Drop widgets here', 'gravity-view') .'</span>';
		} else {
			foreach( $widgets as $key => $widget ) {
				if( !empty( $available_widgets[ $widget['id'] ] ) ) {
					$output .= '<div data-widgetid="'. $widget['id'] .'" class="gv-widgets ui-draggable">';
					$output .= '<h5>'. $available_widgets[ $widget['id'] ]['label'] .'</h5>';
					$output .= $this->render_widget_options( $widget['id'], $available_widgets[ $widget['id'] ]['label'], $area, $key, $post_id );
					$output .= '</div>';
				}
			}
		}
		$output .= '</div>';
		
		return $output;
	}

	
	
	/** AJAX stuff */
	
	
	/**
	 * Returns available fields given a form ID.
	 * 
	 * @access public
	 * @return void
	 */
	function get_available_fields() {
		$response = false;
		
		if( empty( $_POST['formid'] ) ) {
			echo $response;
			die();
		}
		
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxviews' ) ) {
			echo $response;
			die();
		}
		
		$response = $this->render_available_fields( $_POST['formid'] );
		echo $response;
		die();
	}
	
	
	/**
	 * Returns template active areas given a template ID
	 * 
	 * @access public
	 * @return void
	 */
	function get_active_areas() {
		$response = false;
		
		if( empty( $_POST['template_id'] ) ) {
			echo $response;
			die();
		}
		
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxviews' ) ) {
			echo $response;
			die();
		}
		
		$response = $this->render_directory_active_areas( $_POST['template_id'] );
		echo $response;
		die();
	}
	
	
	
	/**
	 * Returns field options - called by ajax when dropping fields into active areas
	 * 
	 * @access public
	 * @return void
	 */
	function get_field_options() {
		$response = false;
		
		if( empty( $_POST['area'] ) || empty( $_POST['field_id'] ) || empty( $_POST['field_label'] ) ) {
			echo $response;
			die();
		}
		
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxviews' ) ) {
			echo $response;
			die();
		}
		
		$response = $this->render_field_options( $_POST['field_id'], $_POST['field_label'], $_POST['area'] );
		echo $response;
		die();
	}
	
	
	/**
	 * get_widget_options function.
	 * 
	 * @access public
	 * @return void
	 */
	function get_widget_options() {
		$response = false;
		
		if( empty( $_POST['area'] ) || empty( $_POST['widget_id'] ) || empty( $_POST['widget_label'] ) ) {
			echo $response;
			die();
		}
		
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxviews' ) ) {
			echo $response;
			die();
		}
		
		$response = $this->render_widget_options( $_POST['widget_id'], $_POST['widget_label'], $_POST['area'] );
		echo $response;
		die();
	}
	
	
	
	
	
	function add_scripts_and_styles( $hook ) {
		global $current_screen;
		
		if( !in_array( $hook , array( 'post.php' , 'post-new.php' ) ) || ( !empty($current_screen->post_type) && 'gravityview' != $current_screen->post_type ) ) {
			return;
		}
		
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		
		//enqueue scripts
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		
		wp_register_script( 'gravityview_views_scripts', GRAVITYVIEW_URL . 'includes/js/admin-views.js', array( 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'jquery-ui-dialog' ) );
		wp_enqueue_script( 'gravityview_views_scripts');
		


/*wp_localize_script( 'gravityview_views_scripts', 'active_langs', array( 'all' => $this->active_langs['all'], 'default_lang' => $this->active_langs['default'], 'default_label' => __('Default','gpoliglota') ) );*/

		wp_localize_script('gravityview_views_scripts', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'gravityview_ajaxviews' ) ) );
		
		//enqueue styles
		wp_register_style( 'gravityview_views_styles', GRAVITYVIEW_URL . 'includes/css/admin-views.css', array() );
		wp_enqueue_style( 'gravityview_views_styles' );
	}


}








?>
