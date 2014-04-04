<?php
/**
 *
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2013, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */



class GravityView_Admin_ApproveEntries {

	// hold notification messages
	public $bulk_update_message = '';

	function __construct() {

		/** Edit Gravity Form page */

		// Add button to left menu
		add_filter( 'gform_add_field_buttons', array( $this, 'add_field_buttons' ) );
		// Set defaults
		add_action( 'gform_editor_js_set_default_values', array( $this, 'set_defaults' ) );

		/** gf_entries page - entries table screen */

		// capture bulk actions
		add_action( 'init', array( $this, 'process_bulk_action') );
		// add hidden field with approve status
		add_action( 'gform_entries_first_column', array( $this, 'add_entry_approved_hidden_input' ), 1, 5 );
		// process ajax approve entry requests
		add_action('wp_ajax_gv_update_approved', array( $this, 'ajax_update_approved'));


		// adding styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_styles') );
		// bypass Gravity Forms no-conflict mode
		add_filter( 'gform_noconflict_scripts', array( $this, 'register_gf_script' ) );
		add_filter( 'gform_noconflict_styles', array( $this, 'register_gf_style' ) );

	}


	/**
	 * Inject new add field buttons in the gravity form editor page
	 *
	 * @access public
	 * @param mixed $field_groups
	 * @return void
	 */
	function add_field_buttons( $field_groups ) {

		$gravityview_fields = array(
			'name' => 'gravityview_fields',
			'label' => 'GravityView Fields',
			'fields' => array(
				array(
					'class' => 'button',
					'value' => __( 'Approved', 'gravity-view' ),
					'onclick' => "StartAddField('gravityviewapproved');"
				),
			)
		);

		array_push( $field_groups, $gravityview_fields );

		return $field_groups;
	}



	/**
	 * At edit form page, set the field Approve defaults
	 *
	 * @access public
	 * @return void
	 */
	function set_defaults() {
		?>
		case 'gravityviewapproved':
			field.label = "<?php _e( 'Approved? (Admin-only)', 'gravity-view' ); ?>";

			field.adminLabel = "<?php _e( 'Approved?', 'gravity-view' ); ?>";
			field.adminOnly = true;

			field.choices = null;
			field.inputs = null;

			if( !field.choices ) {
				field.choices = new Array( new Choice("<?php _e( 'Approved', 'gravity-view' ); ?>") );
			}

			field.inputs = new Array();
			for( var i=1; i<=field.choices.length; i++ ) {
				field.inputs.push(new Input(field.id + (i/10), field.choices[i-1].text));
			}

			field.type = 'checkbox';

			break;
		<?php
	}




	/**
	 * Capture bulk actions - gf_entries table
	 *
	 * @access public
	 * @return void
	 */
	public function process_bulk_action() {
		if( !class_exists( 'RGForms' ) ) {
			return;
		}

		if( RGForms::post('action') === 'bulk' ) {

			check_admin_referer('gforms_entry_list', 'gforms_entry_list');

			$bulk_action = !empty( $_POST['bulk_action'] ) ? $_POST['bulk_action'] : $_POST['bulk_action2'];
			$entries = $_POST['lead'];

			$entry_count = count( $entries ) > 1 ? sprintf(__("%d entries", 'gravity-view' ), count( $entries ) ) : __( '1 entry', 'gravityforms' );

			$bulk_action = explode( '-', $bulk_action );
			if( !isset( $bulk_action[1] ) || empty( $entries ) ) { return false; }

			switch( $bulk_action[0] ) {
				case 'approve':
					self::update_bulk( $entries, 1, $bulk_action[1] );
					$this->bulk_update_message = sprintf( __( "%s approved.", 'gravity-view' ), $entry_count );
					break;

				case 'unapprove':
					self::update_bulk( $entries, 0, $bulk_action[1]);
					$this->bulk_update_message = sprintf( __( "%s disapproved.", 'gravity-view' ), $entry_count );
					break;
			}
		}
	}





	/**
	 * Process a bulk of entries to update the approve field/property
	 *
	 * @access private
	 * @static
	 * @param mixed $entries
	 * @param mixed $approved
	 * @param mixed $form_id
	 * @return void
	 */
	private static function update_bulk( $entries, $approved, $form_id ) {

		if( empty($entries) || !is_array($entries) ) { return false; }

		$approved = empty( $approved ) ? 0 : 'Approved';

		// calculate approved field id
		$approved_column_id = self::get_approved_column( $form_id );

		foreach( $entries as $entry_id ) {
			self::update_approved( $entry_id, $approved, $form_id, $approved_column_id );
		}
	}




	/**
	 * update_approved function.
	 *
	 * @access public
	 * @static
	 * @param int $lead_id (default: 0)
	 * @param int $approved (default: 0)
	 * @param int $form_id (default: 0)
	 * @param int $approvedcolumn (default: 0)
	 * @return void
	 */
	public static function update_approved( $entry_id = 0, $approved = 0, $form_id = 0, $approvedcolumn = 0) {

		if( !class_exists( 'GFAPI' ) ) {
			return;
		}

		if( empty( $approvedcolumn ) ) {
			$approvedcolumn = self::get_approved_column( $form_id );
		}

		$current_user = wp_get_current_user();
		$user_data = get_userdata($current_user->ID);

		//get the entry
		$entry = GFAPI::get_entry( $entry_id );

		//update entry
		$entry[ (string)$approvedcolumn ] = $approved;
		$result = GFAPI::update_entry( $entry );

		// update entry meta
		if( function_exists('gform_update_meta') ) { gform_update_meta( $entry_id, 'is_approved', $approved); }

		// add note to entry
		if( $result === true ) {
			$note = empty( $approved ) ? __( 'Disapproved the lead', 'gravity-view' ) : __( 'Approved the lead', 'gravity-view' );
			if( class_exists( 'RGFormsModel' ) ){
				RGFormsModel::add_note( $entry_id, $current_user->ID, $user_data->display_name, $note );
			}

		}

	}



	public function ajax_update_approved() {
		$response = false;

		if( empty( $_POST['entry_id'] ) || empty( $_POST['form_id'] ) ) {
			echo $response;
			die();
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxgfentries' ) ) {
			echo $response;
			die();
		}

		$this->update_approved( $_POST['entry_id'], $_POST['approved'], $_POST['form_id'] );

		$response = true;
		echo $response;
		die();


	}


	/**
	 * Calculate the approve field.input id
	 *
	 * @access public
	 * @static
	 * @param mixed $form_id
	 * @return void
	 */
	static public function get_approved_column( $form_id ) {
		if( empty( $form_id ) ) {
			return false;
		}

		$form = gravityview_get_form( $form_id );

		foreach( $form['fields'] as $key => $field ) {
			if( !empty( $field['adminOnly'] ) && 'checkbox' == $field['type'] && isset( $field['inputs'] ) && is_array( $field['inputs'] ) ) {
				foreach( $field['inputs'] as $key2 => $input) {
					if( strtolower( $input['label'] ) == 'approved' ) {
						return $input['id'];
					}
				}
			}
		}

		return null;
	}



	static public function add_entry_approved_hidden_input(  $form_id, $field_id, $value, $entry, $query_string ) {
		if( empty( $entry['id'] ) ) {
			return;
		}
		if( gform_get_meta( $entry['id'], 'is_approved' ) ) {
			echo '<input type="hidden" class="entry_approved" id="entry_approved_'. $entry['id'] .'" value="true" />';
		}
	}




	function add_scripts_and_styles( $hook ) {

		if( !class_exists( 'RGForms' ) ) {
			return;
		}


		//enqueue styles & scripts gf_entries
		if( 'forms_page_gf_entries' == $hook ) {

			$form_id = RGForms::get('id');
			$approvedcolumn = $this->get_approved_column( $form_id );

			if( empty( $approvedcolumn ) ) {
				return;
			}

			wp_register_style( 'gravityview_entries_list', GRAVITYVIEW_URL . 'includes/css/admin-entries-list.css', array() );
			wp_enqueue_style( 'gravityview_entries_list' );

			wp_register_script( 'gravityview_gf_entries_scripts',  GRAVITYVIEW_URL  . 'includes/js/admin-entries-list.js', array( 'jquery' ), '1.0.0');
			wp_enqueue_script( 'gravityview_gf_entries_scripts' );

			wp_localize_script( 'gravityview_gf_entries_scripts', 'gvGlobals', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'gravityview_ajaxgfentries'),
				'form_id' => RGForms::get('id'),
				'label_approve' => __( 'Approve', 'gravity-view' ) ,
				'label_disapprove' => __( 'Disapprove', 'gravity-view' ),
				'bulk_message' => $this->bulk_update_message,
				'approve_title' => __( 'Entry not approved for directory viewing. Click to approve this entry.', 'gravity-view'),
				'unapprove_title' => __( 'Entry approved for directory viewing. Click to disapprove this entry.', 'gravity-view'),
				'column_title' => __( 'Show entry in directory view?', 'gravity-view'),
				'column_link' => add_query_arg( array('sort' => $approvedcolumn) ),
			) );

		}

	}

	function register_gf_script( $scripts ) {
		$scripts[] = 'gravityview_gf_entries_scripts';
		return $scripts;
	}

	function register_gf_style( $styles ) {
		$styles[] = 'gravityview_entries_list';
		return $$styles;
	}

}

new GravityView_Admin_ApproveEntries;