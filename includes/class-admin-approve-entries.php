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
		'disapprove' => 'gvdisapprove',
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
				'key' => GravityView_Entry_Approval::meta_key,
				'value' => GravityView_Entry_Approval_Status::APPROVED
			),
		);

		$field_filters_disapproved = array(
			array(
				'key'      => GravityView_Entry_Approval::meta_key,
				'value'    => GravityView_Entry_Approval_Status::DISAPPROVED,
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
			'label'         => GravityView_Entry_Approval_Status::get_label( GravityView_Entry_Approval_Status::APPROVED ),
		);

		$filter_links[] = array(
			'id'            => 'gv_disapproved',
			'field_filters' => $field_filters_disapproved,
			'count'         => $disapproved_count,
			'label'         => GravityView_Entry_Approval_Status::get_label( GravityView_Entry_Approval_Status::DISAPPROVED ),
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
				field.choices = new Array( new Choice("<?php echo esc_js( GravityView_Entry_Approval_Status::get_label( GravityView_Entry_Approval_Status::APPROVED ) ); ?>") );
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
					GravityView_Entry_Approval::update_bulk( $entries, GravityView_Entry_Approval_Status::APPROVED, $form_id );
					$this->bulk_update_message = sprintf( __( '%s approved.', 'gravityview' ), $entry_count );
					break;
				case $this->bulk_action_prefixes['unapprove']:
					GravityView_Entry_Approval::update_bulk( $entries, GravityView_Entry_Approval_Status::UNAPPROVED, $form_id );
					$this->bulk_update_message = sprintf( __( '%s unapproved.', 'gravityview' ), $entry_count );
					break;
				case $this->bulk_action_prefixes['disapprove']:
					GravityView_Entry_Approval::update_bulk( $entries, GravityView_Entry_Approval_Status::DISAPPROVED, $form_id );
					$this->bulk_update_message = sprintf( __( '%s disapproved.', 'gravityview' ), $entry_count );
					break;
			}
		}
	}


	/**
	 * update_approved function.
	 *
     * @since 1.18 Moved to GravityView_Entry_Approval::update_approved
	 * @see GravityView_Entry_Approval::update_approved
     *
	 * @param int $entry_id (default: 0)
	 * @param int $approved (default: 0)
	 * @param int $form_id (default: 0)
	 * @param int $approvedcolumn (default: 0)
     *
	 * @return boolean True: It worked; False: it failed
	 */
	public static function update_approved( $entry_id = 0, $approved = 0, $form_id = 0, $approvedcolumn = 0) {
		return GravityView_Entry_Approval::update_approved( $entry_id, $approved, $form_id, $approvedcolumn );
	}

	/**
	 * Calculate the approve field.input id
	 *
     * @since 1.18 Moved to GravityView_Entry_Approval::get_approved_column
     * @see GravityView_Entry_Approval::get_approved_column
     *
	 * @param mixed $form GF Form or Form ID
	 * @return false|null|string Returns the input ID of the approved field. Returns NULL if no approved fields were found. Returns false if $form_id wasn't set.
	 */
	static public function get_approved_column( $form ) {
		return GravityView_Entry_Approval::get_approved_column( $form );
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

		$status_value = GravityView_Entry_Approval::get_entry_status( $entry, 'value' );

		if( $status_value ) {
			echo '<input type="hidden" class="entry_approval" id="entry_approved_'. $entry['id'] .'" value="' . esc_attr( $status_value ) . '" />';
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

		if( ! class_exists( 'GFForms' ) ) {

			do_action( 'gravityview_log_error', 'GravityView_Admin_ApproveEntries[add_scripts_and_styles] GFForms does not exist.' );

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
			'nonce' => wp_create_nonce( 'gravityview_entry_approval'),
			'admin_nonce' => wp_create_nonce( 'gravityview_admin_entry_approval'),
			'form_id' => $form_id,
			'show_column' => (int)$this->show_approve_entry_column( $form_id ),
			'add_bulk_action' => (int)GVCommon::has_cap( 'gravityview_moderate_entries' ),
			'status_approved' => GravityView_Entry_Approval_Status::APPROVED,
			'status_disapproved' => GravityView_Entry_Approval_Status::DISAPPROVED,
			'status_unapproved' => GravityView_Entry_Approval_Status::UNAPPROVED,
			'bulk_actions' => $this->get_bulk_actions( $form_id ),
			'bulk_message' => $this->bulk_update_message,
			'unapprove_title' => GravityView_Entry_Approval_Status::get_title_attr('unapproved'),
            'approve_title' => GravityView_Entry_Approval_Status::get_title_attr('disapproved'),
			'disapprove_title' => GravityView_Entry_Approval_Status::get_title_attr('approved'),
			'column_title' => __( 'Show entry in directory view?', 'gravityview'),
			'column_link' => esc_url( $this->get_sort_link( $form_id ) ),
		) );

	}

	/**
     * Generate a link to sort by approval status (if there is an Approve/Disapprove field)
     *
     * Note: Sorting by approval will never be great because it's not possible currently to declare the sorting as
     * numeric, but it does group the approved entries together.
     *
	 * @param int $form_id
	 *
	 * @return string Sorting link
	 */
	private function get_sort_link( $form_id = 0 ) {

		$approved_column_id = self::get_approved_column( $form_id );

		if( ! $approved_column_id ) {
		    return '';
        }

	    $order = ( 'desc' === rgget('order') ) ? 'asc' : 'desc';

	    $args = array(
		    'orderby' => $approved_column_id,
            'order' => $order,
        );

	    $link = add_query_arg( $args );

		return $link;
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
					'label' => GravityView_Entry_Approval_Status::get_string('approved', 'action'),
					'value' => sprintf( '%s-%d', $this->bulk_action_prefixes['approve'], $form_id ),
				),
				array(
					'label' => GravityView_Entry_Approval_Status::get_string('disapproved', 'action'),
					'value' => sprintf( '%s-%d', $this->bulk_action_prefixes['disapprove'], $form_id ),
				),
				array(
					'label' => GravityView_Entry_Approval_Status::get_string('unapproved', 'action'),
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
