<?php

/**
 * Nearly great working edit entry...it doesn't store existing images.
 *
 *
 */
class GravityView_Edit_Entry {

	static $file;
	static $nonce_key;
	static $instance;
	var $entry;
	var $form;
	var $view_id;

	function __construct() {

		self::$instance = &$this;

		self::$file = plugin_dir_path( __FILE__ );

		add_filter('gravityview_is_edit_entry', array( $this, 'is_edit_entry') );

		add_action( 'gravityview_edit_entry', array( $this, 'init' ) );

		add_filter( 'gravityview_additional_fields', array( $this, 'add_available_field' ));

		// Modify the field options based on the name of the field type
		add_filter( 'gravityview_template_edit_link_options', array( &$this, 'field_options' ), 10, 5 );

		// add template path to check for field
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

		//
		add_action('wp_ajax_nopriv_rg_delete_file', array('RGForms', 'delete_file'));
	}

	function getInstance() {

		if( !empty( self::$instance ) ) {
			return self::$instance;
		} else {
			self::$instance = new GravityView_Edit_Entry;
			return self::$instance;
		}
	}

	function setup_vars() {
		global $gravityview_view;

		$this->entry = $gravityview_view->entries[0];
		$this->form = $gravityview_view->form;
		$this->form_id = $gravityview_view->form_id;
		$this->view_id = $gravityview_view->view_id;

		self::$nonce_key = sprintf( 'edit_%d_%d_%d', $this->view_id, $this->form_id, $this->entry['id'] );
	}

	/**
	 * The edit entry link creates a secure link with a nonce
	 *
	 * It also mimics the URL structure Gravity Forms expects to have so that
	 * it formats the display of the edit form like it does in the backend, like
	 * "You can edit this post from the post page" fields, for example.
	 *
	 * @filter default text
	 * @action default text
	 * @param  [type]      $entry [description]
	 * @param  [type]      $field [description]
	 * @return [type]             [description]
	 */
	function get_edit_link( $entry, $field ) {
		global $gravityview_view;

		if( empty( self::$nonce_key ) ) {
			self::getInstance()->setup_vars();
		}

		$base = gv_entry_link( $entry, $field );

		$url = add_query_arg( array(
			'page' => 'gf_entries', // Needed for GFForms::get_page()
			'view' => 'entry', // Needed for GFForms::get_page()
			'edit' => wp_create_nonce( self::$nonce_key )
		), $base );

		return $url;
	}

	/**
	 * Include this extension templates path
	 * @param array $file_paths List of template paths ordered
	 */
	function add_template_path( $file_paths ) {

		// Index 100 is the default GravityView template path.
		$file_paths[ 110 ] = self::$file;

		return $file_paths;
	}

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		// Always a link!
		unset( $field_options['show_as_link'] );

		// Always only shown to users

		$add_options = array();
		$add_options['edit_link'] = array(
			'type' => 'text',
			'label' => __( 'Edit Link Text', 'gravity-view' ),
			'desc' => NULL,
			'default' => __('Edit Entry', 'gravity-view'),
			'merge_tags' => true,
		);

		return $add_options + $field_options;
	}

	function add_available_field( $available_fields = array() ) {

		$available_fields['edit_link'] = array(
			'label_text' => __( 'Edit Entry Link', 'gravity-view' ),
			'field_id' => 'edit_link',
			'label_type' => 'field',
			'input_type' => 'edit_link',
			'field_options' => NULL
		);

		return $available_fields;
	}

	/**
	 * Force Gravity Forms to output scripts as if it were in the admin
	 * @return [type]      [description]
	 */
	function print_scripts() {
		global $gravityview_view;
		wp_register_script( 'gform_gravityforms', GFCommon::get_base_url().'/js/gravityforms.js', array( 'jquery', 'gform_json', 'gform_placeholder', 'sack','plupload-all' ) );

		GFForms::enqueue_admin_scripts();
		GFFormDisplay::enqueue_form_scripts($gravityview_view->form, false);
		GFForms::print_scripts();

		echo '<style>
			.detail-label { display:block; }
			.gv-error { padding:.5em .75em; background-color:#ffffcc; border:1px solid #ccc; }
		</style>';

		wp_enqueue_style('gform_gravityforms_admin', GFCommon::get_base_url().'/css/admin.css');
	}

	/**
	 * Load required files and trigger edit flow
	 *
	 * Run when the is_edit_entry returns true.
	 * @return void
	 */
	function init() {
		global $gravityview_view;

		require_once(GFCommon::get_base_path() . "/form_display.php");
		require_once(GFCommon::get_base_path() . "/entry_detail.php");

		if( !class_exists( 'GFEntryDetail' )) {
			GravityView_Plugin::log_error( 'GFEntryDetail does not exist' );
		}

		$this->setup_vars();

		// Sorry bro, you're not allowed here.
		if( false === $this->user_can_edit_entry( true ) ) {
			return;
		}

		$this->print_scripts( true );
		$this->process_save();
		$this->edit_entry_form();
	}

	function process_save() {
		global $gravityview_view;
		 // If the form is submitted
		if(RGForms::post("action") === "update") {

	        // Make sure the entry, view, and form IDs are all correct
	        check_admin_referer( self::$nonce_key, self::$nonce_key );

	        $lead_id = absint( $_POST['lid'] );

	        //Loading files that have been uploaded to temp folder
	        $files = GFCommon::json_decode(stripslashes(RGForms::post("gform_uploaded_files")));
	        if(!is_array($files)) {
	            $files = array();
	        }

	        GFFormsModel::$uploaded_files[$this->form_id] = $files;
	        GFFormsModel::save_lead( $this->form, $this->entry );

	        do_action("gform_after_update_entry", $this->form, $this->entry["id"]);
	        do_action("gform_after_update_entry_{$this->form["id"]}", $this->form, $this->entry["id"]);

	        // Update the
	        $this->entry = RGFormsModel::get_lead( $this->entry["id"] );
			$this->entry = GFFormsModel::set_entry_meta( $this->entry, $this->form);
		}
	}

	function is_edit_entry() {

		$gf_page = ( 'entry' === RGForms::get("view") );

		return ( $gf_page && isset( $_GET['edit'] ) || RGForms::post("action") === "update" );
	}

	function create_nonce() {

		return wp_create_nonce( self::$nonce_key );

	}

	function verify_nonce() {

		return wp_verify_nonce( $_GET['edit'], self::$nonce_key );

	}

	function user_can_edit_entry( $echo = false ) {

		$error = NULL;

		if( ! $this->verify_nonce() ) {
			$error = __( 'The link to edit this entry is not valid; it may have expired.', 'gravity-view');
		}

		if( ! GFCommon::current_user_can_any("gravityforms_edit_entries") ) {
			$error = __( 'You do not have permission to edit this entry.', 'gravity-view');
		}

		if( $this->entry['status'] === 'trash' ) {
			$error = __('You cannot edit the entry; it is in the trash.', 'gravity-view' );
			return false;
		}

		// No errors; everything's fine here!
		if( empty( $error ) ) { return true; }

		if( $echo ) {
			echo '<p class="gv-error error">'. esc_html( $error ).'</p>';
		}

		return false;
	}

	public function edit_entry_form() {	?>
		<h2 class="gf_admin_page_title">
			<span><?php echo __("Entry #", "gravityforms") . absint($this->entry["id"]); ?></span>
			<span class="gf_admin_page_subtitle">
				<span class="gf_admin_page_formid">ID: <?php echo $this->form['id']; ?></span>
				<?php echo $this->form['title']; ?>
			</span>
		</h2>

		<?php // The ID of the form needs to be `gform_{form_id}` for the pluploader ?>
		<form method="post" id="gform_<?php echo $this->form_id; ?>" enctype='multipart/form-data'>
		    <?php wp_nonce_field( self::$nonce_key, self::$nonce_key ); ?>
		    <input type="hidden" name="action" id="action" value="update"/>
		    <input type="hidden" name="screen_mode" id="screen_mode" value="view" />
		    <input type="hidden" name="lid" value="<?php echo absint($this->entry["id"]); ?>" />
	<?php
			GFEntryDetail::lead_detail_edit( $this->form, $this->entry );
	?>
		<div id="publishing-action">
		    <input class="button button-large button-primary" type="submit" tabindex="4" value="<?php esc_attr_e( 'Update', 'gravity-view'); ?>" name="save" />

            <a class="button button-small" tabindex="5" href="<?php echo remove_query_arg( array( 'page', 'view', 'edit' ) ); ?>"><?php esc_attr_e( 'Cancel', 'gravity-view' ); ?></a>
		</div>
<?php
		GFFormDisplay::footer_init_scripts($this->form_id);
	}

}

new GravityView_Edit_Entry;
