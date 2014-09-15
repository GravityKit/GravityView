<?php

class GravityView_Admin_Metaboxes {

	function __construct() {

		// Make Yoast go down to the bottom please.
		add_filter('wpseo_metabox_prio', array( $this, 'return_low') );

		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ));

		// Fix annoying 3rd party metabox behavior
		// Remove metaboxes. We need to run this twice for Genesis (9) and others (11). Default is 10.
		add_action( 'admin_menu' , array( $this, 'remove_other_metaboxes' ), 9 );
		add_action( 'admin_menu' , array( $this, 'remove_other_metaboxes' ), 11 );
		// Add them back in
		add_action( 'add_meta_boxes', array( $this, 'add_other_metaboxes' ), 20 );

	}

	/**
	 * Return 'low' as the status for metabox priority
	 * @return string 'low'
	 */
	function return_low() {
		return 'low';
	}

	function register_metabox() {

		// select data source for this view
		add_meta_box( 'gravityview_select_form', __( 'Data Source', 'gravity-view' ), array( $this, 'render_select_form_metabox' ), 'gravityview', 'normal', 'high' );

		// select view type/template
		add_meta_box( 'gravityview_select_template', __( 'Choose a View Type', 'gravity-view' ), array( $this, 'render_select_template_metabox' ), 'gravityview', 'normal', 'high' );

		// View Configuration box
		add_meta_box( 'gravityview_view_config', __( 'View Configuration', 'gravity-view' ), array( $this, 'render_view_configuration_metabox' ), 'gravityview', 'normal', 'high' );

		// Other Settings box
		add_meta_box( 'gravityview_template_settings', __( 'View Settings', 'gravity-view' ), array( $this, 'render_view_settings_metabox' ), 'gravityview', 'side', 'core' );

		// Filter & Sort box
		add_meta_box( 'gravityview_sort_filter', __( 'Filter &amp; Sort', 'gravity-view' ), array( $this, 'render_sort_filter_metabox' ), 'gravityview', 'normal', 'high' );

		// information box
		add_action( 'post_submitbox_misc_actions', array( $this, 'render_shortcode_hint' ) );

	}

	/**
	 * Render html for 'select form' metabox
	 *
	 * @access public
	 * @param object $post
	 * @return void
	 */
	function render_select_form_metabox( $post ) {

		if( !empty( $post->ID ) ) {
			$this->post_id = $post->ID;
		}

		// Use nonce for verification
		wp_nonce_field( 'gravityview_select_form', 'gravityview_select_form_nonce' );

		//current value
		$current_form = gravityview_get_form_id( $post->ID );

		// input ?>
		<label for="gravityview_form_id" ><?php esc_html_e( 'Where would you like the data to come from for this View?', 'gravity-view' ); ?></label>

		<?php
		// check for available gravity forms
		$forms = gravityview_get_forms();
		?>

		<p>
			<?php if ( empty( $current_form ) ) : ?>
				<?php // render "start fresh" button ?>
				<a class="button button-primary" href="#gv_start_fresh" title="<?php esc_attr_e( 'Start Fresh', 'gravity-view' ); ?>"><?php esc_html_e( 'Start Fresh', 'gravity-view' ); ?></a>

				<?php if( !empty( $forms ) ) { ?>
				<span>&nbsp;<?php esc_html_e( 'or use an existing form', 'gravity-view' ); ?>&nbsp;</span>
				<?php } ?>

			<?php endif; ?>

			<?php
			// If there are no forms to select, show no forms.
			if( !empty( $forms ) ) { ?>
			<select name="gravityview_form_id" id="gravityview_form_id">
				<option value="" <?php selected( '', $current_form, true ); ?>>&mdash; <?php esc_html_e( 'list of forms', 'gravity-view' ); ?> &mdash;</option>
				<?php foreach( $forms as $form ) : ?>
					<option value="<?php echo $form['id']; ?>" <?php selected( $form['id'], $current_form, true ); ?>><?php echo $form['title']; ?></option>
				<?php endforeach; ?>
			</select>
			<?php } ?>

			&nbsp;<a class="button button-primary" <?php if( empty( $current_form ) ) { echo 'style="display:none;"'; } ?> id="gv_switch_view_button" href="#gv_switch_view" title="<?php esc_attr_e( 'Switch View', 'gravity-view' ); ?>"><?php esc_html_e( 'Switch View Type', 'gravity-view' ); ?></a>

		</p>

		<?php // confirm dialog box ?>
		<div id="gravityview_form_id_dialog" class="gv-dialog-options gv-dialog-warning" title="<?php esc_attr_e( 'Attention', 'gravity-view' ); ?>">
			<p><?php esc_html_e( 'Changing the form will reset your field configuration. Changes will be permanent once you save the View.', 'gravity-view' ); ?></p>
		</div>

		<?php // confirm template dialog box ?>
		<div id="gravityview_switch_template_dialog" class="gv-dialog-options gv-dialog-warning" title="<?php esc_attr_e( 'Attention', 'gravity-view' ); ?>">
			<p><?php esc_html_e( 'Changing the View Type will reset your field configuration. Changes will be permanent once you save the View.', 'gravity-view' ); ?></p>
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
	function render_select_template_metabox( $post ) {

		// Use nonce for verification
		wp_nonce_field( 'gravityview_select_template', 'gravityview_select_template_nonce' );

		//current value
		$current_template = gravityview_get_template_id( $post->ID );

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
									<p><a href="#gv_select_template" class="button button-large button-primary" data-templateid="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Select', 'gravity-view'); ?></a></p>
									<?php if( !empty( $template['preview'] ) ) : ?>
										<a href="<?php echo esc_url( $template['preview'] ); ?>" rel="external" class="gv-site-preview"><i class="dashicons dashicons-admin-links" title="<?php esc_html_e( 'View a live demo of this preset', 'gravity-view'); ?>"></i></a>
									<?php endif; ?>
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
	 * Generate the script tags necessary for the Gravity Forms Merge Tag picker to work.
	 *
	 * Creates
	 * @filter default text
	 * @action default text
	 * @param  [type]      $curr_form [description]
	 * @return [type]                 [description]
	 */
	function render_merge_tags_scripts( $curr_form ) {

		if( empty( $curr_form )) { return; }

		$form = gravityview_get_form( $curr_form );

		$get_id_backup = isset($_GET['id']) ? $_GET['id'] : NULL;

		if( isset( $form['id'] ) ) {
		    $form_script = 'var form = ' . GFCommon::json_encode($form) . ';';

		    // The `gf_vars()` method needs a $_GET[id] variable set with the form ID.
		    $_GET['id'] = $form['id'];

		} else {
		    $form_script = 'var form = new Form();';
		}

		$output = '<script type="text/javascript" data-gv-merge-tags="1">' . $form_script . "\n" . GFCommon::gf_vars(false) . '</script>';

		// Restore previous $_GET setting
		$_GET['id'] = $get_id_backup;

		return $output;
	}

	/**
	 * Render html for 'View Configuration' metabox
	 *
	 * @access public
	 * @param mixed $post
	 * @return void
	 */
	function render_view_configuration_metabox( $post ) {

		// Use nonce for verification
		wp_nonce_field( 'gravityview_view_configuration', 'gravityview_view_configuration_nonce' );

		// Selected Form
		$curr_form = gravityview_get_form_id( $post->ID );

		// Selected template
		$curr_template = gravityview_get_template_id( $post->ID );

		echo $this->render_merge_tags_scripts( $curr_form );
?>
		<div id="gv-view-configuration-tabs">

			<ul class="nav-tab-wrapper">
				<li><a href="#directory-view" class="nav-tab"><i class="dashicons dashicons-admin-page"></i> <?php esc_html_e( 'Multiple Entries', 'gravity-view' ); ?></a></li>
				<li><a href="#single-view" class="nav-tab"><i class="dashicons dashicons-media-default"></i> <?php esc_html_e( 'Single Entry', 'gravity-view' ); ?></a></li>
			</ul>

			<div id="directory-view">

				<div id="directory-fields" class="gv-section">

					<h4><?php esc_html_e( 'Above Entries Widgets', 'gravity-view'); ?> <span><?php esc_html_e( 'These widgets will be shown above entries.', 'gravity-view'); ?></span></h4>

					<?php do_action('gravityview_render_widgets_active_areas', $curr_template, 'header', $post->ID ); ?>

					<h4><?php esc_html_e( 'Entries Fields', 'gravity-view'); ?> <span><?php esc_html_e( 'These fields will be shown for each entry.', 'gravity-view'); ?></span></h4>

					<div id="directory-active-fields" class="gv-grid gv-grid-pad gv-grid-border">
						<?php if(!empty( $curr_template ) ) {
							do_action('gravityview_render_directory_active_areas', $curr_template, 'directory', $post->ID, true );
						} ?>
					</div>

					<h4><?php esc_html_e( 'Below Entries Widgets', 'gravity-view'); ?> <span><?php esc_html_e( 'These widgets will be shown below entries.', 'gravity-view'); ?></span></h4>

					<?php do_action('gravityview_render_widgets_active_areas', $curr_template, 'footer', $post->ID ); ?>


					<?php // list of available fields to be shown in the popup ?>
					<div id="directory-available-fields" class="hide-if-js gv-tooltip">
						<span class="close"><i class="dashicons dashicons-dismiss"></i></span>
						<?php do_action('gravityview_render_available_fields', $curr_form, 'directory' ); ?>
					</div>

					<?php // list of available widgets to be shown in the popup ?>
					<div id="directory-available-widgets" class="hide-if-js gv-tooltip">
						<span class="close"><i class="dashicons dashicons-dismiss"></i></span>
						<?php do_action('gravityview_render_available_widgets' ); ?>
					</div>

				</div>


			</div><?php //end directory tab ?>



			<?php // Single View Tab ?>

			<div id="single-view">

				<div id="single-fields" class="gv-section">

					<h4><?php esc_html_e( 'These fields will be shown in Single Entry view.', 'gravity-view'); ?></h4>

					<div id="single-active-fields" class="gv-grid gv-grid-pad gv-grid-border">
						<?php if(!empty( $curr_template ) ) {
							do_action('gravityview_render_directory_active_areas', $curr_template, 'single', $post->ID, true );
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
	function render_view_settings_metabox( $post ) {

		$curr_form = gravityview_get_form_id( $post->ID );

		// View template settings
		$current_settings = gravityview_get_template_settings( $post->ID );

		?>

		<table class="form-table">

		<?php

			GravityView_Admin_Views::render_setting_row( 'page_size', $current_settings );

			GravityView_Admin_Views::render_setting_row( 'lightbox', $current_settings );

			GravityView_Admin_Views::render_setting_row( 'show_only_approved', $current_settings );

			GravityView_Admin_Views::render_setting_row( 'hide_empty', $current_settings );

			GravityView_Admin_Views::render_setting_row( 'user_edit', $current_settings );

			do_action( 'gravityview_admin_directory_settings', $current_settings );

		?>

		</table>

		<h3 style="margin-top:1em;"><?php esc_html_e( 'Single Entry Settings', 'gravity-view'); ?>:</h3>

		<table class="form-table"><?php

			GravityView_Admin_Views::render_setting_row( 'single_title', $current_settings );

			GravityView_Admin_Views::render_setting_row( 'back_link_label', $current_settings );

		?>
		</table>

		<?php

	}

	function render_sort_filter_metabox( $post ) {

		$curr_form = gravityview_get_form_id( $post->ID );

		// View template settings
		$current_settings = gravityview_get_template_settings( $post->ID );

		?>
		<table class="form-table">

			<?php

			do_action( 'gravityview_metabox_sort_filter_before', $current_settings );

			// Begin Sort fields
			do_action( 'gravityview_metabox_sort_before', $current_settings );

			$sort_fields_input = '<select name="template_settings[sort_field]" id="gravityview_sort_field">'.gravityview_get_sortable_fields( $curr_form, $current_settings['sort_field'] ).'</select>';

			GravityView_Admin_Views::render_setting_row( 'sort_field', $current_settings, $sort_fields_input );

			GravityView_Admin_Views::render_setting_row( 'sort_direction', $current_settings );

			// End Sort fields
			do_action( 'gravityview_metabox_sort_after', $current_settings );

			// Begin Filter fields
			do_action( 'gravityview_metabox_filter_before', $current_settings );

			GravityView_Admin_Views::render_setting_row( 'start_date', $current_settings );

			GravityView_Admin_Views::render_setting_row( 'end_date', $current_settings );

			// End Filter fields
			do_action( 'gravityview_metabox_filter_after', $current_settings );

			do_action( 'gravityview_metabox_sort_filter_after', $current_settings );

			?>

		</table>
		<?php
	}



	/**
	 * Render shortcode hint in the Publish metabox
	 *
	 * @access public
	 * @param object $post
	 * @return void
	 */
	function render_shortcode_hint() {
		global $post;

		// Only show this on GravityView post types.
		if( false === gravityview_is_admin_page() ) { return; }

		printf('<div class="misc-pub-section gv-shortcode misc-pub-section-last"><i class="dashicons dashicons-editor-code"></i> <span>%s</span><div><input type="text" readonly="readonly" value="[gravityview id=\'%d\']" class="code widefat" /><span class="howto">%s</span></div></div>', __( 'Embed Shortcode', 'gravity-view' ), $post->ID, esc_html__( 'Add this shortcode to a post or page to embed this view.', 'gravity-view' ) );
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

		$gv_page = gravityview_is_admin_page();

		// New View or Edit View page
		if($gv_page === 'single') {

			// Prevent the SEO from being checked. Eesh.
			add_filter( 'wpseo_use_page_analysis', '__return_false' );

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

		if(!gravityview_is_admin_page()) { return; }

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

}

new GravityView_Admin_Metaboxes;
