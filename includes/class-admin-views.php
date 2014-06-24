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

	private $admin_notices = array();

	function __construct() {

		// check if gravityforms is active
		add_action( 'admin_init', array( $this, 'check_gravityforms' ) );

		//throw notice messages if needed
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );

		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ));
		add_action( 'save_post', array( $this, 'save_postdata' ) );

		// Fix annoying 3rd party metabox behavior
		// Remove metaboxes. We need to run this twice for Genesis (9) and others (11). Default is 10.
		add_action( 'admin_menu' , array( $this, 'remove_other_metaboxes' ), 9 );
		add_action( 'admin_menu' , array( $this, 'remove_other_metaboxes' ), 11 );

		// Add them back in
		add_action( 'add_meta_boxes', array( $this, 'add_other_metaboxes' ), 20 );

		// adding styles and scripts
		add_action( 'admin_enqueue_scripts', array( 'GravityView_Admin_Views', 'add_scripts_and_styles'), 999 );
		add_filter( 'gform_noconflict_styles', array( $this, 'register_no_conflict') );
		add_filter( 'gform_noconflict_scripts', array( $this, 'register_no_conflict') );
		add_filter( 'gravityview_noconflict_styles', array( $this, 'register_no_conflict') );
		add_filter( 'gravityview_noconflict_scripts', array( $this, 'register_no_conflict') );

		// AJAX
		//get field options
		add_action( 'wp_ajax_gv_field_options', array( $this, 'get_field_options' ) );

		// get available fields
		add_action( 'wp_ajax_gv_available_fields', array( $this, 'get_available_fields_html' ) );

		// get active areas
		add_action( 'wp_ajax_gv_get_active_areas', array( $this, 'get_active_areas' ) );

		// get preset fields
		add_action( 'wp_ajax_gv_get_preset_fields', array( $this, 'get_preset_fields_config' ) );

		// get preset fields
		add_action( 'wp_ajax_gv_set_preset_form', array( $this, 'create_preset_form' ) );

		add_action( 'wp_ajax_gv_sortable_fields_form', array( $this, 'get_sortable_fields' ) );

		// Add Connected Form column
		add_filter('manage_gravityview_posts_columns' , array( $this, 'add_post_type_columns' ) );

		add_action( 'manage_gravityview_posts_custom_column', array( $this, 'add_post_type_column_content'), 10, 2 );

	}

	public function add_post_type_column_content( $column_name, $post_id )	{

		$form_id = get_post_meta( $post_id, '_gravityview_form_id', true );

		// All Views should have a connected form. If it doesn't, that's not right.
		if( empty($form_id) ) {
			GravityView_Plugin::log_error( sprintf( '[add_post_type_column_content] View ID %s does not have a connected GF form.', $post_id ) );
			echo __( 'Not connected.', 'gravity-view' );
			return;
		}

		$form = gravityview_get_form( $form_id );

		if( !$form ) {
			GravityView_Plugin::log_error( sprintf( '[add_post_type_column_content] Connected form not found: Form #%d', $form_id ) );

			echo __( 'The connected form can not be found; it may no longer exist.', 'gravity-view' );
		}

		$url = admin_url( sprintf( 'admin.php?page=gf_edit_forms&amp;id=%d', $form_id ) );

		echo sprintf( '<a href="%s">%s</a>', $url , $form['title'] );

	}

	public function add_post_type_columns( $columns ) {

		// Get the date column and save it for later to add back in.
		// This adds it after the Data Source column.
		// This way, we don't need to do array_slice, array_merge, etc.
		$date = $columns['date'];
		unset( $columns['date'] );

		$columns['gv_connected_form'] = __('Data Source', 'gravity-view');

		// Add the date back in.
		$columns['date'] = $date;

		return $columns;
	}

	/**
	 * Check if Gravity Forms plugin is active and show notice if not.
	 *
	 * @access public
	 * @return void
	 */
	public function check_gravityforms() {

		$gf_status = GravityView_Plugin::get_plugin_status( 'gravityforms/gravityforms.php' );

		if( $gf_status !== true ) {
			$image = '<img src="'.plugins_url( 'images/astronaut-200x263.png', GRAVITYVIEW_FILE ).'" class="alignleft" height="87" width="66" alt="The GravityView Astronaut Says:" style="margin:0 10px 10px 0;" />';
			if( $gf_status === 'inactive' ) {
				$this->admin_notices[] = array( 'class' => 'error', 'message' => sprintf( __( '%sGravityView requires Gravity Forms to be active. %sActivate Gravity Forms%s to use the GravityView plugin.', 'gravity-view' ), '<h3>'.$image, "</h3>\n\n".'<strong><a href="'. wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=gravityforms/gravityforms.php' ), 'activate-plugin_gravityforms/gravityforms.php') . '" class="button button-large">', '</a></strong>' ) );
			} else {
				$this->admin_notices[] = array( 'class' => 'error', 'message' => sprintf( __( '%sGravityView requires Gravity Forms to be installed in order to run properly. %sGet Gravity Forms%s - starting at $39%s%s', 'gravity-view' ), '<h3>'.$image, "</h3>\n\n".'<a href="http://katz.si/gravityforms" class="button button-secondary button-large button-hero">' , '<em>', '</em>', '</a>') );
			}
			return false;
		}

		return true;
	}

	/**
	 * Outputs the admin notices generated by the plugin
	 *
	 * @return void
	 */
	function admin_notice() {

		if( empty( $this->admin_notices ) ) {
			return;
		}

		foreach( $this->admin_notices as $notice ) {

			echo '<div class="'. sanitize_html_class( $notice['class'] ).'">';
			echo wpautop($notice['message']);
			echo '<div class="clear"></div>';
			echo '</div>';

		}
		//reset the notices handler
		$this->admin_notices = array();
	}

	/**
	 * Modify WooThemes metabox behavior
	 *
	 * Only show when the View has been configured.
	 *
	 * @return void
	 */
	function remove_other_metaboxes() {
		global $pagenow;

		$gv_page = $this->is_gravityview_admin_page();

		// New View or Edit View page
		if($gv_page === 'single') {

			// Genesis - adds the metaboxes too high. Added back in below.
			remove_action( 'admin_menu', 'genesis_add_inpost_layout_box' );

			// New View page
			if($pagenow === 'post-new.php' ) {

				// WooThemes
				remove_meta_box( 'woothemes-settings', 'gravityview', 'normal' );

				// WordPress SEO Plugin
				add_filter( 'option_wpseo_titles', array( $this, 'hide_wordpress_seo_metabox' ) );
			}

		}

	}

	function add_other_metaboxes() {
		global $pagenow;

		if(!$this->is_gravityview_admin_page()) { return; }

		// Genesis
		if(function_exists('genesis_inpost_layout_box') && $pagenow !== 'post-new.php') {
			// Add back in Genesis meta box
			add_meta_box( 'genesis_inpost_layout_box', __( 'Layout Settings', 'genesis' ), 'genesis_inpost_layout_box', 'gravityview', 'advanced', 'default' );
		}
	}

	/**
	 * Modify the WordPress SEO plugin's metabox behavior
	 *
	 * Only show when the View has been configured.
	 * @param  array       $options WP SEO options array
	 * @return array               Modified
	 */
	function hide_wordpress_seo_metabox( $options = array() ) {
		global $pagenow;

		$options['hideeditbox-gravityview'] = true;

		return $options;
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
		$current_form = get_post_meta( $post->ID, '_gravityview_form_id', true );

		// input ?>
		<label for="gravityview_form_id" ><?php esc_html_e( 'Where would you like the data to come from for this View?', 'gravity-view' ); ?></label>

		<?php
		// check for available gravity forms
		$forms = gravityview_get_forms();
		?>

		<p>
			<?php if ( empty( $current_form ) ) : ?>
				<?php // render "start fresh" button ?>
				<a class="button-primary" href="#gv_start_fresh" title="<?php esc_attr_e( 'Start Fresh', 'gravity-view' ); ?>"><?php esc_html_e( 'Start Fresh', 'gravity-view' ); ?></a>

				<span>&nbsp;<?php esc_html_e( 'or use an existing form', 'gravity-view' ); ?>&nbsp;</span>

			<?php endif; ?>

			<?php // render select box ?>
			<select name="gravityview_form_id" id="gravityview_form_id">
				<option value="" <?php selected( '', $current_form, true ); ?>>&mdash; <?php esc_html_e( 'list of forms', 'gravity-view' ); ?> &mdash;</option>
				<?php foreach( $forms as $form ) : ?>
					<option value="<?php echo $form['id']; ?>" <?php selected( $form['id'], $current_form, true ); ?>><?php echo $form['title']; ?></option>
				<?php endforeach; ?>
			</select>

			<?php // render change layout button
			if( !empty( $current_form ) ): ?>
				&nbsp;<a class="button-primary" href="#gv_switch_view" title="<?php esc_attr_e( 'Switch View', 'gravity-view' ); ?>"><?php esc_html_e( 'Switch View', 'gravity-view' ); ?></a>
			<?php endif; ?>

		</p>

		<?php // confirm dialog box ?>
		<div id="gravityview_form_id_dialog" class="gv-dialog-options" title="<?php esc_attr_e( 'Attention', 'gravity-view' ); ?>">
			<p><?php esc_html_e( 'Changing the form will discard all the existent View configuration.', 'gravity-view' ); ?></p>
		</div>

		<?php // confirm template dialog box ?>
		<div id="gravityview_switch_template_dialog" class="gv-dialog-options" title="<?php esc_attr_e( 'Attention', 'gravity-view' ); ?>">
			<p><?php esc_html_e( 'Changing the View Type will discard all the existent View configuration.', 'gravity-view' ); ?></p>
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
	 * @todo  Re-enable the Preview link
	 * @group Beta
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
									<!-- // TODO: Take screenshots. <p><a href="#gv_preview_template" class="button-secondary"><?php esc_html_e( 'Preview', 'gravity-view'); ?></a></p> -->
								<?php endif; ?>
							</div>
						</div>
						<div class="gv-template-preview" title="<?php esc_html_e( 'Preview', 'gravity-view'); ?>: <?php echo esc_attr( $template['label'] ); ?>"><img src="<?php echo esc_url( $template['preview'] ); ?>" ></div>
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

		$form = gravityview_get_form( $curr_form );

		if(isset($form['id']))
		    echo "<script type=\"text/javascript\">var form = " . GFCommon::json_encode($form) . ";</script>";
		else
		    echo "<script type=\"text/javascript\">var form = new Form();</script>";


		$get_id_backup = $_GET['id'];
		$_GET['id'] = $curr_form;
		echo '<script type="text/javascript">' . GFCommon::gf_vars(false) . '</script>';
		$_GET['id'] = $get_id_backup;

?>
		<div id="tabs">

			<ul class="nav-tab-wrapper">
				<li><a href="#directory-view" class="nav-tab"><i class="dashicons dashicons-admin-page"></i> <?php esc_html_e( 'Multiple Entries', 'gravity-view' ); ?></a></li>
				<li><a href="#single-view" class="nav-tab"><i class="dashicons dashicons-media-default"></i> <?php esc_html_e( 'Single Entry', 'gravity-view' ); ?></a></li>
			</ul>

			<div id="directory-view">

				<div id="directory-fields" class="gv-section">

					<h4><?php esc_html_e( 'Above Entries Widgets', 'gravity-view'); ?> <span><?php esc_html_e( 'These widgets will be shown above entries.', 'gravity-view'); ?></span></h4>

					<?php echo $this->render_widgets_active_areas( $curr_template, 'header', $post->ID ); ?>

					<h4><?php esc_html_e( 'Entries Fields', 'gravity-view'); ?> <span><?php esc_html_e( 'These fields will be shown for each entry.', 'gravity-view'); ?></span></h4>

					<div id="directory-active-fields" class="gv-grid gv-grid-pad gv-grid-border">
						<?php if(!empty( $curr_template ) ) {
							echo $this->render_directory_active_areas( $curr_template, 'directory', $post->ID );
						} ?>
					</div>

					<h4><?php esc_html_e( 'Below Entries Widgets', 'gravity-view'); ?> <span><?php esc_html_e( 'These widgets will be shown below entries.', 'gravity-view'); ?></span></h4>

					<?php echo $this->render_widgets_active_areas( $curr_template, 'footer', $post->ID ); ?>


					<?php // list of available fields to be shown in the popup ?>
					<div id="directory-available-fields" class="hide-if-js gv-tooltip">
						<span class="close"><i class="dashicons dashicons-dismiss"></i></span>
						<?php $this->render_available_fields( $curr_form, 'directory' ); ?>
					</div>

					<?php // list of available widgets to be shown in the popup ?>
					<div id="directory-available-widgets" class="hide-if-js gv-tooltip">
						<span class="close"><i class="dashicons dashicons-dismiss"></i></span>
						<?php $this->render_available_widgets(); ?>
					</div>

				</div>


			</div><?php //end directory tab ?>



			<?php // Single View Tab ?>

			<div id="single-view">

				<div id="single-fields" class="gv-section">

					<h4><?php esc_html_e( 'These fields will be shown in Single Entry view.', 'gravity-view'); ?></h4>

					<div id="single-active-fields" class="gv-grid gv-grid-pad gv-grid-border">
						<?php if(!empty( $curr_template ) ) {
							echo $this->render_directory_active_areas( $curr_template, 'single', $post->ID );
						} ?>
					</div>

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

		$curr_form = get_post_meta( $post->ID, '_gravityview_form_id', true );

		// View template settings
		$settings = get_post_meta( $post->ID, '_gravityview_template_settings', true );

		$defaults = array(
			'lightbox' => true,
			'page_size' => 25,
			'show_only_approved' => false,
			'sort_field' => '',
			'sort_direction' => 'ASC',
			'start_date' => '',
			'end_date' => '',
			'single_title' => '',
			'back_link_label' => '',
		);

		$ts = wp_parse_args( $settings, $defaults );

		?>

		<table class="form-table">

			<tr valign="top">
				<td scope="row">
					<label for="gravityview_page_size"><?php esc_html_e( 'Number of entries to show per page', 'gravity-view'); ?></label>
				</td>
				<td>
					<input name="template_settings[page_size]" id="gravityview_page_size" type="number" step="1" min="1" value="<?php empty( $ts['page_size'] ) ? print 25 : print $ts['page_size']; ?>" class="small-text">
				</td>
			</tr>
			<tr valign="top">
				<td colspan="2">
					<label class="checkbox">
						<input name="template_settings[lightbox]" type="hidden" value="0" />
						<input name="template_settings[lightbox]" id="gravityview_lightbox" type="checkbox" value="1"<?php checked( !empty($ts['lightbox']) , true, true ); ?> class="checkbox">
						<span><?php esc_html_e( 'Enable lightbox for images', 'gravity-view'); ?></span>
					</label>
				</td>
			</tr>
			<tr valign="top">
				<td scope="row" colspan="2">
					<label class="checkbox">
						<input name="template_settings[show_only_approved]" type="checkbox" id="gravityview_only_approved" value="1" <?php empty( $ts['show_only_approved'] ) ? print '' : checked( $ts['show_only_approved'] , 1, true ); ?>>
						<?php esc_html_e( 'Show only approved entries', 'gravity-view' ); ?>
					</label>
				</td>
			</tr>

			<tr valign="top">
				<td scope="row">
					<label for="gravityview_sort_field"><?php esc_html_e( 'Sort by field', 'gravity-view'); ?></label>
				</td>
				<td>
					<select name="template_settings[sort_field]" id="gravityview_sort_field" class="widefat">
						<?php echo gravityview_get_sortable_fields( $curr_form, $ts['sort_field'] ); ?>
					</select>
				</td>
			</tr>

			<tr valign="top">
				<td scope="row">
					<label for="gravityview_sort_direction"><?php esc_html_e( 'Sort direction', 'gravity-view'); ?></label>
				</td>
				<td>
					<select name="template_settings[sort_direction]" id="gravityview_sort_direction">
						<option value="ASC" <?php selected( 'ASC', $ts['sort_direction'], true ); ?>><?php esc_html_e( 'ASC', 'gravity-view'); ?></option>
						<option value="DESC" <?php selected( 'DESC', $ts['sort_direction'], true ); ?>><?php esc_html_e( 'DESC', 'gravity-view'); ?></option>
					</select>
				</td>
			</tr>

			<tr valign="top">
				<td scope="row">
					<label for="gravityview_start_date"><?php esc_html_e( 'Filter by Start Date', 'gravity-view'); ?></label>
				</td>
				<td>
					<input name="template_settings[start_date]" id="gravityview_start_date" type="text" class="widefat gv-datepicker"value="<?php echo $ts['start_date']; ?>">
				</td>
			</tr>

			<tr valign="top">
				<td scope="row">
					<label for="gravityview_end_date"><?php esc_html_e( 'Filter by End Date', 'gravity-view'); ?></label>
				</td>
				<td>
					<input name="template_settings[end_date]" id="gravityview_end_date" type="text" class="widefat gv-datepicker" value="<?php echo $ts['end_date']; ?>">
				</td>
			</tr>

			<?php // Hook for other template custom settings

			do_action( 'gravityview_admin_directory_settings', $ts );

			?>

		</table>

		<h3 style="margin-top:1em;"><?php esc_html_e( 'Single Entry Settings', 'gravity-view'); ?>:</h3>

		<table class="form-table">
			<tr valign="top">
				<td scope="row">
					<label for="gravityview_se_title"><?php esc_html_e( 'Single Entry Title', 'gravity-view'); ?></label>
				</td>
				<td>
					<input name="template_settings[single_title]" id="gravityview_se_title" type="text" class="widefat" value="<?php echo $ts['single_title']; ?>">
				</td>
			</tr>
			<tr valign="top">
				<td scope="row">
					<label for="gravityview_se_back_label"><?php esc_html_e( 'Back Link Label', 'gravity-view'); ?></label>
				</td>
				<td>
					<input name="template_settings[back_link_label]" id="gravityview_se_back_label" type="text" class="widefat" value="<?php echo $ts['back_link_label']; ?>">
				</td>
			</tr>
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
		printf('<p>%s <code>[gravityview id="%d"]</code></p>', esc_html__( 'To insert this view into a post or a page use the following shortcode:', 'gravity-view' ), $post->ID );
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

		GravityView_Plugin::log_debug( '[save_postdata] Saving View post type. Data: ' . print_r( $_POST, true ) );

		// check if this is a start fresh View
		if ( isset( $_POST['gravityview_select_form_nonce'] ) && wp_verify_nonce( $_POST['gravityview_select_form_nonce'], 'gravityview_select_form' ) ) {

			$form_id = !empty( $_POST['gravityview_form_id'] ) ? $_POST['gravityview_form_id'] : '';
			// save form id
			update_post_meta( $post_id, '_gravityview_form_id', $form_id );

		}

		// Was this a start fresh?
		if ( ! empty( $_POST['gravityview_form_id_start_fresh'] ) ) {
			add_post_meta( $post_id, '_gravityview_start_fresh', 1 );
		} else {
			delete_post_meta( $post_id, '_gravityview_start_fresh' );
		}

		// Check if we have a template id
		if ( isset( $_POST['gravityview_select_template_nonce'] ) && wp_verify_nonce( $_POST['gravityview_select_template_nonce'], 'gravityview_select_template' ) ) {

			$template_id = !empty( $_POST['gravityview_directory_template'] ) ? $_POST['gravityview_directory_template'] : '';

			// now save template id
			update_post_meta( $post_id, '_gravityview_directory_template', $template_id );
		}


		// save View Configuration metabox
		if ( isset( $_POST['gravityview_view_configuration_nonce'] ) && wp_verify_nonce( $_POST['gravityview_view_configuration_nonce'], 'gravityview_view_configuration' ) ) {

			// template settings
			if( empty( $_POST['template_settings'] ) ) {
				$_POST['template_settings'] = array();
			}
			update_post_meta( $post_id, '_gravityview_template_settings', $_POST['template_settings'] );

			// Directory&single Visible Fields
			if( !empty( $preset_fields ) ) {
				$fields = $preset_fields;
			} elseif( empty( $_POST['fields'] ) ) {
				$fields = array();
			} else {
				$fields = $_POST['fields'];
			}
			update_post_meta( $post_id, '_gravityview_directory_fields', $fields );

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
	function import_form( $xml_path = '' ) {

		GravityView_Plugin::log_debug( '[import_form] Import Preset Form. File: ' . print_r( $xml_path, true ) );

		if( empty( $xml_path ) || !class_exists('GFExport') || !file_exists( $xml_path ) ) {
			GravityView_Plugin::log_error( '[import_form] Class GFExport or file not found. file: ' . print_r( $xml_path, true ) );
			return false;
		}

		// import form
		$forms = '';
		$count = GFExport::import_file( $xml_path, $forms );

		GravityView_Plugin::log_debug( '[import_form] Importing form. Result: ' . print_r( $count, true ) . '. Form: ' . print_r( $forms, true ) );

		if( $count != 1 || empty( $forms[0]['id'] ) ) {
			GravityView_Plugin::log_error( '[import_form] Form Import Failed!' );
			return false;
		}

		// import success - return form id
		return $forms[0]['id'];
	}

	/**
	 * Import fields configuration from an exported WordPress View preset
	 * @param  string $file path to file
	 * @return array       Fields config array (unserialized)
	 */
	function import_fields( $file ) {

		if( empty( $file ) || !file_exists(  $file ) ) {
			GravityView_Plugin::log_error( '[import_fields] Importing Preset Fields. File not found. file: ' . print_r( $file, true ) );
			return false;
		}

		if( !class_exists('WXR_Parser') ) {
			include_once GRAVITYVIEW_DIR . 'includes/lib/xml-parsers/parsers.php';
		}

		$parser = new WXR_Parser();
		$presets = $parser->parse( $file );

		if(is_wp_error( $presets )) {
			GravityView_Plugin::log_error( '[import_fields] Importing Preset Fields failed. Threw WP_Error: ' . $presets->get_error_message() );
			return false;
		}

		if( empty( $presets['posts'][0]['postmeta'] ) && !is_array( $presets['posts'][0]['postmeta'] ) ) {
			GravityView_Plugin::log_error( '[import_fields] Importing Preset Fields failed. Meta not found in file: ' . print_r( $file, true ) );
			return false;
		}

		GravityView_Plugin::log_debug(print_r($presets['posts'][0]['postmeta'], true));

		$fields = $widgets = array();
		foreach( $presets['posts'][0]['postmeta'] as $meta ) {
			switch ($meta['key']) {
				case '_gravityview_directory_fields':
					$fields = maybe_unserialize( $meta['value'] );
					break;
				case '_gravityview_directory_widgets':
					$widgets = maybe_unserialize( $meta['value'] );
					break;
			}
		}

		GravityView_Plugin::log_debug( '[import_fields] Imported Preset Fields: ' . print_r( $fields, true ) );
		GravityView_Plugin::log_debug( '[import_fields] Imported Preset Widgets: ' . print_r( $widgets, true ) );

		return array(
			'fields' => $fields,
			'widgets' => $widgets
		);
	}

	/**
	 * Get the the form fields for a preset (no form created yet)
	 * @param  string $template_id Preset template
	 *
	 */
	function pre_get_form_fields( $template_id = '') {

		if( empty( $template_id ) ) {
			return false;
		} else {
			$form_file = apply_filters( 'gravityview_template_formxml', '', $template_id );
			if( !file_exists( $form_file )  ) {
				GravityView_Plugin::log_error( '[pre_get_available_fields] Importing Form Fields for preset ['. $template_id .']. File not found. file: ' . $form_file );
				return false;
			}
		}

		// Load xml parser (from GravityForms)
		$xml_parser = trailingslashit( WP_PLUGIN_DIR ) . 'gravityforms/xml.php';
		if( file_exists( $xml_parser ) ) {
			require_once( $xml_parser );
		}

		// load file
		$xmlstr = file_get_contents( $form_file );

        $options = array(
            "page" => array("unserialize_as_array" => true),
            "form"=> array("unserialize_as_array" => true),
            "field"=> array("unserialize_as_array" => true),
            "rule"=> array("unserialize_as_array" => true),
            "choice"=> array("unserialize_as_array" => true),
            "input"=> array("unserialize_as_array" => true),
            "routing_item"=> array("unserialize_as_array" => true),
            "creditCard"=> array("unserialize_as_array" => true),
            "routin"=> array("unserialize_as_array" => true),
            "confirmation" => array("unserialize_as_array" => true),
            "notification" => array("unserialize_as_array" => true)
            );

		$xml = new RGXML($options);
        $forms = $xml->unserialize($xmlstr);

        if( !$forms ) {
        	GravityView_Plugin::log_error( '[pre_get_available_fields] Importing Form Fields for preset ['. $template_id .']. Error importing file: ' . $form_file );
        	return false;
        }

        if( !empty( $forms[0] ) && is_array( $forms[0] ) ) {
        	$form = $forms[0];
        }

        GravityView_Plugin::log_debug( '[pre_get_available_fields] Importing Form Fields for preset ['. $template_id .']. Form: ' . print_r( $form, true ) );

        return $form;

	}


	/**
	 * Generate the HTML for a field or widget label
	 *
	 * @param  string      $label_text The text of the label
	 * @param  string      $field_id   The ID of the field
	 * @param  string      $label_type   Is this a label for a `field` or `widget`?
	 * @param  boolean     $add_controls   Add the field/widget controls?
	 * @param  string      $field_options   Add field options DIV
	 * @return string                  HTML of output
	 */
	function render_label( $label_text, $field_id, $label_type = 'field', $input_type = NULL, $field_options = '' ) {

		$settings_title = sprintf(__('Configure %s Settings', 'gravity-view'), ucfirst($label_type));
		$delete_title = sprintf(__('Remove %s', 'gravity-view'), ucfirst($label_type));
		$hide_settings_link = empty($field_options) ? 'hide-if-js' : '';
		$settings_link = sprintf( '<a href="#settings" class="dashicons-admin-generic dashicons %s" title="%s"></a>', $hide_settings_link, $settings_title );

		$output = '<h5 class="field-id-'.esc_attr($field_id).'">';

		$output .= esc_attr( $label_text );

		$output .= sprintf('<span class="gv-field-controls">%s<a href="#remove" class="dashicons-dismiss dashicons" title="%s"></a></span>', $settings_link, $delete_title);

		$output .= '</h5>';

		$output = '<div data-fieldid="'.esc_attr($field_id).'" data-inputtype="'.esc_attr( $input_type ).'" class="gv-fields">'.$output.$field_options.'</div>';

		return $output;
	}

	/**
	 * Render html for displaying available fields based on a Form ID
	 * $blacklist_field_types - contains the field types which are not proper to be shown in a directory.
	 *
	 * @filter  gravityview_blacklist_field_types Modify the types of fields that shouldn't be shown in a View.
	 * @access public
	 * @param string $form_id (default: '')
	 * @param string $context (default: 'single')
	 * @return void
	 */
	function render_available_fields( $form = '', $context = 'single' ) {

		$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array() );

		$fields = $this->get_available_fields( $form );

		$all_fields = $this->render_label( __( '+ Add All Fields', 'gravity-view' ) , 'all-fields', 'field' );

		if( !empty( $fields ) ) {

			foreach( $fields as $id => $details ) {

				if( in_array( $details['type'], $blacklist_field_types ) ) {
					continue;
				}

				echo $this->render_label($details['label'], $id, 'field', $details['type'] );

			} // End foreach

			// Add All Fields link
			echo $all_fields;
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
			'id' => array( 'label' => __('Entry ID', 'gravity-view'), 'type' => 'id'),
			'date_created' => array( 'label' => __('Entry Date', 'gravity-view'), 'type' => 'date_created'),
			'source_url' => array( 'label' => __('Source URL', 'gravity-view'), 'type' => 'source_url'),
			'ip' => array( 'label' => __('User IP', 'gravity-view'), 'type' => 'ip'),
			'created_by' => array( 'label' => __('User', 'gravity-view'), 'type' => 'created_by'),
        );

        if('single' !== $zone) {
        	$entry_default_fields['entry_link'] = array('label' => __('Link to Entry', 'gravity-view'), 'type' => 'entry_link');
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
		$default_fields = $this->get_entry_default_fields($form, $zone);

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

				echo $this->render_label($details['label'], $id, 'widget');

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

		if( $type === 'widget' ) {
			$button_label = __( 'Add Widget', 'gravity-view' );
		} elseif( $type === 'field' ) {
			$button_label = __( 'Add Field', 'gravity-view' );
		}

		// if saved values, get available fields to label everyone
		if( !empty( $values ) && 'field' === $type && !empty( $this->post_id ) ) {
			$form_id = get_post_meta( $this->post_id, '_gravityview_form_id', true );
			$available_fields = $this->get_available_fields( $form_id, $zone );
		}

		foreach( $rows as $row ) :
			foreach( $row as $col => $areas ) :
				$column = ($col == '2-2') ? '1-2' : $col; ?>

				<div class="gv-grid-col-<?php echo esc_attr( $column ); ?>">

					<?php foreach( $areas as $area ) : ?>

						<div class="gv-droppable-area">
							<div class="active-drop active-drop-<?php echo $type; ?>" data-areaid="<?php echo esc_attr( $zone .'_'. $area['areaid'] ); ?>">

								<?php // render saved fields

								if( !empty( $values[ $zone .'_'. $area['areaid'] ] ) ) {

									foreach( $values[ $zone .'_'. $area['areaid'] ] as $uniqid => $field ) {

										$input_type = isset($available_fields[ $field['id'] ]['type']) ? $available_fields[ $field['id'] ]['type'] : NULL;

										//if( !empty( $available_fields[ $field['id'] ] ) ) :

										$field_options = $this->render_field_options( $type, $template_id, $field['id'], $field['label'], $zone .'_'. $area['areaid'], $input_type, $uniqid, $field, $zone );

										echo $this->render_label($field['label'], $field['id'], $type, $input_type, $field_options);
										//endif;

									}

								} // End if zone is not empty ?>

								<span class="drop-message"><?php echo sprintf(esc_attr__('"%s" or drag existing %ss here.', 'gravity-view'), $button_label, $type ); ?></span>
							</div>
							<div class="gv-droppable-area-action">
								<a href="#" class="gv-add-field button-secondary" title="" data-objecttype="<?php echo esc_attr( $type ); ?>" data-areaid="<?php echo esc_attr( $zone .'_'. $area['areaid'] ); ?>" data-context="<?php echo esc_attr( $zone ); ?>"><?php echo '+ '.esc_html( $button_label ); ?></a>
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
		$this->render_active_areas( $template_id, 'field', $context, $template_areas, $fields );
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
	function render_field_options( $field_type, $template_id, $field_id, $field_label, $area, $input_type = NULL, $uniqid = '', $current = '', $context = 'single' ) {

		if( empty( $uniqid ) ) {
			//generate a unique field id
			$uniqid = uniqid('', false);
		}

		// get field/widget options
		$options = $this->get_default_field_options( $field_type, $template_id, $field_id, $context, $input_type );

		// two different post arrays, depending of the field type
		$name_prefix = $field_type .'s' .'['. $area .']['. $uniqid .']';

		// build output
		$output = '';
		$output .= '<input type="hidden" class="field-key" name="'. $name_prefix .'[id]" value="'. esc_attr( $field_id ) .'">';
		$output .= '<input type="hidden" class="field-label" name="'. $name_prefix .'[label]" value="'. esc_attr( $field_label ) .'">';

		// If there are no options, return what we got.
		if(empty($options)) { return $output; }

		$output .= '<div class="gv-dialog-options" title="'. esc_attr( sprintf( __( 'Options: %s', 'gravity-view' ), $field_label ) ) .'">';
		$output .= '<ul>';

		foreach( $options as $key => $details ) {
			$value = isset( $current[ $key ] ) ? $current[ $key ] : NULL;
			$output .= '<li>'. $this->render_field_option( $name_prefix . '['. $key .']' , $details, $value) .'</li>';
		}

		// close options window
		$output .= '</ul>';
		$output .= '</div>';

		return $output;

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
	public function get_default_field_options( $field_type, $template_id, $field_id, $context, $input_type ) {

		$field_options = array();

		if( 'field' === $field_type ) {

			/**
			 * Modify the capabilities shown in the field dropdown
			 * @link  https://github.com/zackkatz/GravityView/wiki/How-to-modify-capabilities-shown-in-the-field-%22Only-visible-to...%22-dropdown
			 * @since  1.0.1
			 */
			$select_cap_choices = apply_filters('gravityview_field_visibility_caps',
				array(
					array( 'label' => __( 'Any logged-in user', 'gravity-view' ), 'value' => 'read' ),
					array( 'label' => __( 'Author or higher', 'gravity-view' ), 'value' => 'publish_posts' ),
					array( 'label' => __( 'Editor or higher', 'gravity-view' ), 'value' => 'delete_others_posts' ),
					array( 'label' => __( 'Administrator', 'gravity-view' ), 'value' => 'manage_options' ),
				)
			);

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
					'default' => ''
				),
				'custom_class' => array(
					'type' => 'text',
					'label' => __( 'Custom CSS Class:', 'gravity-view' ),
					'desc' => __( 'This class will be added to the field container.', 'gravity-view'),
					'default' => ''
				),
				'only_loggedin' => array(
					'type' => 'checkbox',
					'label' => __( 'Make visible only to logged-in users?', 'gravity-view' ),
					'default' => ''
				),
				'only_loggedin_cap' => array(
					'type' => 'select',
					'label' => __( 'Make visible for:', 'gravity-view' ),
					'choices' => $select_cap_choices,
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
	public static function render_field_option( $name = '', $option, $current = NULL ) {

		$defaults = array(
			'default' => '',
			'desc' => '',
			'value' => NULL,
			'label' => '',
			'type'	=> 'text',
			'choices' => NULL,
		);

		$parsed_option = wp_parse_args( $option, $defaults );

		extract( $parsed_option );


		$output = '';

		if( is_null($current) ) {
			$current = $default;
		}

		$id = sanitize_html_class( $name );

		$output .= '<label for="'. $id .'" class="gv-label-'.sanitize_html_class( $type ).'">';

		if(!empty($desc)) {
			$desc = '<span class="howto">'.$desc.'</span>';
		}

		switch( $type ) {
			case 'checkbox':
				$output .= self::render_checkbox_option( $name, $id, $current );
				$output .= '&nbsp;'.$label.$desc;
				break;

			case 'select':
				$output .= $label.$desc.'&nbsp;';
				$output .= self::render_select_option( $name, $id, $choices, $current );
				break;

			case 'text':
			default:
				$output .= $label.$desc;
				$output .= '<div>';
				$output .= self::render_text_option( $name, $id, $current );
				$output .= '</div>';
				break;
		}

		$output .= '</label>';

		return $output;
	}


	/**
	 * Render the HTML for a checkbox input to be used on the field & widgets options
	 * @param  string $name , name attribute
	 * @param  string $current current value
	 * @return string         html tags
	 */
	public static function render_checkbox_option( $name = '', $id = '', $current = '' ) {

		$output  = '<input name="'. $name .'" type="hidden" value="0">';
		$output .= '<input name="'. $name .'" id="'. $id .'" type="checkbox" value="1" '. checked( $current, '1', false ) .' >';

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
	public static function render_text_option( $name = '', $id = '', $current = '', $add_merge_tags = true ) {

		// Show the merge tags only if the field is a list view,
		if( preg_match( '/_list-/', $name ) && $add_merge_tags ) {
			$merge_class = ' merge-tag-support mt-position-right mt-hide_all_fields';
		}

		return '<input name="'. $name .'" id="'. $id .'" type="text" value="'. $current .'" class="all-options'.$merge_class.'">';
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
		foreach( $choices as $choice ) {
			$output .= '<option value="'. $choice['value'] .'" '. selected( $choice['value'], $current, false ) .'>'. $choice['label'] .'</option>';
		}
		$output .= '</select>';

		return $output;
	}

	/** -------- AJAX ---------- */

	/**
	 * Verify the nonce. Exit if not verified.
	 * @return void
	 */
	function check_ajax_nonce() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxviews' ) ) {
			exit( false );
		}
	}


	/**
	 * Returns available fields given a form ID or a preset template ID
	 * AJAX callback
	 *
	 * @access public
	 * @return void
	 */
	function get_available_fields_html() {

		//check nonce
		$this->check_ajax_nonce();

		// If Form was changed, JS sends form ID, if start fresh, JS sends templateid
		if( !empty( $_POST['formid'] ) ) {
			$this->render_available_fields( (int) $_POST['formid'] );
			exit();
		} elseif( !empty( $_POST['templateid'] ) ) {
			$form = $this->pre_get_form_fields( $_POST['templateid'] );
			$this->render_available_fields( $form );
			exit();
		}

		//if everything fails..
		exit( false );
	}


	/**
	 * Returns template active areas given a template ID
	 * AJAX callback
	 *
	 * @access public
	 * @return void
	 */
	function get_active_areas() {
		$this->check_ajax_nonce();

		if( empty( $_POST['template_id'] ) ) {
			exit( false );
		}

		$response['directory'] = $this->render_directory_active_areas( $_POST['template_id'], 'directory' );
		$response['single'] = $this->render_directory_active_areas( $_POST['template_id'], 'single' );

		exit( json_encode( $response ) );
	}

	/**
	 * Fill in active areas with preset configuration according to the template selected
	 * @return void
	 */
	function get_preset_fields_config() {

		$this->check_ajax_nonce();

		if( empty( $_POST['template_id'] ) ) {
			exit( false );
		}

		// get the fields xml config file for this specific preset
		$preset_fields_path = apply_filters( 'gravityview_template_fieldsxml', array(), $_POST['template_id'] );
		// import fields
		if( !empty( $preset_fields_path ) ) {
			$presets = $this->import_fields( $preset_fields_path );
		} else {
			$presets = array( 'widgets' => array(), 'fields' => array() );
		}

		$template_id = esc_attr( $_POST['template_id'] );

		// template areas
		$template_areas = apply_filters( 'gravityview_template_active_areas', array(), $template_id );

		// widget areas
		$default_widget_areas = GravityView_Plugin::get_default_widget_areas();

		ob_start();
		$this->render_active_areas( $template_id, 'widget', 'header', $default_widget_areas, $presets['widgets'] );
		$response['header'] = ob_get_contents();
		ob_end_clean();

		ob_start();
		$this->render_active_areas( $template_id, 'widget', 'footer', $default_widget_areas, $presets['widgets'] );
		$response['footer'] = ob_get_contents();
		ob_end_clean();

		ob_start();
		$this->render_active_areas( $template_id, 'field', 'directory', $template_areas, $presets['fields'] );
		$response['directory'] = ob_get_contents();
		ob_end_clean();

		ob_start();
		$this->render_active_areas( $template_id, 'field', 'single', $template_areas, $presets['fields'] );
		$response['single'] = ob_get_contents();
		ob_end_clean();

		GravityView_Plugin::log_debug('[get_preset_fields_config] AJAX Response: '.print_r($response, true));

		exit( json_encode( $response ) );
	}

	/**
	 * Create the preset form requested before the View save
	 *
	 * @return void
	 */
	function create_preset_form() {

		$this->check_ajax_nonce();

		if( empty( $_POST['template_id'] ) ) {
			GravityView_Plugin::log_error( '[create_preset_form] Cannot create preset form; the template_id is empty.' );
			exit( false );
		}

		// get the xml for this specific template_id
		$preset_form_xml_path = apply_filters( 'gravityview_template_formxml', '', $_POST['template_id'] );

		// import form
		$form_id = $this->import_form( $preset_form_xml_path );

		// get the form ID
		if( $form_id === false ) {
			// send error to user
			GravityView_Plugin::log_error( '[create_preset_form] Error importing form for template id: ' . $_POST['template_id'] );

			exit( false );
		}

		echo '<option value="'.$form_id.'" selected></option>';

		exit();

	}


	/**
	 * Returns field options - called by ajax when dropping fields into active areas
	 * AJAX callback
	 *
	 * @access public
	 * @return void
	 */
	function get_field_options() {
		$this->check_ajax_nonce();

		if( empty( $_POST['template'] ) || empty( $_POST['area'] ) || empty( $_POST['field_id'] ) || empty( $_POST['field_type'] ) ) {
			exit( false );
		}

		// Fix apostrophes added by JSON response
		$post = array_map( 'stripslashes_deep', $_POST );

		// Sanitize
		$post = array_map( 'esc_attr', $post );

		// The GF type of field: `product`, `name`, `creditcard`, `id`, `text`
		$input_type = isset($post['input_type']) ? esc_attr( $post['input_type'] ) : NULL;
		$context = isset($post['context']) ? esc_attr( $post['context'] ) : NULL;

		$response = $this->render_field_options( $post['field_type'], $post['template'], $post['field_id'], $post['field_label'], $post['area'], $input_type, '', '', $context  );

		exit( $response );
	}

	/**
	 * Given a View id, calculates the assigned form, and returns the form fields (only the sortable ones )
	 * AJAX callback
	 *
	 *
	 * @access public
	 * @return void
	 */
	function get_sortable_fields() {
		$this->check_ajax_nonce();

		$form = '';

		// if form id is set, use it, else, get form from preset
		if( !empty( $_POST['form_id'] ) ) {

			$form = (int) $_POST['form_id'];

		} elseif( !empty( $_POST['template_id'] ) ) {

			$form = $this->pre_get_form_fields( $_POST['template_id'] );

		}

		$response = gravityview_get_sortable_fields( $form );

		exit( $response );
	}



	/**
	 * Uservoice feedback widget
	 * @group Beta
	 */
	static function enqueue_uservoice_widget() {
		wp_enqueue_script( 'gravityview-uservoice-widget', plugins_url('includes/js/uservoice.js', GRAVITYVIEW_FILE), array(), GravityView_Plugin::version, true);
		wp_localize_script( 'gravityview-uservoice-widget', 'gvUserVoice', array(
			'email' => GravityView_Settings::getSetting( 'support-email' )
		));
	}

	static function is_gravityview_admin_page($hook = '', $page = NULL) {
		global $current_screen, $plugin_page, $pagenow, $post;

		$is_page = false;

		if((!empty($current_screen) && isset($current_screen->post_type) && $current_screen->post_type === 'gravityview') || (isset($_GET['post_type']) && $_GET['post_type'] === 'gravityview') || (!empty($post) && !empty($post->post_type) && $post->post_type === 'gravityview') ) {

			// $_GET `post_type` variable
			if(in_array($pagenow, array( 'post.php' , 'post-new.php' )) ) {
				$is_page = 'single';
			} elseif ($plugin_page === 'settings') {
				$is_page = 'settings';
			} else {
				$is_page = 'views';
			}
		}

		$is_page = apply_filters( 'gravityview_is_admin_page', $is_page, $hook );

		// If the current page is the same as the compared page
		if(!empty($page)) {
			return $is_page === $page;
		}

		return $is_page;
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
		wp_enqueue_style( 'gravityview_fonts', plugins_url('includes/css/admin-fonts.css', GRAVITYVIEW_FILE), array() );

		wp_register_script( 'gravityview-jquery-cookie', plugins_url('includes/lib/jquery-cookie/jquery.cookie.js', GRAVITYVIEW_FILE), array( 'jquery' ), GravityView_Plugin::version, true );

		// Don't process any scripts below here if it's not a GravityView page.
		if(!self::is_gravityview_admin_page($hook)) { return; }


		// Add the UserVoice widget on all GV pages
		self::enqueue_uservoice_widget();

		// Only enqueue the following on single pages
		if(self::is_gravityview_admin_page($hook, 'single')) {

			wp_enqueue_script( 'jquery-ui-datepicker' );
			//wp_enqueue_style( 'gravityview_views_datepicker', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css' );
			wp_enqueue_style( 'gravityview_views_datepicker', plugins_url('includes/css/admin-datepicker.css', GRAVITYVIEW_FILE), GravityView_Plugin::version );

			//enqueue scripts
			wp_enqueue_script( 'gravityview_views_scripts', plugins_url('includes/js/admin-views.js', GRAVITYVIEW_FILE), array( 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'jquery-ui-tooltip', 'jquery-ui-dialog', 'gravityview-jquery-cookie'  ), GravityView_Plugin::version);

			wp_localize_script('gravityview_views_scripts', 'gvGlobals', array(
				'cookiepath' => COOKIEPATH,
				'nonce' => wp_create_nonce( 'gravityview_ajaxviews' ),
				'label_viewname' => __( 'Enter View name here', 'gravity-view' ),
				'label_close' => __( 'Close', 'gravity-view' ),
				'label_cancel' => __( 'Cancel', 'gravity-view' ),
				'label_continue' => __( 'Continue', 'gravity-view' ),
				'label_ok' => __( 'Ok', 'gravity-view' ),
				'label_publisherror' => __( 'Error while creating the View for you. Check the settings or contact GravityView support.', 'gravity-view' ),
			));

			wp_enqueue_style( 'gravityview_views_styles', plugins_url('includes/css/admin-views.css', GRAVITYVIEW_FILE), array('dashicons', 'wp-jquery-ui-dialog' ), GravityView_Plugin::version );

			self::enqueue_gravity_forms_scripts();

		} // End single page
	}

	function enqueue_gravity_forms_scripts() {
		GFForms::register_scripts();

		$thickbox = !GFCommon::is_wp_version("3.3") ? 'gf_thickbox' : 'thickbox';
		$scripts = array(
		    $thickbox,
		    'jquery-ui-core',
		    'jquery-ui-sortable',
		    'jquery-ui-tabs',
		    'sack',
		    'gform_gravityforms',
		    'gform_forms',
		    'gform_json',
		    'gform_form_admin',
		    'gform_floatmenu',
		    'gform_menu',
		    'gform_placeholder',
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
			$allow_scripts = array( 'jquery-ui-dialog', 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'jquery-ui-tooltip', 'gravityview_views_scripts', 'gravityview-uservoice-widget', 'gravityview-jquery-cookie', 'gravityview_views_datepicker' );
			$registered = array_merge( $registered, $allow_scripts );
		} elseif( preg_match('/style/ism', $filter ) ) {
			$allow_styles = array( 'dashicons', 'wp-jquery-ui-dialog', 'gravityview_views_styles', 'gravityview_fonts', 'gravityview_views_datepicker' );
			$registered = array_merge( $registered, $allow_styles );
		}

		return $registered;
	}


}

new GravityView_Admin_Views;
