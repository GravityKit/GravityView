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
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_styles'), 999 );
		add_filter( 'gravityview_noconflict_styles', array( $this, 'register_no_conflict') );
		add_filter( 'gravityview_noconflict_scripts', array( $this, 'register_no_conflict') );

		// AJAX
		//get field options
		add_action( 'wp_ajax_gv_field_options', array( $this, 'get_field_options' ) );

		// get available fields
		add_action( 'wp_ajax_gv_available_fields', array( $this, 'get_available_fields' ) );

		// get active areas
		add_action( 'wp_ajax_gv_get_active_areas', array( $this, 'get_active_areas' ) );
	}



	function register_metabox() {

		// select data source for this view
		add_meta_box( 'gravityview_select_form', __( 'Data Source', 'gravity-view' ), array( $this, 'render_select_form' ), 'gravityview', 'normal', 'high' );

		// select view type/template
		add_meta_box( 'gravityview_select_template', __( 'Choose a View Type', 'gravity-view' ), array( $this, 'render_select_template' ), 'gravityview', 'normal', 'high' );

		// View Configuration box
		add_meta_box( 'gravityview_directory_view', __( 'View Configuration', 'gravity-view' ), array( $this, 'render_view_configuration' ), 'gravityview', 'normal', 'high' );

		// information box
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

		// input ?>
		<label for="gravityview_form_id" ><?php esc_html_e( 'Where would you like the data to come from for this View?', 'gravity-view' ); ?></label>

		<?php
		// check for available gravity forms
		$forms = gravityview_get_forms();

		// render "start fresh" button ?>
		<p>
			<a class="button-primary" href="#" title="<?php esc_attr_e( 'Start Fresh', 'gravity-view' ); ?>"><?php esc_html_e( 'Start Fresh', 'gravity-view' ); ?></a>

			<span>&nbsp;<?php esc_html_e( 'or use an existing form', 'gravity-view' ); ?>&nbsp;</span>

			<?php // render select box ?>
			<select name="gravityview_form_id" id="gravityview_form_id">
				<option value="" <?php selected( '', $current, true ); ?>>-- <?php esc_html_e( 'list of forms', 'gravity-view' ); ?> --</option>
				<?php foreach( $forms as $form ) : ?>
					<option value="<?php echo $form['id']; ?>" <?php selected( $form['id'], $current, true ); ?>><?php echo $form['title']; ?></option>
				<?php endforeach; ?>
			</select>
		</p>

		<?php // confirm dialog box ?>
		<div id="gravityview_form_id_dialog" class="gv-dialog-options" title="<?php esc_attr_e( 'Attention', 'gravity-view' ); ?>">
			<p><?php esc_html_e( 'Changing the form will discard all the existent View configuration.', 'gravity-view' ); ?></p>
		</div>

		<?php // no js notice ?>
		<div class="error hide-if-js">
			<p><?php esc_html_e( 'GravityView requires Javascript to be enabled.', 'gravity-view' ); ?></p>
		</div>

		<?php
	}


	/**
	 * Render html for 'select template' metabox
	 *
	 * @access public
	 * @param object $post
	 * @return void
	 */
	function render_select_template() {

		// Use nonce for verification
		wp_nonce_field( 'gravityview_select_template', 'gravityview_select_template_nonce' );

		//current value
		$current_template = get_post_meta( $post->ID, '_gravityview_directory_template', true );

		// Fetch available style templates
		$templates = apply_filters( 'gravityview_register_directory_template', array() );

		// list all the available templates (type= fresh or custom )
		?>
		<div id="">
			<?php foreach( $templates as $id => $template ) : ?>
				<div class="gv-template" data-type="<?php echo esc_attr( $template['type'] ); ?>">
					<label for="gv_directory_template_<?php echo esc_attr( $id ); ?>">
						<input type="radio" class="hide-if-js" id="gv_directory_template_<?php echo esc_attr( $id ); ?>" name="gravityview_directory_template" value="<?php echo esc_attr( $id ); ?>" <?php checked( $id, $current_template, true ); ?>>
						<img src="<?php echo esc_url( $template['preview'] ); ?>" alt="<?php echo esc_attr( $template['label'] ); ?>">
					</label>
				</div>
			<?php endforeach; ?>
		</div>

<?php

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

		// Selected Form
		$curr_form = get_post_meta( $post->ID, '_gravityview_form_id', true );

		// Selected template
		$curr_template = get_post_meta( $post->ID, '_gravityview_directory_template', true );

		// View template settings
		$template_settings = get_post_meta( $post->ID, '_gravityview_template_settings', true );

		?>
		<div id="tabs">

			<input type="hidden" name="gv-active-tab" id="gv-active-tab" value="<?php echo get_post_meta( $post->ID, '_gravityview_tab_active', true ); ?>">

			<ul class="nav-tab-wrapper">
				<li><a href="#directory-view" class="nav-tab"><?php esc_html_e( 'Directory', 'gravity-view' ); ?></a></li>
				<li><a href="#single-view" class="nav-tab"><?php esc_html_e( 'Single Entry', 'gravity-view' ); ?></a></li>
			</ul>

			<div id="directory-view">

				<div id="directory-fields" class="gv-section">

					<h4><?php esc_html_e( 'Customize your directory view', 'gravity-view'); ?></h4>

					<?php //render header widget areas ?>

					<?php //render Listing areas ?>

					<?php //render footer widget areas ?>



				</div>

				<hr>

				<?php // Other View Settings ?>

				<table class="form-table">

					<tr valign="top">
						<td scope="row">
							<label for="gravityview_page_size"><?php esc_html_e( 'Number of entries to show per page', 'gravity-view'); ?></label>
						</td>
						<td>
							<input name="template_settings[page_size]" id="gravityview_page_size" type="number" step="1" min="1" value="<?php empty( $template_settings['page_size'] ) ? print 25 : print $template_settings['page_size']; ?>" class="small-text">
						</td>
					</tr>
					<tr valign="top">
						<td scope="row">
							<label for="gravityview_only_approved"><?php esc_html_e( 'Show only entries approved', 'gravity-view' ); ?></label>
						</td>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php esc_html_e( 'Show only entries approved', 'gravity-view' ); ?></span></legend>
								<label for="gravityview_only_approved">
									<input name="template_settings[show_only_approved]" type="checkbox" id="gravityview_only_approved" value="1" <?php empty( $template_settings['show_only_approved'] ) ? print '' : checked( $template_settings['show_only_approved'] , 1, true ); ?>>
								</label>
							</fieldset>
						</td>
					</tr>

					<?php // Hook for other template custom settings

					do_action( 'gravityview_admin_directory_settings', $template_settings );

					?>

				</table>


			</div>



			<?php // Single View Tab ?>

			<div id="single-view">

				<div id="single-fields" class="gv-section">
					<h4><?php esc_html_e( 'Customize your single view', 'gravity-view'); ?></h4>


				</div>

				<hr>

				<table class="form-table">

					<?php // Hook for other template custom settings

					do_action( 'gravityview_admin_single_settings', $template_settings );

					?>

				</table>

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

		//set active tab index
		$active_tab = empty( $_POST['gv-active-tab'] ) ? 0 : $_POST['gv-active-tab'];
		update_post_meta( $post_id, '_gravityview_tab_active', $active_tab );

		// save form id
		if ( isset( $_POST['gravityview_select_form_nonce'] ) && wp_verify_nonce( $_POST['gravityview_select_form_nonce'], 'gravityview_select_form' ) ) {
			update_post_meta( $post_id, '_gravityview_form_id', $_POST['gravityview_form_id'] );
		}

		// save template id
		if ( isset( $_POST['gravityview_select_template_nonce'] ) && wp_verify_nonce( $_POST['gravityview_select_template_nonce'], 'gravityview_select_template' ) ) {
			update_post_meta( $post_id, '_gravityview_directory_template', $_POST['gravityview_directory_template'] );
		}

		// save View Configuration metabox
		if ( isset( $_POST['gravityview_view_configuration_nonce'] ) && wp_verify_nonce( $_POST['gravityview_view_configuration_nonce'], 'gravityview_view_configuration' ) ) {

			// Directory Visible Fields
			if( empty( $_POST['template_settings'] ) ) {
				$_POST['template_settings'] = array();
			}
			update_post_meta( $post_id, '_gravityview_template_settings', $_POST['template_settings'] );

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

		$fields = gravityview_get_form_fields( $form_id, true );

		$output = '';

		if( !empty( $fields ) ) {
			foreach( $fields as $id => $details ) {

				if( in_array( $details['type'], $blacklist_field_types ) ) {
					continue;
				}

				$output .= '<div data-fieldid="'. $id .'" class="gv-fields">';
				$output .= '<h5>'. $details['label'] . '</h5>';
				$output .= '<span class="gv-field-controls"><a href="#settings" class="dashicons-admin-generic dashicons"></a>';
				$output .= '<a href="#remove" class="dashicons-dismiss dashicons"></a>';
				$output .= '</span>';
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
			$available_fields = gravityview_get_form_fields( get_post_meta( $post_id, '_gravityview_form_id', true ), true );
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
						$output .= '<h5>'. $available_fields[ $field['id'] ]['label'] . '</h5>';
						$output .= '<span class="gv-field-controls"><a href="#settings" class="dashicons-admin-generic dashicons"></a>';
						$output .= '<a href="#remove" class="dashicons-dismiss dashicons"></a>';
						$output .= '</span>';
						$output .= $this->render_field_options( $template_id, $field['id'], $available_fields[ $field['id'] ]['label'], $area['areaid'], $uniqid, $field, $context );
						$output .= '</div>';
					}

				}

			}

			$output .= '<span class="drop-message">'. esc_html__( 'Drop fields here', 'gravity-view' ).'</span>';
			// close active area
			$output .= '</div>';
			$output .= '</fieldset>';
		}

		return $output;

	}

	/**
	 * Render Field Options html (shown through a dialog box)
	 *
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
	function render_field_options( $template_id, $field_id, $field_label, $area, $uniqid = '', $current = '', $context = 'single' ) {

		if( empty( $uniqid ) ) {
			//generate a unique field id
			$uniqid = uniqid('', false);
		}

		// generic field options
		$field_options = array(
			'show_label' => array( 'type' => 'checkbox', 'label' => __( 'Show Label', 'gravity-view' ), 'default' => true ),
			'custom_label' => array( 'type' => 'input_text', 'label' => __( 'Custom Label:', 'gravity-view' ), 'default' => '' ),
			'custom_class' => array( 'type' => 'input_text', 'label' => __( 'Custom CSS Class:', 'gravity-view' ), 'default' => '' ),
		);

		//get defined field options
		$field_options = apply_filters( 'gravityview_template_field_options', $field_options, $template_id );

		// build output
		$output = '';
		$output .= '<input type="hidden" class="field-key" name="fields['. $area .']['. $uniqid .'][id]" value="'. $field_id .'">';
		$output .= '<input type="hidden" class="field-label" name="fields['. $area .']['. $uniqid .'][label]" value="'. $field_label .'">';
		$output .= '<div class="gv-dialog-options" title="'. esc_attr__( 'Field Options', 'gravity-view' ) . ': '. $field_label .' ['. $field_id .']">';
		$output .= '<ul>';

		foreach( $field_options as $key => $details ) {

			$default = isset( $details['default'] ) ? $details['default'] : '';
			//$default = '';
			$curr_value = isset( $current[ $key ] ) ? $current[ $key ] : $default;
			$label = isset( $details['label'] ) ? $details['label'] : '';
			$type = isset( $details['type'] ) ? $details['type'] : 'input_text';

			switch( $type ) {
				case 'checkbox':
					$output .= '<li>'. $this->render_checkbox_option( 'fields['. $area .']['. $uniqid .']['. $key .']' , $label, $curr_value ) .'</li>';
					break;

				case 'input_text':
				default:
					$output .= '<li>'. $this->render_input_text_option( 'fields['. $area .']['. $uniqid .']['. $key .']' , $label, $curr_value ) .'</li>';
					break;

			}

		}


		$search_filter = !empty( $current['search_filter'] ) ? 1 : '';
		$only_loggedin = !empty( $current['only_loggedin'] ) ? 1 : '';
		$only_loggedin_cap = !empty( $current['only_loggedin_cap'] ) ? $current['only_loggedin_cap'] : 'read';

		// default logged-in visibility
		$select_cap_choices = array(
			array( 'label' => __( 'Any', 'gravity-view' ), 'value' => 'read' ),
			array( 'label' => __( 'Author or higher', 'gravity-view' ), 'value' => 'publish_posts' ),
			array( 'label' => __( 'Editor or higher', 'gravity-view' ), 'value' => 'delete_others_posts' ),
			array( 'label' => __( 'Administrator', 'gravity-view' ), 'value' => 'manage_options' ),
		);
		$output .= '<li>' . $this->render_checkbox_option( 'fields['. $area .']['. $uniqid .'][only_loggedin]' , __( 'Only visible to logged in users with role:', 'gravity-view' ) ) ;
		$output .=  $this->render_selectbox_option( 'fields['. $area .']['. $uniqid .'][only_loggedin_cap]', '', $select_cap_choices, $only_loggedin_cap ) . '</li>';

		//todo: make a hook to insert widget related field options
		$output .= '<li>' . $this->render_checkbox_option( 'fields['. $area .']['. $uniqid .'][search_filter]' , __( 'Use this field as a search filter', 'gravity-view' ), $search_filter ) . '</li>';


		// close options window
		$output .= '</ul>';
		$output .= '</div>';

		return $output;

	}



	/**
	 * Render the HTML for a checkbox input to be used on the field & widgets options
	 * @param  string $name , name attribute
	 * @param  string $label   label text
	 * @param  string $current current value
	 * @return string         html tags
	 */
	public static function render_checkbox_option( $name = '', $label = '', $current = '' ) {
		$id = sanitize_html_class( $name );

		$output = '';
		$output .= '<input name="'. $name .'" type="hidden" value="0">';
		$output .= '<input name="'. $name .'" id="'. $id .'" type="checkbox" value="1" '. checked( $current, '1', false ) .' >';
		$output .= '<label for="'. $id .'" class="gv-label-checkbox">'. $label .'</label>';

		return $output;
	}


	/**
	 * Render the HTML for an input text to be used on the field & widgets options
	 * @param  string $name    [name attribute]
	 * @param  string $label   [label text]
	 * @param  string $current [current value]
	 * @return string         [html tags]
	 */
	public static function render_input_text_option( $name = '', $label = '', $current = '' ) {
		$id = sanitize_html_class( $name );

		$output = '';
		$output .= '<label for="'. $id .'" class="gv-label-text">'. $label .'</label>';
		$output .= '<input name="'. $name .'" id="'. $id .'" type="text" value="'. $current .'" class="all-options">';

		return $output;
	}

	/**
	 * Render the HTML for a select box to be used on the field & widgets options
	 * @param  string $name    [name attribute]
	 * @param  string $label   [label text]
	 * @param  array $choices [select options]
	 * @param  string $current [current value]
	 * @return string          [html tags]
	 */
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


	/**
	 * Returns available fields given a form ID.
	 * AJAX callback
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
	 * AJAX callback
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
	 * AJAX callback
	 *
	 * @access public
	 * @return void
	 */
	function get_field_options() {
		$response = false;

		if( empty( $_POST['template'] ) || empty( $_POST['area'] ) || empty( $_POST['field_id'] ) || empty( $_POST['field_label'] ) ) {
			echo $response;
			die();
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxviews' ) ) {
			echo $response;
			die();
		}

		$response = $this->render_field_options( $_POST['template'], $_POST['field_id'], $_POST['field_label'], $_POST['area'] );
		echo $response;
		die();
	}


	/**
	 * Enqueue scripts and styles at Views editor
	 *
	 * @access public
	 * @param mixed $hook
	 * @return void
	 */
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

		wp_enqueue_script( 'gravityview_views_scripts', GRAVITYVIEW_URL . 'includes/js/admin-views.js', array( 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'jquery-ui-dialog' ) );

		wp_localize_script('gravityview_views_scripts', 'gvGlobals', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'gravityview_ajaxviews' ),
			'label_close' => __( 'Close', 'gravity-view' ),
			'label_cancel' => __( 'Cancel', 'gravity-view' ),
			'label_continue' => __( 'Continue', 'gravity-view' ),
			'label_ok' => __( 'Ok', 'gravity-view' ),
		));

		//enqueue styles
		wp_enqueue_style( 'gravityview_views_styles', GRAVITYVIEW_URL . 'includes/css/admin-views.css', array() );
	}

	function register_no_conflict( $registered ) {

		$filter = current_filter();

		if( 'gravityview_noconflict_scripts' === $filter ) {
			$allow_scripts = array( 'jquery-ui-dialog', 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'gravityview_views_scripts' );
			$registered = array_merge( $registered, $allow_scripts );
		} elseif( 'gravityview_noconflict_styles' === $filter ) {
			$allow_styles = array( 'dashicons', 'wp-jquery-ui-dialog', 'gravityview_views_styles' );
			$registered = array_merge( $registered, $allow_styles );
		}

		return $registered;
	}


}

new GravityView_Admin_Views;