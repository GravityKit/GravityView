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

	private $post_id;

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
		add_action( 'wp_ajax_gv_available_fields', array( $this, 'get_available_fields_html' ) );

		// get active areas
		add_action( 'wp_ajax_gv_get_active_areas', array( $this, 'get_active_areas' ) );
	}



	function register_metabox() {

		// select data source for this view
		add_meta_box( 'gravityview_select_form', __( 'Data Source', 'gravity-view' ), array( $this, 'render_select_form' ), 'gravityview', 'normal', 'high' );

		// select view type/template
		add_meta_box( 'gravityview_select_template', __( 'Choose a View Type', 'gravity-view' ), array( $this, 'render_select_template' ), 'gravityview', 'normal', 'high' );

		// View Configuration box
		add_meta_box( 'gravityview_view_config', __( 'View Configuration', 'gravity-view' ), array( $this, 'render_view_configuration' ), 'gravityview', 'normal', 'high' );

		// Other Settings box
		add_meta_box( 'gravityview_template_settings', __( 'View Settings', 'gravity-view' ), array( $this, 'render_view_settings' ), 'gravityview', 'side', 'core' );

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

		if( !empty( $post->ID ) ) {
			$this->post_id = $post->ID;
		}

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
			<a class="button-primary" href="#gv_start_fresh" title="<?php esc_attr_e( 'Start Fresh', 'gravity-view' ); ?>"><?php esc_html_e( 'Start Fresh', 'gravity-view' ); ?></a>

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
		// hidden field to keep track of start fresh state ?>
		<input type="hidden" id="gravityview_form_id_start_fresh" name="gravityview_form_id_start_fresh" value="0">
		<?php
	}


	/**
	 * Render html for 'select template' metabox
	 *
	 * @access public
	 * @param object $post
	 * @return void
	 */
	function render_select_template( $post ) {

		// Use nonce for verification
		wp_nonce_field( 'gravityview_select_template', 'gravityview_select_template_nonce' );

		//current value
		$current_template = get_post_meta( $post->ID, '_gravityview_directory_template', true );

		// Fetch available style templates
		$templates = apply_filters( 'gravityview_register_directory_template', array() );


		// current input ?>
		<input type="hidden" id="gravityview_directory_template" name="gravityview_directory_template" value="<?php echo esc_attr( $current_template ); ?>">

		<?php // list all the available templates (type= fresh or custom ) ?>
		<div class="gv-grid">
			<?php foreach( $templates as $id => $template ) :
				$selected = ( $id == $current_template ) ? ' gv-selected' : ''; ?>

				<div class="gv-grid-col-1-3">
					<div class="gv-view-types-module<?php echo $selected; ?>" data-filter="<?php echo esc_attr( $template['type'] ); ?>">
						<div class="gv-view-types-hover">
							<div>
								<?php if( !empty( $template['buy_source'] ) ) : ?>
									<p><a href="<?php echo esc_url( $template['buy_source'] ); ?>" class="button-primary button-buy-now"><?php esc_html_e( 'Buy Now', 'gravity-view'); ?></a></p>
								<?php else: ?>
									<p><a href="#gv_select_template" class="button-primary" data-templateid="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Select', 'gravity-view'); ?></a></p>
									<p><a href="#gv_preview_template" class="button-secondary"><?php esc_html_e( 'Preview', 'gravity-view'); ?></a></p>
								<?php endif; ?>
							</div>
						</div>
						<div class="gv-view-types-normal">
							<img src="<?php echo esc_url( $template['logo'] ); ?>" alt="<?php echo esc_attr( $template['label'] ); ?>">
							<h5><?php echo esc_attr( $template['label'] ); ?></h5>
							<p class="description"><?php echo esc_attr( $template['description'] ); ?></p>
						</div>
					</div>
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


					<h4><?php esc_html_e( 'Above Listings', 'gravity-view'); ?> <span><?php esc_html_e( 'Define the header widgets', 'gravity-view'); ?></span></h4>

					<?php echo $this->render_widgets_active_areas( $curr_template, 'header', $post->ID ); ?>

					<h4><?php esc_html_e( 'Listings', 'gravity-view'); ?> <span><?php esc_html_e( 'Configure the entry layout', 'gravity-view'); ?></span></h4>

					<div id="directory-active-fields" class="gv-grid gv-grid-pad gv-grid-border">
						<?php if(!empty( $curr_template ) ) {
							echo $this->render_directory_active_areas( $curr_template, 'directory', $post->ID );
						} ?>
					</div>

					<h4><?php esc_html_e( 'Below Listings', 'gravity-view'); ?> <span><?php esc_html_e( 'Define the footer widgets', 'gravity-view'); ?></span></h4>

					<?php echo $this->render_widgets_active_areas( $curr_template, 'footer', $post->ID ); ?>


					<?php // list of available fields to be shown in the popup ?>
					<div id="directory-available-fields" class="hide-if-js">
						<?php echo $this->render_available_fields( $curr_form, true ); ?>
					</div>

					<?php // list of available widgets to be shown in the popup ?>
					<div id="directory-available-widgets" class="hide-if-js">
						<?php echo $this->render_available_widgets(); ?>
					</div>

				</div>


			</div><?php //end directory tab ?>



			<?php // Single View Tab ?>

			<div id="single-view">

				<div id="single-fields" class="gv-section">
					<h4><?php esc_html_e( 'Customize your single view', 'gravity-view'); ?></h4>


				</div>

			</div> <?php // end single view tab ?>

		</div> <?php // end tabs ?>
		<?php
	}


	/**
	 * Render html View General Settings
	 *
	 * @access public
	 * @param object $post
	 * @return void
	 */
	function render_view_settings( $post ) {

		// View template settings
		$template_settings = get_post_meta( $post->ID, '_gravityview_template_settings', true );
		?>

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

error_log( 'this $POST: ' . print_r( $_POST , true ) );
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

		// Check if we have a template id
		if ( isset( $_POST['gravityview_select_template_nonce'] ) && wp_verify_nonce( $_POST['gravityview_select_template_nonce'], 'gravityview_select_template' ) ) {

			$template_id = $_POST['gravityview_directory_template'];
		}

		// check if this is a start fresh View
		if ( isset( $_POST['gravityview_select_form_nonce'] ) && wp_verify_nonce( $_POST['gravityview_select_form_nonce'], 'gravityview_select_form' ) ) {

			if( !empty( $_POST['gravityview_form_id_start_fresh'] ) && !empty( $template_id ) ) {

				// get the xml for this specific template_id
				$preset_xml_path = apply_filters( 'gravityview_template_formxml', '', $template_id );

				// import form
				$form_id = $this->import_form( $preset_xml_path );

				// get the form ID
				if( $form_id === false ) {
					// send error to user
					error_log( 'this error on form insert: ' . print_r( $preset_xml_path , true ) );
				}

			} else {
				$form_id = $_POST['gravityview_form_id'];
			}

			// save form id
			update_post_meta( $post_id, '_gravityview_form_id', $form_id );
		}

		// now save template id
		if( !empty( $template_id ) ) {
			update_post_meta( $post_id, '_gravityview_directory_template', $template_id );
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
	 * Import Gravity Form XML
	 * @param  string $xml_path Path to form xml file
	 * @return int | bool       Imported form ID or false
	 */
	function import_form( $xml_path ) {

		if( empty( $xml_path ) || !class_exists('GFExport') || !file_exists( $xml_path ) ) {
			error_log( 'NOT FOUND: ' . print_r( $xml_path , true ) );
			return false;
		}

		// import form
		$forms = '';
		$count = GFExport::import_file( $xml_path, $forms );

		if( $count != 1 || empty( $forms[0]['id'] ) ) {
			return false;
		}

		// import success - return form id
		return $forms[0]['id'];
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

		$fields = $this->get_available_fields( $form_id );

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
	 * Retrieve the default fields id, label and type
	 * @return array
	 */
	function get_entry_default_fields() {
		$entry_default_fields = array(
		        'id' => array( 'label' => 'Entry Id', 'type' => 'id'),
                'ip' => array( 'label' => 'User IP', 'type' => 'ip'),
                'date_created' => array( 'label' => 'Entry Date', 'type' => 'date_created'),
                'source_url' => array( 'label' => 'Source Url', 'type' => 'source_url'),
                'created_by' => array( 'label' => 'User', 'type' => 'created_by'),
        );
        return apply_filters( 'gravityview_entry_default_fields', $entry_default_fields );
	}

	/**
	 * Calculate the available fields
	 * @param  string $form_id Form ID
	 * @return array         fields
	 */
	function get_available_fields( $form_id = '' ) {
		if( empty( $form_id ) ) {
			return array();
		}

		// get form fields
		$fields = gravityview_get_form_fields( $form_id, true );

		// get meta fields
		$meta_fields = gravityview_get_entry_meta( $form_id );

		// get default fields
		$default_fields = $this->get_entry_default_fields();

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

		$output = '';

		if( !empty( $widgets ) ) {
			foreach( $widgets as $id => $details ) {

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
	 * Generic function to render rows and columns of active areas for widgets & fields
	 * @param  string $type   Either 'widget' or 'field'
	 * @param  string $zone   Either 'single', 'directory', 'header', 'footer'
	 * @param  array $rows    The layout structure: rows, columns and areas
	 * @param  array $values  Saved objects
	 * @return void
	 */
	function render_active_areas( $template_id, $type, $zone, $rows, $values ) {

		if( $type === 'widget' ) {
			$button_label = __( 'Add Widget', 'gravity-view' );
		} elseif( $type === 'field' ) {
			$button_label = __( 'Add Field', 'gravity-view' );
		}

		// if saved values, get available fields to label everyone
		if( !empty( $values ) && 'field' === $type && !empty( $this->post_id ) ) {
			$form_id = get_post_meta( $this->post_id, '_gravityview_form_id', true );
			$available_fields = $this->get_available_fields( $form_id );
		}

error_log( 'Values ' . print_r( $values , true ) );

		foreach( $rows as $row ) :
			foreach( $row as $col => $areas ) :
				$column = ($col == '2-2') ? '1-2' : $col; ?>

				<div class="gv-grid-col-<?php echo esc_attr( $column ); ?>">

					<?php foreach( $areas as $area ) : ?>

						<div class="gv-droppable-area">
							<div class="active-drop active-drop-<?php echo $type; ?>" data-areaid="<?php echo esc_attr( $zone .'_'. $area['areaid'] ); ?>">

								<?php // render saved fields
								if( !empty( $values[ $zone .'_'. $area['areaid'] ] ) ) :
error_log( 'this $area[] ' . print_r( $area['areaid'] , true ) );
									foreach( $values[ $zone .'_'. $area['areaid'] ] as $uniqid => $field ) :

										if( !empty( $available_fields[ $field['id'] ] ) ) : ?>

											<div data-fieldid="<?php echo $field['id']; ?>" class="gv-fields">
												<h5><?php echo $available_fields[ $field['id'] ]['label']; ?></h5>
												<span class="gv-field-controls">
													<a href="#settings" class="dashicons-admin-generic dashicons"></a>
													<a href="#remove" class="dashicons-dismiss dashicons"></a>
												</span>
												<?php echo $this->render_field_options( $type, $template_id, $field['id'], $available_fields[ $field['id'] ]['label'], $zone .'_'. $area['areaid'], $uniqid, $field, $zone ); ?>
											</div>

										<?php endif; ?>
									<?php endforeach; ?>
								<?php endif; ?>

								<span class="drop-message">Drop fields here</span>
							</div>
							<div class="gv-droppable-area-action">
								<a href="#" class="gv-add-field button-secondary" title="" data-objecttype="<?php echo esc_attr( $type ); ?>" data-areaid="<?php echo esc_attr( $zone .'_'. $area['areaid'] ); ?>"><?php echo '+ '.esc_html( $button_label ); ?></a>
								<p class="gv-droppable-area-title"><?php echo esc_html( $area['title'] ); ?></p>
								<p class="gv-droppable-area-subtitle"><?php echo esc_html( $area['subtitle'] ); ?></p>
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

		$default_widget_areas = array(
			array( '1-1' => array( array( 'areaid' => 'top', 'title' => __('Full Width Top', 'gravity-view' ) , 'subtitle' => '' ) ) ),
			array( '1-2' => array( array( 'areaid' => 'left', 'title' => __('Left', 'gravity-view') , 'subtitle' => '' ) ), '2-2' => array( array( 'areaid' => 'right', 'title' => __('Right', 'gravity-view') , 'subtitle' => '' ) ) ),
			array( '1-1' => array( 	array( 'areaid' => 'bottom', 'title' => __('Full Width Bottom', 'gravity-view') , 'subtitle' => '' ) ) )
		);

		$widgets = array();
		if( !empty( $post_id ) ) {
			$widgets = get_post_meta( $post_id, '_gravityview_directory_widgets', true );

		}

		ob_start();
		?>

		<div class="gv-grid gv-grid-pad gv-grid-border">
			<?php echo $this->render_active_areas( $template_id, 'widget', $zone, $default_widget_areas, $widgets ); ?>
		</div>

		<?php
		$output = ob_get_contents();
		ob_end_clean();
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
	function render_directory_active_areas( $template_id = '', $context = 'single', $post_id = '' ) {

		if( empty( $template_id ) ) {
			return;
		}

		$output = '';

		$template_areas = apply_filters( 'gravityview_template_active_areas', array(), $template_id );

		$fields = '';
		if( !empty( $post_id ) ) {
			$fields = get_post_meta( $post_id, '_gravityview_directory_fields', true );
		}

		ob_start();
		?>

		<?php $this->render_active_areas( $template_id, 'field', $context, $template_areas, $fields ); ?>

		<?php

		/*
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
		*/

		$output = ob_get_contents();
		ob_end_clean();
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
	function render_field_options( $field_type, $template_id, $field_id, $field_label, $area, $uniqid = '', $current = '', $context = 'single' ) {

		if( empty( $uniqid ) ) {
			//generate a unique field id
			$uniqid = uniqid('', false);
		}

		// get field/widget options
		$options = $this->get_default_field_options( $field_type, $template_id, $field_id );

		// two different post arrays, depending of the field type
		$name_prefix = $field_type .'s' .'['. $area .']['. $uniqid .']';

		// build output
		$output = '';
		$output .= '<input type="hidden" class="field-key" name="'. $name_prefix .'[id]" value="'. $field_id .'">';
		$output .= '<input type="hidden" class="field-label" name="'. $name_prefix .'[label]" value="'. $field_label .'">';
		$output .= '<div class="gv-dialog-options" title="'. esc_attr__( 'Options', 'gravity-view' ) . ': '. $field_label .'">';
		$output .= '<ul>';

		foreach( $options as $key => $details ) {

			$default = isset( $details['default'] ) ? $details['default'] : '';
			//$default = '';
			$curr_value = isset( $current[ $key ] ) ? $current[ $key ] : $default;
			$label = isset( $details['label'] ) ? $details['label'] : '';
			$type = isset( $details['type'] ) ? $details['type'] : 'input_text';

			switch( $type ) {
				case 'checkbox':
					$output .= '<li>'. $this->render_checkbox_option( $name_prefix . '['. $key .']' , $label, $curr_value ) .'</li>';
					break;

				case 'input_text':
				default:
					$output .= '<li>'. $this->render_input_text_option( $name_prefix . '['. $key .']' , $label, $curr_value ) .'</li>';
					break;

			}

		}

		//TODO: Move this to other place..
		if( 'field' === $field_type ) {
			$only_loggedin = !empty( $current['only_loggedin'] ) ? 1 : '';
			$only_loggedin_cap = !empty( $current['only_loggedin_cap'] ) ? $current['only_loggedin_cap'] : 'read';

			// default logged-in visibility
			$select_cap_choices = array(
				array( 'label' => __( 'Any', 'gravity-view' ), 'value' => 'read' ),
				array( 'label' => __( 'Author or higher', 'gravity-view' ), 'value' => 'publish_posts' ),
				array( 'label' => __( 'Editor or higher', 'gravity-view' ), 'value' => 'delete_others_posts' ),
				array( 'label' => __( 'Administrator', 'gravity-view' ), 'value' => 'manage_options' ),
			);
			$output .= '<li>' . $this->render_checkbox_option( $name_prefix . '[only_loggedin]' , __( 'Only visible to logged in users with role:', 'gravity-view' ) ) ;
			$output .=  $this->render_selectbox_option( $name_prefix . '[only_loggedin_cap]', '', $select_cap_choices, $only_loggedin_cap ) . '</li>';
		}

		// close options window
		$output .= '</ul>';
		$output .= '</div>';

		return $output;

	}


	public function get_default_field_options( $field_type, $template_id, $field_id ) {

		$field_options = array();

		if( 'field' === $field_type ) {
			// Default options - fields
			$field_options = array(
				'show_label' => array( 'type' => 'checkbox', 'label' => __( 'Show Label', 'gravity-view' ), 'default' => true ),
				'custom_label' => array( 'type' => 'input_text', 'label' => __( 'Custom Label:', 'gravity-view' ), 'default' => '' ),
				'custom_class' => array( 'type' => 'input_text', 'label' => __( 'Custom CSS Class:', 'gravity-view' ), 'default' => '' ),
			);
		} elseif( 'widget' === $field_type ) {

		}

		// hook to inject template specific field/widget options
		return apply_filters( "gravityview_template_{$field_type}_options", $field_options, $template_id, $field_id );

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
	function get_available_fields_html() {
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

		// $response = $this->render_directory_active_areas( $_POST['template_id'] );
		// echo $response;
		$response['directory'] = $this->render_directory_active_areas( $_POST['template_id'], 'directory' );
		$response['single'] = $this->render_directory_active_areas( $_POST['template_id'], 'single' );

		echo json_encode( $response );
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

		if( empty( $_POST['template'] ) || empty( $_POST['area'] ) || empty( $_POST['field_id'] ) || empty( $_POST['field_type'] ) || empty( $_POST['field_label'] ) ) {
			echo $response;
			die();
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxviews' ) ) {
			echo $response;
			die();
		}

		$response = $this->render_field_options( $_POST['field_type'], $_POST['template'], $_POST['field_id'], $_POST['field_label'], $_POST['area'] );
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
		wp_enqueue_script( 'jquery-ui-tooltip' );


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
			$allow_scripts = array( 'jquery-ui-dialog', 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'gravityview_views_scripts', 'jquery-ui-tooltip' );
			$registered = array_merge( $registered, $allow_scripts );
		} elseif( 'gravityview_noconflict_styles' === $filter ) {
			$allow_styles = array( 'dashicons', 'wp-jquery-ui-dialog', 'gravityview_views_styles' );
			$registered = array_merge( $registered, $allow_styles );
		}

		return $registered;
	}


}

new GravityView_Admin_Views;