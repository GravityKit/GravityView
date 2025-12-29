<?php
/**
 * @file class-admin-approve-entries.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
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
		'approve'    => 'gvapprove',
		'disapprove' => 'gvdisapprove',
		'unapprove'  => 'gvunapprove',
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

		// add hidden field with approve status
		add_action( 'gform_entries_first_column_actions', array( $this, 'add_entry_approved_hidden_input' ), 1, 5 );

		add_filter( 'gravityview/metaboxes/tooltips', array( $this, 'tooltips' ) );

		// adding styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_styles' ) );
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
	 * @param bool  $include_counts Whether to include counts in the output
	 *
	 * @return array Filter links, with GravityView approved/disapproved links added
	 */
	public function filter_links_entry_list( $filter_links = array(), $form = array(), $include_counts = true ) {

		/**
		 * Disable filter links.
		 *
		 * @since 1.17.1
		 *
		 * @param bool  $show_filter_links True: show the "approved"/"disapproved" filter links. False: hide them.
		 * @param array $form              GF Form object of current form.
		 */
		$show_filter_links = apply_filters( 'gravityview/approve_entries/show_filter_links_entry_list', true, $form );

		if ( false === $show_filter_links ) {
			return $filter_links;
		}

		$field_filters_approved = array(
			array(
				'key'   => GravityView_Entry_Approval::meta_key,
				'value' => GravityView_Entry_Approval_Status::APPROVED,
			),
		);

		$field_filters_disapproved = array(
			array(
				'key'   => GravityView_Entry_Approval::meta_key,
				'value' => GravityView_Entry_Approval_Status::DISAPPROVED,
			),
		);

		// Field filters do not allow for complex logic, so we need to use a custom SQL query for unapproved entries where:
		// 1. 'is_approved' meta key = 3 [OR] 'is_approved' meta key = ''
		// [AND]
		// 2. 'partial_entry_percent' meta key = '' [OR] 'partial_entry_percent' meta key = NULL
		$field_filters_unapproved = array(
			'mode' => 'any',
			array(
				'key'   => GravityView_Entry_Approval::meta_key,
				'value' => '__filter_unapproved'
			)
		);

		add_filter( 'gform_gf_query_sql', function ( $sql ) use ( $form ) {
			$entry_meta_table = GFFormsModel::get_entry_meta_table_name();

			// Detect the placeholder that indicates we should use the custom SQL query.
			if ( false === strpos( $sql['where'], '__filter_unapproved' ) ) {
				return $sql;
			}

			$form_id           = (int) $form['id'];
			$unapproved_status = (int) GravityView_Entry_Approval_Status::UNAPPROVED;

			$additional_joins = "
				LEFT JOIN `{$entry_meta_table}` AS `gv_approval`
				ON (`gv_approval`.`entry_id` = `t1`.`id` AND `gv_approval`.`meta_key` = 'is_approved')
				LEFT JOIN `{$entry_meta_table}` AS `gv_partial`
				ON (`gv_partial`.`entry_id` = `t1`.`id` AND `gv_partial`.`meta_key` = 'partial_entry_percent')
			";

			// Append our JOINs to the existing ones. The existing JOINs (like o2 for sorting) will remain intact.
			$sql['join'] = $sql['join'] . $additional_joins;

			// And now we add the custom SQL for the unapproved status being set to 3 or empty.
			// The existing WHERE clause is overwritten, but it wasn't complex.
			// We are replacing `AND (`m3`.`meta_key` = 'is_approved' AND `m3`.`meta_value` = '__filter_unapproved')`.
			$sql['where'] = "
				WHERE (
				`t1`.`form_id` IN ('{$form_id}')
				AND (
					`t1`.`status` = 'active'
					AND (
						`gv_approval`.`meta_value` IS NULL
						OR `gv_approval`.`meta_value` = '{$unapproved_status}'
						OR `gv_approval`.`meta_value` = ''
					)
					AND (
						`gv_partial`.`meta_value` IS NULL
						OR `gv_partial`.`meta_value` = ''
					)
				)
			)
			";

			return $sql;
		} );

		$approved_count = $disapproved_count = $unapproved_count = 0;

		// Only count if necessary
		if ( $include_counts ) {
			$approved_count    = count(
				gravityview_get_entry_ids(
					$form['id'],
					array(
						'status'        => 'active',
						'field_filters' => $field_filters_approved,
					)
				)
			);
			$disapproved_count = count(
				gravityview_get_entry_ids(
					$form['id'],
					array(
						'status'        => 'active',
						'field_filters' => $field_filters_disapproved,
					)
				)
			);
			$unapproved_count  = count(
				gravityview_get_entry_ids(
					$form['id'],
					array(
						'status'        => 'active',
						'field_filters' => $field_filters_unapproved,
					)
				)
			);
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

		$filter_links[] = array(
			'id'            => 'gv_unapproved',
			'field_filters' => $field_filters_unapproved,
			'count'         => $unapproved_count,
			'label'         => GravityView_Entry_Approval_Status::get_label( GravityView_Entry_Approval_Status::UNAPPROVED ),
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
			'title' => __( 'GravityView Fields', 'gk-gravityview' ),
			'value' => __( 'Allow administrators to approve or reject entries and users to opt-in or opt-out of their entries being displayed.', 'gk-gravityview' ),
		);

		return $tooltips;
	}


	/**
	 * Inject new add field buttons in the gravity form editor page
	 *
	 * @param mixed $field_groups
	 * @return array Array of fields
	 */
	function add_field_buttons( $field_groups ) {

		$gravityview_fields = array(
			'name'   => 'gravityview_fields',
			'label'  => 'GravityView',
			'fields' => array(
				array(
					'class'     => 'button',
					'value'     => __( 'Approve/Reject', 'gk-gravityview' ),
					'onclick'   => "StartAddField('gravityviewapproved_admin');",
					'data-type' => 'gravityviewapproved_admin',
					'data-icon' => 'dashicons-yes-alt',
				),
				array(
					'class'     => 'button',
					'value'     => __( 'User Opt-In', 'gk-gravityview' ),
					'onclick'   => "StartAddField('gravityviewapproved');",
					'data-type' => 'gravityviewapproved',
					'data-icon' => 'dashicons-media-text',
				),
			),
		);

		array_push( $field_groups, $gravityview_fields );

		return $field_groups;
	}



	/**
	 * At edit form page, set the field Approve defaults
	 *
	 * @todo Convert to a partial include file
	 * @return void
	 */
	function set_defaults() {
		?>
		case 'gravityviewapproved_admin':
			field.label = "<?php echo esc_js( __( 'Approved? (Admin-only)', 'gk-gravityview' ) ); ?>";

			field.adminLabel = "<?php echo esc_js( __( 'Approved?', 'gk-gravityview' ) ); ?>";
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
			field.label = "<?php echo esc_js( __( 'Show Entry on Website', 'gk-gravityview' ) ); ?>";

			field.adminLabel = "<?php echo esc_js( __( 'Opt-In', 'gk-gravityview' ) ); ?>";
			field.adminOnly = false;

			field.choices = null;
			field.inputs = null;

			if( !field.choices ) {
				field.choices = new Array(
					new Choice("<?php echo esc_js( __( 'Yes, display my entry on the website', 'gk-gravityview' ) ); ?>")
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
	public static function update_approved( $entry_id = 0, $approved = 0, $form_id = 0, $approvedcolumn = 0 ) {
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
	public static function get_approved_column( $form ) {
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
	public static function add_entry_approved_hidden_input( $form_id, $field_id, $value, $entry, $query_string ) {

		if ( ! GVCommon::has_cap( 'gravityview_moderate_entries', $entry['id'] ) ) {
			return;
		}

		if ( empty( $entry['id'] ) ) {
			return;
		}

		$status_value = GravityView_Entry_Approval::get_entry_status( $entry, 'value' );

		if ( $status_value ) {
			echo '<input type="hidden" class="entry_approval" id="entry_approved_' . $entry['id'] . '" value="' . esc_attr( $status_value ) . '" />';
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

		$form_id = GFForms::get( 'id' );

		// If there are no forms identified, use the first form. That's how GF does it.
		if ( empty( $form_id ) && class_exists( 'RGFormsModel' ) ) {
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

		if ( ! isset( $forms[0] ) ) {
			gravityview()->log->error( 'No forms were found' );
			return 0;
		}

		$first_form = $forms[0];

		$form_id = is_object( $forms[0] ) ? $first_form->id : $first_form['id'];

		return intval( $form_id );
	}


	function add_scripts_and_styles( $hook ) {

		if ( ! class_exists( 'GFForms' ) ) {
			gravityview()->log->error( 'GFForms does not exist.' );
			return;
		}

		// enqueue styles & scripts gf_entries
		// But only if we're on the main Entries page, not on reports pages
		if ( 'entry_list' !== GFForms::get_page() ) {
			return;
		}

		$form_id = $this->get_form_id();

		// Things are broken; no forms were found
		if ( empty( $form_id ) ) {
			return;
		}

		wp_enqueue_style( 'gravityview_entries_list', plugins_url( 'assets/css/admin-entries-list.css', GRAVITYVIEW_FILE ), array(), GV_PLUGIN_VERSION );

		$script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'gravityview_gf_entries_scripts', plugins_url( 'assets/js/admin-entries-list' . $script_debug . '.js', GRAVITYVIEW_FILE ), array( 'jquery' ), GV_PLUGIN_VERSION );

		wp_enqueue_script( 'gravityview_entries_list-popper', plugins_url( 'assets/lib/tippy/popper.min.js', GRAVITYVIEW_FILE ), array(), GV_PLUGIN_VERSION );
		wp_enqueue_script( 'gravityview_entries_list-tippy', plugins_url( 'assets/lib/tippy/tippy.min.js', GRAVITYVIEW_FILE ), array(), GV_PLUGIN_VERSION );
		wp_enqueue_style( 'gravityview_entries_list-tippy', plugins_url( 'assets/lib/tippy/tippy.css', GRAVITYVIEW_FILE ), array(), GV_PLUGIN_VERSION );

		wp_localize_script(
			'gravityview_gf_entries_scripts',
			'gvGlobals',
			array(
				'nonce'                    => wp_create_nonce( 'gravityview_entry_approval' ),
				'admin_nonce'              => wp_create_nonce( 'gravityview_admin_entry_approval' ),
				'form_id'                  => $form_id,
				'show_column'              => (int) $this->show_approve_entry_column( $form_id ),
				'add_bulk_action'          => (int) GVCommon::has_cap( 'gravityview_moderate_entries' ),
				'status_approved'          => GravityView_Entry_Approval_Status::APPROVED,
				'status_disapproved'       => GravityView_Entry_Approval_Status::DISAPPROVED,
				'status_unapproved'        => GravityView_Entry_Approval_Status::UNAPPROVED,
				'bulk_actions'             => GravityView_Bulk_Actions::get_bulk_actions( $form_id ),
				'bulk_message'             => $this->bulk_update_message,
				'unapprove_title'          => GravityView_Entry_Approval_Status::get_title_attr( 'unapproved' ),
				'approve_title'            => GravityView_Entry_Approval_Status::get_title_attr( 'disapproved' ),
				'disapprove_title'         => GravityView_Entry_Approval_Status::get_title_attr( 'approved' ),
				'column_title'             => esc_html__( 'GravityView entry approval status', 'gk-gravityview' ),
				'column_link'              => esc_url( $this->get_sort_link() ),
				'status_popover_template'  => GravityView_Entry_Approval::get_popover_template(),
				'status_popover_placement' => GravityView_Entry_Approval::get_popover_placement(),
			)
		);
	}

	/**
	 * Generate a link to sort by approval status
	 *
	 * Note: Sorting by approval will never be great because it's not possible currently to declare the sorting as
	 * numeric, but it does group the approved entries together.
	 *
	 * @since 2.0.14 Remove need for approval field for sorting by approval status
	 *
	 * @param int $form_id [NO LONGER USED]
	 *
	 * @return string Sorting link
	 */
	private function get_sort_link( $form_id = 0 ) {

		$args = array(
			'orderby' => 'is_approved',
			'order'   => ( 'desc' === \GV\Utils::_GET( 'order' ) ) ? 'asc' : 'desc',
		);

		return add_query_arg( $args );
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
		 * Return true to hide reject/approve if there are no connected Views.
		 *
		 * @since 1.7.2
		 *
		 * @param bool $hide_if_no_connections Whether to hide the approval column when no Views are connected.
		 */
		$hide_if_no_connections = apply_filters( 'gravityview/approve_entries/hide-if-no-connections', false );

		if ( $hide_if_no_connections ) {

			$connected_views = gravityview_get_connected_views( $form_id, array( 'posts_per_page' => 1 ), false );

			if ( empty( $connected_views ) ) {
				$show_approve_column = false;
			}
		}

		/**
		 * Override whether the column is shown.
		 *
		 * @since 1.7.2
		 *
		 * @param bool $show_approve_column Whether the column will be shown.
		 * @param int  $form_id             The ID of the Gravity Forms form for which entries are being shown.
		 */
		$show_approve_column = apply_filters( 'gravityview/approve_entries/show-column', $show_approve_column, $form_id );

		return $show_approve_column;
	}

	function register_gform_noconflict_script( $scripts ) {
		$scripts[] = 'gravityview_gf_entries_scripts';
		$scripts[] = 'gravityview_entries_list-popper';
		$scripts[] = 'gravityview_entries_list-tippy';
		return $scripts;
	}

	function register_gform_noconflict_style( $styles ) {
		$styles[] = 'gravityview_entries_list';
		$styles[] = 'gravityview_entries_list-tippy';
		return $styles;
	}
}

new GravityView_Admin_ApproveEntries();
