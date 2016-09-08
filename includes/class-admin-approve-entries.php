<?php
/**
 * @file class-admin-approve-entries.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */



class GravityView_Admin_ApproveEntries {

	// hold notification messages
	public $bulk_update_message = '';

	/**
	 * @var array Set the prefixes here instead of spread across the class
	 * @since 1.17
	 */
	private $bulk_action_prefixes = array(
		'approve' => 'gvapprove',
		'unapprove' => 'gvunapprove',
	);

	function __construct() {

		$this->add_hooks();

	}

	private function add_hooks() {
		/** Edit Gravity Form page */

		// Add button to left menu
		add_filter( 'gform_add_field_buttons', array( $this, 'add_field_buttons' ) );
		// Set defaults
		add_action( 'gform_editor_js_set_default_values', array( $this, 'set_defaults' ) );

		/** gf_entries page - entries table screen */

		// capture bulk actions
		add_action( 'gform_loaded', array( $this, 'process_bulk_action') );

		// add hidden field with approve status
		add_action( 'gform_entries_first_column_actions', array( $this, 'add_entry_approved_hidden_input' ), 1, 5 );

		// process ajax approve entry requests
		add_action('wp_ajax_gv_update_approved', array( $this, 'ajax_update_approved'));

		// when using the User opt-in field, check on entry submission
		add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );

		// in case entry is edited (on admin or frontend)
		add_action( 'gform_after_update_entry', array( $this, 'after_update_entry_update_approved_meta' ), 10, 2);


		add_filter( 'gravityview_tooltips', array( $this, 'tooltips' ) );

		// adding styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_styles') );
		// bypass Gravity Forms no-conflict mode
		add_filter( 'gform_noconflict_scripts', array( $this, 'register_gform_noconflict_script' ) );
		add_filter( 'gform_noconflict_styles', array( $this, 'register_gform_noconflict_style' ) );

		add_filter( 'gform_filter_links_entry_list', array( $this, 'filter_links_entry_list' ), 10, 3 );
	}

	/**
	 * Add filter links to the Entries page
	 *
	 * Can be disabled by returning false on the `gravityview/approve_entries/show_filter_links_entry_list` filter
	 *
	 * @since 1.17.1
	 *
	 * @param array $filter_links Array of links to include in the subsubsub filter list. Includes `id`, `field_filters`, `count`, and `label` keys
	 * @param array $form GF Form object of current form
	 * @param bool $include_counts Whether to include counts in the output
	 *
	 * @return array Filter links, with GravityView approved/disapproved links added
	 */
	public function filter_links_entry_list( $filter_links = array(), $form = array(), $include_counts = true ) {

		/**
		 * @filter `gravityview/approve_entries/show_filter_links_entry_list` Disable filter links
		 * @since 1.17.1
		 * @param bool $show_filter_links True: show the "approved"/"disapproved" filter links. False: hide them.
		 * @param array $form GF Form object of current form
		 */
		if( false === apply_filters( 'gravityview/approve_entries/show_filter_links_entry_list', true, $form ) ) {
			return $filter_links;
		}

		$field_filters_approved = array(
			array(
				'key' => 'is_approved',
				'value' => 'Approved'
			),
		);

		$field_filters_disapproved = array(
			array(
				'key'      => 'is_approved',
				'value'    => '0',
			),
		);

		$approved_count = $disapproved_count = 0;

		// Only count if necessary
		if( $include_counts ) {
			$approved_count = count( gravityview_get_entry_ids( $form['id'], array( 'status' => 'active', 'field_filters' => $field_filters_approved ) ) );
			$disapproved_count = count( gravityview_get_entry_ids( $form['id'], array( 'status' => 'active', 'field_filters' => $field_filters_disapproved ) ) );
		}

		$filter_links[] = array(
			'id'            => 'gv_approved',
			'field_filters' => $field_filters_approved,
			'count'         => $approved_count,
			'label'         => esc_html__( 'Approved', 'gravityview' ),
		);

		$filter_links[] = array(
			'id'            => 'gv_disapproved',
			'field_filters' => $field_filters_disapproved,
			'count'         => $disapproved_count,
			'label'         => esc_html__( 'Disapproved', 'gravityview' ),
		);

		return $filter_links;
	}

	/**
	 * Add the GravityView Fields group tooltip
	 *
	 * @param $tooltips
	 *
	 * @return array Tooltips array with GravityView fields tooltip
	 */
	function tooltips( $tooltips ) {

		$tooltips['form_gravityview_fields'] = array(
			'title' => __('GravityView Fields', 'gravityview'),
			'value' => __( 'Allow administrators to approve or reject entries and users to opt-in or opt-out of their entries being displayed.', 'gravityview'),
		);

		return $tooltips;
	}


	/**
	 * Inject new add field buttons in the gravity form editor page
	 *
	 * @access public
	 * @param mixed $field_groups
	 * @return array Array of fields
	 */
	function add_field_buttons( $field_groups ) {

		$gravityview_fields = array(
			'name' => 'gravityview_fields',
			'label' => 'GravityView',
			'fields' => array(
				array(
					'class' => 'button',
					'value' => __( 'Approve/Reject', 'gravityview' ),
					'onclick' => "StartAddField('gravityviewapproved_admin');",
					'data-type' => 'gravityviewapproved_admin'
				),
				array(
					'class' => 'button',
					'value' => __( 'User Opt-In', 'gravityview' ),
					'onclick' => "StartAddField('gravityviewapproved');",
					'data-type' => 'gravityviewapproved'
				),
			)
		);

		array_push( $field_groups, $gravityview_fields );

		return $field_groups;
	}



	/**
	 * At edit form page, set the field Approve defaults
	 *
	 * @todo Convert to a partial include file
	 * @access public
	 * @return void
	 */
	function set_defaults() {
		?>
		case 'gravityviewapproved_admin':
			field.label = "<?php echo esc_js( __( 'Approved? (Admin-only)', 'gravityview' ) ); ?>";

			field.adminLabel = "<?php echo esc_js( __( 'Approved?', 'gravityview' ) ); ?>";
			field.adminOnly = true;

			field.choices = null;
			field.inputs = null;

			if( !field.choices ) {
				field.choices = new Array( new Choice("<?php _e( 'Approved', 'gravityview' ); ?>") );
			}

			field.inputs = new Array();
			for( var i=1; i<=field.choices.length; i++ ) {
				field.inputs.push(new Input(field.id + (i/10), field.choices[i-1].text));
			}

			field.type = 'checkbox';
			field.gravityview_approved = 1;

			break;
		case 'gravityviewapproved':
			field.label = "<?php echo esc_js( __( 'Show Entry on Website', 'gravityview' ) ); ?>";

			field.adminLabel = "<?php echo esc_js( __( 'Opt-In', 'gravityview' ) ); ?>";
			field.adminOnly = false;

			field.choices = null;
			field.inputs = null;

			if( !field.choices ) {
				field.choices = new Array(
					new Choice("<?php echo esc_js( __( 'Yes, display my entry on the website', 'gravityview' ) ); ?>")
				);
			}

			field.inputs = new Array();
			for( var i=1; i<=field.choices.length; i++ ) {
				field.inputs.push(new Input(field.id + (i/10), field.choices[i-1].text));
			}

			field.type = 'checkbox';
			field.gravityview_approved = 1;

			break;
		<?php
	}

	/**
	 * Get the Bulk Action submitted value if it is a GravityView Approve/Unapprove action
	 *
	 * @since 1.17.1
	 *
	 * @return string|false If the bulk action was GravityView Approve/Unapprove, return the full string (gvapprove-16, gvunapprove-16). Otherwise, return false.
	 */
	private function get_gv_bulk_action() {

		$gv_bulk_action = false;

		if( version_compare( GFForms::$version, '2.0', '>=' ) ) {
			$bulk_action = ( '-1' !== rgpost('action') ) ? rgpost('action') : rgpost('action2');
		} else {
			// GF 1.9.x - Bulk action 2 is the bottom bulk action select form.
			$bulk_action = rgpost('bulk_action') ? rgpost('bulk_action') : rgpost('bulk_action2');
		}

		// Check the $bulk_action value against GV actions, see if they're the same. I hate strpos().
		if( ! empty( $bulk_action ) && preg_match( '/^('. implode( '|', $this->bulk_action_prefixes ) .')/ism', $bulk_action ) ) {
			$gv_bulk_action = $bulk_action;
		}

		return $gv_bulk_action;
	}

	/**
	 * Capture bulk actions - gf_entries table
	 *
	 * @uses  GravityView_frontend::get_search_criteria() Convert the $_POST search request into a properly formatted request.
	 * @access public
	 * @return void|boolean
	 */
	public function process_bulk_action() {

		if ( ! is_admin() || ! class_exists( 'GFForms' ) || empty( $_POST ) ) {
			return false;
		}

		// The action is formatted like: gvapprove-16 or gvunapprove-16, where the first word is the name of the action and the second is the ID of the form.
		$bulk_action = $this->get_gv_bulk_action();
		
		// gforms_entry_list is the nonce that confirms we're on the right page
		// gforms_update_note is sent when bulk editing entry notes. We don't want to process then.
		if ( $bulk_action && rgpost('gforms_entry_list') && empty( $_POST['gforms_update_note'] ) ) {

			check_admin_referer( 'gforms_entry_list', 'gforms_entry_list' );

			/**
			 * The extra '-' is to make sure that there are at *least* two items in array.
			 * @see https://github.com/katzwebservices/GravityView/issues/370
			 */
			$bulk_action .= '-';

			list( $approved_status, $form_id ) = explode( '-', $bulk_action );

			if ( empty( $form_id ) ) {
				do_action( 'gravityview_log_error', '[process_bulk_action] Form ID is empty from parsing bulk action.', $bulk_action );
				return false;
			}

			// All entries are set to be updated, not just the visible ones
			if ( ! empty( $_POST['all_entries'] ) ) {

				// Convert the current entry search into GF-formatted search criteria
				$search = array(
					'search_field' => isset( $_POST['f'] ) ? $_POST['f'][0] : 0,
					'search_value' => isset( $_POST['v'][0] ) ? $_POST['v'][0] : '',
					'search_operator' => isset( $_POST['o'][0] ) ? $_POST['o'][0] : 'contains',
				);

				$search_criteria = GravityView_frontend::get_search_criteria( $search, $form_id );

				// Get all the entry IDs for the form
				$entries = gravityview_get_entry_ids( $form_id, $search_criteria );

			} else {

				// Changed from 'lead' to 'entry' in 2.0
				$entries = isset( $_POST['lead'] ) ? $_POST['lead'] : $_POST['entry'];

			}

			if ( empty( $entries ) ) {
				do_action( 'gravityview_log_error', '[process_bulk_action] Entries are empty' );
				return false;
			}

			$entry_count = count( $entries ) > 1 ? sprintf( __( '%d entries', 'gravityview' ), count( $entries ) ) : __( '1 entry', 'gravityview' );

			switch ( $approved_status ) {
				case $this->bulk_action_prefixes['approve']:
					self::update_bulk( $entries, 1, $form_id );
					$this->bulk_update_message = sprintf( __( '%s approved.', 'gravityview' ), $entry_count );
					break;

				case $this->bulk_action_prefixes['unapprove']:
					self::update_bulk( $entries, 0, $form_id );
					$this->bulk_update_message = sprintf( __( '%s disapproved.', 'gravityview' ), $entry_count );
					break;
			}
		}
	}





	/**
	 * Process a bulk of entries to update the approve field/property
	 *
	 * @access private
	 * @static
	 * @param array|boolean $entries If array, array of entry IDs that are to be updated. If true: update all entries.
	 * @param int $approved Approved status. If `0`: unapproved, if not empty, `Approved`
	 * @param int $form_id The Gravity Forms Form ID
	 * @return boolean|void
	 */
	private static function update_bulk( $entries, $approved, $form_id ) {

		if( empty($entries) || ( $entries !== true && !is_array($entries) ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ' Entries were empty or malformed.', $entries );
			return false;
		}

		if( ! GVCommon::has_cap( 'gravityview_moderate_entries' ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ' User does not have the `gravityview_moderate_entries` capability.' );
			return false;
		}

		$approved = empty( $approved ) ? 0 : 'Approved';

		// calculate approved field id once instead of looping through in the update_approved() method
		$approved_column_id = self::get_approved_column( $form_id );

		foreach( $entries as $entry_id ) {
			self::update_approved( (int)$entry_id, $approved, $form_id, $approved_column_id );
		}
	}




	/**
	 * update_approved function.
	 *
	 * @access public
	 * @static
	 * @param int $entry_id (default: 0)
	 * @param int $approved (default: 0)
	 * @param int $form_id (default: 0)
	 * @param int $approvedcolumn (default: 0)
	 * @return boolean True: It worked; False: it failed
	 */
	public static function update_approved( $entry_id = 0, $approved = 0, $form_id = 0, $approvedcolumn = 0) {

		if( !class_exists( 'GFAPI' ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . 'GFAPI does not exist' );
			return false;
		}

		if( empty( $approvedcolumn ) ) {
			$approvedcolumn = self::get_approved_column( $form_id );
		}

		//get the entry
		$entry = GFAPI::get_entry( $entry_id );

		//update entry
		$entry[ (string)$approvedcolumn ] = $approved;

		/** @var bool|WP_Error $result */
		$result = GFAPI::update_entry( $entry );

		/**
		 * GFAPI::update_entry() doesn't trigger `gform_after_update_entry`, so we trigger updating the meta ourselves.
		 */
		self::update_approved_meta( $entry_id, $approved, $form_id );

		// add note to entry
		if( $result === true ) {

			$note = empty( $approved ) ? __( 'Disapproved the Entry for GravityView', 'gravityview' ) : __( 'Approved the Entry for GravityView', 'gravityview' );

			/**
			 * @filter `gravityview/approve_entries/add-note` Add a note when the entry has been approved or disapproved?
			 * @since 1.16.3
			 * @param bool $add_note True: Yep, add that note! False: Do not, under any circumstances, add that note!
			 */
			$add_note = apply_filters( 'gravityview/approve_entries/add-note', true );

			if( $add_note && class_exists( 'GravityView_Entry_Notes' ) ) {
				$current_user = wp_get_current_user();
				GravityView_Entry_Notes::add_note( $entry_id, $current_user->ID, $current_user->display_name, $note );
			}

			/**
			 * Destroy the cache for this form
			 * @see class-cache.php
			 * @since 1.5.1
			 */
			do_action( 'gravityview_clear_form_cache', $form_id );

		} else if( is_wp_error( $result ) ) {

			do_action( 'gravityview_log_error', __METHOD__ . sprintf( ' - Entry approval not updated: %s', $result->get_error_message() ) );

			$result = false;
		}

		return $result;

	}


	/**
	 * Update the is_approved meta whenever the entry is submitted (and it contains a User Opt-in field)
	 *
	 * @since 1.16.6
	 *
	 * @param $entry array Gravity Forms entry object
	 * @param $form array Gravity Forms form object
	 */
	public function after_submission( $entry, $form ) {
		$this->after_update_entry_update_approved_meta( $form , $entry['id'] );
	}



	/**
	 * Update the is_approved meta whenever the entry is updated
	 *
	 * @since 1.7.6.1 Was previously named `update_approved_meta`
	 *
	 * @param  array $form     Gravity Forms form array
	 * @param  int $entry_id ID of the Gravity Forms entry
	 * @return void
	 */
	public function after_update_entry_update_approved_meta( $form, $entry_id = NULL ) {

		$approvedcolumn = self::get_approved_column( $form['id'] );

        /**
         * If the form doesn't contain the approve field, don't assume anything.
         */
        if( empty( $approvedcolumn ) ) {
            return;
        }

		$entry = GFAPI::get_entry( $entry_id );

		self::update_approved_meta( $entry_id, $entry[ (string)$approvedcolumn ], $form['id'] );

	}

	/**
	 * Update the `is_approved` entry meta value
	 *
	 * @since 1.7.6.1 `after_update_entry_update_approved_meta` was previously to be named `update_approved_meta`
	 * @since 1.17.1 Added $form_id parameter
	 *
	 * @param  int $entry_id ID of the Gravity Forms entry
	 * @param  string $is_approved String whether entry is approved or not. `0` for not approved, `Approved` for approved.
	 * @param int $form_id ID of the form of the entry being updated. Improves query performance.
	 *
	 * @return void
	 */
	private static function update_approved_meta( $entry_id, $is_approved, $form_id = 0 ) {

		/**
		 * Make sure that the "User Opt-in" and the Admin Approve/Reject entry set the same meta value
		 * @since 1.16.6
		 */
		$is_approved = empty( $is_approved ) ? 0 : 'Approved';

		// update entry meta
		if( function_exists('gform_update_meta') ) {

			gform_update_meta( $entry_id, 'is_approved', $is_approved, $form_id );

			/**
			 * @action `gravityview/approve_entries/updated` Triggered when an entry approval is updated
			 * @since 1.7.6.1
			 * @param  int $entry_id ID of the Gravity Forms entry
			 * @param  string $is_approved String whether entry is approved or not. `0` for not approved, `Approved` for approved.
			 */
			do_action( 'gravityview/approve_entries/updated', $entry_id, $is_approved );

			if( empty( $is_approved ) ) {

				/**
				 * @action `gravityview/approve_entries/disapproved` Triggered when an entry is rejected
				 * @since 1.7.6.1
				 * @param  int $entry_id ID of the Gravity Forms entry
				 */
				do_action( 'gravityview/approve_entries/disapproved', $entry_id );

			} else {

				/**
				 * @action `gravityview/approve_entries/approved` Triggered when an entry is approved
				 * @since 1.7.6.1
				 * @param  int $entry_id ID of the Gravity Forms entry
				 */
				do_action( 'gravityview/approve_entries/approved', $entry_id );

			}

		} else {

			do_action('gravityview_log_error', __METHOD__ . ' - `gform_update_meta` does not exist.' );

		}
	}


	/**
	 * Approve/Disapprove entries using the × or ✓ icons in the GF Entries screen
	 * @return void
	 */
	public function ajax_update_approved() {

		if( empty( $_POST['entry_id'] ) || empty( $_POST['form_id'] ) ) {

			do_action( 'gravityview_log_error', __METHOD__ . ' entry_id or form_id are empty.', $_POST );

			$result = false;
		}

		else if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxgfentries' ) ) {

			do_action( 'gravityview_log_error', __METHOD__ . ' Security check failed.', $_POST );

			$result = false;
		}

		else if( ! GVCommon::has_cap( 'gravityview_moderate_entries', $_POST['entry_id'] ) ) {

			do_action( 'gravityview_log_error', __METHOD__ . ' User does not have the `gravityview_moderate_entries` capability.' );

			$result = false;
		}

		else {

			$result = self::update_approved( $_POST['entry_id'], $_POST['approved'], $_POST['form_id'] );

			if( is_wp_error( $result ) ) {
				/** @var WP_Error $result */
				do_action( 'gravityview_log_error', __METHOD__ .' Error updating approval: ' . $result->get_error_message() );
				$result = false;
			}

		}

		exit( $result );
	}


	/**
	 * Calculate the approve field.input id
	 *
	 * @access public
	 * @static
	 * @param mixed $form GF Form or Form ID
	 * @return false|null|string Returns the input ID of the approved field. Returns NULL if no approved fields were found. Returns false if $form_id wasn't set.
	 */
	static public function get_approved_column( $form ) {

        if( empty( $form ) ) {
            return null;
        }

        if( !is_array( $form ) ) {
            $form = GVCommon::get_form( $form );
        }

		foreach( $form['fields'] as $key => $field ) {

            $field = (array) $field;

			if( !empty( $field['gravityview_approved'] ) ) {
				if( !empty($field['inputs'][0]['id']) ) {
					return $field['inputs'][0]['id'];
				}
			}

            // Note: This is just for backward compatibility from GF Directory plugin and old GV versions - when using i18n it may not work..
            if( 'checkbox' == $field['type'] && isset( $field['inputs'] ) && is_array( $field['inputs'] ) ) {
                foreach ( $field['inputs'] as $key2 => $input ) {
                    if ( strtolower( $input['label'] ) == 'approved' ) {
                        return $input['id'];
                    }
                }
            }
		}

		return null;
	}

	/**
	 * Add a hidden input that is used in the Javascript to show approved/disapproved entries checkbox
	 *
	 * See the /assets/js/admin-entries-list.js setInitialApprovedEntries method
	 *
	 * @param $form_id
	 * @param $field_id
	 * @param $value
	 * @param $entry
	 * @param $query_string
	 *
	 * @return void
	 */
	static public function add_entry_approved_hidden_input(  $form_id, $field_id, $value, $entry, $query_string ) {

		if( ! GVCommon::has_cap( 'gravityview_moderate_entries', $entry['id'] ) ) {
			return;
		}

		if( empty( $entry['id'] ) ) {
			return;
		}

		if( gform_get_meta( $entry['id'], 'is_approved' ) ) {
			echo '<input type="hidden" class="entry_approved" id="entry_approved_'. $entry['id'] .'" value="true" />';
		}
	}

	/**
	 * Get the form ID of the form currently being displayed
	 *
	 * @since 1.17.1
	 *
	 * @return int ID of the current form being displayed. `0` is returned if no forms are found.
	 */
	private function get_form_id() {

		$form_id = GFForms::get('id');

		// If there are no forms identified, use the first form. That's how GF does it.
		if( empty( $form_id ) && class_exists('RGFormsModel') ) {
			$form_id = $this->get_first_form_id();
		}

		return absint( $form_id );
	}

	/**
	 * Get the first form ID from Gravity Forms, sorted in the same order as in the All Forms page
	 *
	 * @see GFEntryList::all_entries_page() This method is based on the form-selecting code here
	 *
	 * @since 1.17.2
	 *
	 * @return int ID of the first form, sorted by title. `0` if no forms were found.
	 */
	private function get_first_form_id() {

		$forms = RGFormsModel::get_forms( null, 'title' );

		if( ! isset( $forms[0] ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ': No forms were found' );
			return 0;
		}

		$first_form = $forms[0];

		$form_id = is_object( $forms[0] ) ? $first_form->id : $first_form['id'];

		return intval( $form_id );
	}


	function add_scripts_and_styles( $hook ) {

		if( ! class_exists( 'RGForms' ) ) {

			do_action( 'gravityview_log_error', 'GravityView_Admin_ApproveEntries[add_scripts_and_styles] RGForms does not exist.' );

			return;
		}

		// enqueue styles & scripts gf_entries
		// But only if we're on the main Entries page, not on reports pages
		if( GFForms::get_page() !== 'entry_list' ) {
			return;
		}

		$form_id = $this->get_form_id();

		// Things are broken; no forms were found
		if( empty( $form_id ) ) {
			return;
		}

		wp_enqueue_style( 'gravityview_entries_list', plugins_url('assets/css/admin-entries-list.css', GRAVITYVIEW_FILE), array(), GravityView_Plugin::version );

		$script_debug = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

		wp_enqueue_script( 'gravityview_gf_entries_scripts', plugins_url('assets/js/admin-entries-list'.$script_debug.'.js', GRAVITYVIEW_FILE), array( 'jquery' ), GravityView_Plugin::version );

		wp_localize_script( 'gravityview_gf_entries_scripts', 'gvGlobals', array(
			'nonce' => wp_create_nonce( 'gravityview_ajaxgfentries'),
			'form_id' => $form_id,
			'show_column' => (int)$this->show_approve_entry_column( $form_id ),
			'add_bulk_action' => (int)GVCommon::has_cap( 'gravityview_moderate_entries' ),
			'bulk_actions' => $this->get_bulk_actions( $form_id ),
			'bulk_message' => $this->bulk_update_message,
			'approve_title' => __( 'Entry not approved for directory viewing. Click to approve this entry.', 'gravityview'),
			'unapprove_title' => __( 'Entry approved for directory viewing. Click to disapprove this entry.', 'gravityview'),
			'column_title' => __( 'Show entry in directory view?', 'gravityview'),
			'column_link' => esc_url( add_query_arg( array('sort' => self::get_approved_column( $form_id ) ) ) ),
		) );

	}

	/**
	 * Get an array of options to be added to the Gravity Forms "Bulk action" dropdown in a "GravityView" option group
	 *
	 * @since 1.16.3
	 *
	 * @param int $form_id  ID of the form currently being displayed
	 *
	 * @return array Array of actions to be added to the GravityView option group
	 */
	private function get_bulk_actions( $form_id ) {

		$bulk_actions = array(
			'GravityView' => array(
				array(
					'label' => __( 'Approve', 'gravityview' ),
					'value' => sprintf( '%s-%d', $this->bulk_action_prefixes['approve'], $form_id ),
				),
				array(
					'label' => __( 'Disapprove', 'gravityview' ),
					'value' => sprintf( '%s-%d', $this->bulk_action_prefixes['unapprove'], $form_id ),
				),
			),
		);

		/**
		 * @filter `gravityview/approve_entries/bulk_actions` Modify the GravityView "Bulk action" dropdown list. Return an empty array to hide.
		 * @see https://gist.github.com/zackkatz/82785402c996b51b4dc9 for an example of how to use this filter
		 * @since 1.16.3
		 * @param array $bulk_actions Associative array of actions to be added to "Bulk action" dropdown inside GravityView `<optgroup>`. Parent array key is the `<optgroup>` label, then each child array must have `label` (displayed text) and `value` (input value) keys
		 * @param int $form_id ID of the form currently being displayed
		 */
		$bulk_actions = apply_filters( 'gravityview/approve_entries/bulk_actions', $bulk_actions, $form_id );

		// Sanitize the values, just to be sure.
		foreach ( $bulk_actions as $key => $group ) {
			foreach ( $group as $i => $action ) {
				$bulk_actions[ $key ][ $i ]['label'] = esc_html( $bulk_actions[ $key ][ $i ]['label'] );
				$bulk_actions[ $key ][ $i ]['value'] = esc_attr( $bulk_actions[ $key ][ $i ]['value'] );
			}
		}

		return $bulk_actions;
	}

	/**
	 * Should the Approve/Reject Entry column be shown in the GF Entries page?
	 *
	 * @since 1.7.2
	 *
	 * @param int $form_id The ID of the Gravity Forms form for which entries are being shown
	 *
	 * @return bool True: Show column; False: hide column
	 */
	private function show_approve_entry_column( $form_id ) {

		$show_approve_column = GVCommon::has_cap( 'gravityview_moderate_entries' );

		/**
		 * @filter `gravityview/approve_entries/hide-if-no-connections` Return true to hide reject/approve if there are no connected Views
		 * @since 1.7.2
		 * @param boolean $hide_if_no_connections
		 */
		$hide_if_no_connections = apply_filters('gravityview/approve_entries/hide-if-no-connections', false );

		if( $hide_if_no_connections ) {

			$connected_views = gravityview_get_connected_views( $form_id );

			if( empty( $connected_views ) ) {
				$show_approve_column = false;
			}
		}

		/**
		 * @filter `gravityview/approve_entries/show-column` Override whether the column is shown
		 * @param boolean $show_approve_column Whether the column will be shown
		 * @param int $form_id The ID of the Gravity Forms form for which entries are being shown
		 */
		$show_approve_column = apply_filters('gravityview/approve_entries/show-column', $show_approve_column, $form_id );

		return $show_approve_column;
	}

	function register_gform_noconflict_script( $scripts ) {
		$scripts[] = 'gravityview_gf_entries_scripts';
		return $scripts;
	}

	function register_gform_noconflict_style( $styles ) {
		$styles[] = 'gravityview_entries_list';
		return $styles;
	}

}

new GravityView_Admin_ApproveEntries;
