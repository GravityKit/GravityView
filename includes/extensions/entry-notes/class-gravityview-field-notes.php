<?php

/**
 * Add Entry Notes
 */
class GravityView_Field_Notes extends GravityView_Field {

	/**
	 * @var string
	 */
	static $file;

	/**
	 * @var string
	 */
	static $path;

	/**
	 * @var bool Are we doing an AJAX request?
	 */
	private $doing_ajax = false;

	var $name = 'notes';

	function __construct() {

		self::$path = plugin_dir_path( __FILE__ );
		self::$file = __FILE__;

		$this->doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

		$this->add_hooks();

		parent::__construct();
	}

	private function add_hooks() {

		add_shortcode( 'gv_note_add', array( 'GravityView_Field_Notes', 'get_add_note_part' ) );

		add_action( 'wp', array( $this, 'maybe_delete_notes'), 1000 );
		add_action( 'wp_ajax_nopriv_gv_delete_notes', array( $this, 'maybe_delete_notes') );
		add_action( 'wp_ajax_gv_delete_notes', array( $this, 'maybe_delete_notes') );

		add_action( 'wp', array( $this, 'maybe_add_note'), 1000 );
		add_action( 'wp_ajax_nopriv_gv_note_add', array( $this, 'maybe_add_note') );
		add_action( 'wp_ajax_gv_note_add', array( $this, 'maybe_add_note') );

		// add template path to check for field
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );
	}

	/**
	 * Verify permissions, check if $_POST is set and as expected. If so, use process_add_note
	 *
	 * @since 1.17
	 *
	 * @see process_add_note
	 *
	 * @return void
	 */
	function maybe_add_note() {
		if( ! GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
			return;
		}

		if( isset( $_POST['action'] ) && 'gv_note_add' === $_POST['action'] ) {

			if( $this->doing_ajax ) {
				parse_str( wp_unslash( $_POST['data'] ), $data );
			} else {
				$data = $_POST;
			}

			$this->process_add_note( (array) $data );
		}
	}

	/**
	 * Handle adding a note.
	 *
	 * Verify the request. If valid, add the note. If AJAX request, send response JSON.
	 *
	 * @since 1.17
	 *
	 * @var array $data {
	 *  @type string $action "gv_note_add"
	 *  @type string $entry-slug Entry slug or ID to add note to
	 *  @type string $gv_note_add Nonce with action "gv_note_add_{entry slug}" and name "gv_note_add"
	 *  @type string $_wp_http_referer Relative URL to submitting page ('/view/example/entry/123/')
	 *  @type string $note-content Note content
	 *  @type string $add_note Submit button value ('Add Note')
	 * }
	 *
	 * @return void
	 */
	function process_add_note( $data ) {

		$valid = wp_verify_nonce( $data['gv_note_add'], 'gv_note_add_' . $data['entry-slug'] );

		if( $valid ) {
			$entry = gravityview_get_entry( $data['entry-slug'], false );

			$added = $this->add_note( $entry, $data );

			if( $this->doing_ajax ) {
				if ( is_wp_error( $added ) ) {
					wp_send_json_error( array( 'error' => $added->get_error_message() ) );
				} else {

					$note = GravityView_Entry_Notes::get_note( $added );

					if( $note ) {
						$html = self::display_note( $note, true );
						wp_send_json_success( array( 'html' => $html ) );
					} else {
						wp_send_json_error( array( 'error' => esc_html__( 'There was an error adding the note.', 'gravityview' ) ) );
					}
				}
			}
		} else {
			wp_send_json_error( array( 'error' => esc_html__( 'The request was invalid. Refresh the page and try again.', 'gravityview' ) ) );
		}
	}

	/**
	 * Possibly delete notes, if request is proper.
	 *
	 * Verify permissions. Check expected $_POST. Parse args, then send to process_delete_notes
	 *
  	 * @since 1.17
	 *
	 * @see process_delete_notes
	 *
	 * @return void
	 */
	function maybe_delete_notes() {

		if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
			return;
		}

		if ( isset( $_POST['action'] ) && 'gv_delete_notes' === $_POST['action'] ) {

			if ( $this->doing_ajax ) {
				parse_str( wp_unslash( $_POST['data'] ), $data );
			} else {
				$data = $_POST;
			}

			$required_args = array(
				'gv_delete_notes' => '',
				'entry-slug' => ''
			);

			$data = wp_parse_args( $data, $required_args );

			$this->process_delete_notes( $data );
		}
	}

	/**
	 * Handle deleting notes
	 *
	 * @var array $data {
	 *  @type string $action "gv_delete_notes"
	 *  @type string $entry-slug Entry slug or ID to add note to
	 *  @type string $gv_delete_notes Nonce with action "gv_delete_notes_{entry slug}" and name "gv_delete_notes"
	 *  @type string $_wp_http_referer Relative URL to submitting page ('/view/example/entry/123/')
	 *  @type string $bulk_action Value from action dropdown ("delete")
	 *  @type int[]  $note  Array of Note IDs to be deleted
	 * }
	 *
	 * @return void
	 */
	function process_delete_notes( $data ) {

		$valid = wp_verify_nonce( $data['gv_delete_notes'], 'gv_delete_notes_' . $data['entry-slug'] );
			GravityView_Entry_Notes::delete_notes( $data['note'] );

		if ( $valid ) {
			if( $this->doing_ajax ) {
				wp_send_json_success();
			}
		} elseif( $this->doing_ajax ) {
			wp_send_json_error( array( 'error' => esc_html__( 'The request was invalid. Refresh the page and try again.', 'gravityview' ) ) );
		}
	}

	/**
	 * Include this extension templates path
	 *
	 * @since 1.17
	 *
	 * @param array $file_paths List of template paths ordered
	 *
	 * @return array File paths with `./` and `./partials/` paths added
	 */
	public function add_template_path( $file_paths ) {

		$file_paths[ 172 ] = self::$path;
		$file_paths[ 173 ] = self::$path . 'partials/';

		return $file_paths;
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset( $field_options['show_as_link'] );

		$field_options['notes_is_editable'] = array(
			'type' => 'checkbox',
			'label' => __( 'Allow adding and deleting notes?', 'gravityview' ),
			'value' => false,
		);

		// TODO: Add setting to just show Add form

		$field_options['note_text_add_note'] = array(
			'type' => 'text',
			'label' => "Add Note button text",
			'value' => __('Add Note', 'gravityview'),
		);

		return $field_options;
	}

	/**
	 * Delete notes if user has permissisons.
	 *
	 * @uses RGFormsModel::delete_notes
	 *
	 * @since 1.17
	 *
	 * @param int[] $notes
	 */
	function delete_notes( $notes = array() ) {
	static public function strings() {

		$strings = array(
			'bulk-action' => __( 'Bulk action', 'gravityview' ),
			'delete' => __( 'Delete', 'gravityview' ),
			'bulk-action-button' => __( 'Apply', 'gravityview' ),
			'caption' => __( 'Notes for this entry', 'gravityview' ),
			'toggle-notes' => __( 'Toggle all notes', 'gravityview' ),
			'note-content-column' => __( 'Note Content', 'gravityview' ),
			'no-notes' => __( 'There are no notes.', 'gravityview' ),
			'processing' => __( 'Processing&hellip;', 'gravityview' ),
		);

		if ( ! GVCommon::has_cap( 'gravityforms_edit_entry_notes' ) ) {
			wp_die( esc_html__( "You don't have adequate permission to delete notes.", 'gravityview' ) );
		}
		/**
		 * @filter `gravityview/field/notes/strings` Modify the text used in the Entry Notes field. Sanitized by `esc_html` after return.
		 * @since 1.17
		 * @param array $strings Text in key => value pairs
		 */
		$strings = gv_map_deep( apply_filters( 'gravityview/field/notes/strings', $strings ), 'esc_html' );

		RGFormsModel::delete_notes( $notes );
		return $strings;
	}

	static public function display_note( $note, $is_editable = false ) {

		$note_content = array(
			'avatar'                 => apply_filters( 'gravityview/field/notes/avatar', get_avatar( $note->user_id, 48 ), $note ),
			'user_name'              => $note->user_name,
			'user_email'             => $note->user_email,
			'added_on'               => esc_html__( 'added on {date_created_formatted}', 'gravityview' ),
			'value'                  => nl2br( esc_html( $note->value ) ),
			'date_created'           => $note->date_created,
			'date_created_formatted' => GFCommon::format_date( $note->date_created, false ),
			'user_id'                => intval( $note->user_id ),
			'note_type'              => $note->note_type,
			'id'                     => intval( $note->id ),
		);

		ob_start();
		GravityView_View::getInstance()->get_template_part( 'note', 'detail' );
		$note_detail_html = ob_get_clean();

		foreach ( $note_content as $tag => $value ) {
			$note_detail_html = str_replace( '{' . $tag . '}', $value, $note_detail_html );
		}

		$note_row_template = 'row';
		if ( $is_editable && GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
			$note_row_template = 'row-editable';
		}

		ob_start();
		GravityView_View::getInstance()->get_template_part( 'note', $note_row_template );
		$note_row = ob_get_clean();

		$replacements = array(
			'{note_id}' => $note_content['id'],
			'{row_class}' => 'gv-entry-note',
			'{note_detail}' => $note_detail_html
		);

		foreach ( $replacements as $tag => $replacement ) {
			$note_row = str_replace( $tag, $replacement, $note_row );
		}

		return $note_row;
	}

	/**
	 * @param array $entry
	 * @param array $data Note details array
	 *
	 * @return int|WP_Error
	 */
	function add_note( $entry, $data ) {
		global $current_user, $wpdb;

		$user_data = get_userdata( $current_user->ID );

		$note_content = wp_unslash( trim( $data['note-content'] ) );

		if( empty( $note_content ) ) {
			return new WP_Error( 'gv-add-note-empty', __( 'The note is empty.', 'gravityview' ) );
		}

		$return = GravityView_Entry_Notes::add_note( $entry['id'], $current_user->ID, $user_data->display_name, $note_content, 'gravityview/frontend' );

		$this->maybe_send_entry_notes( $entry, $data );

		return $return;
	}

	/**
	 * Get the Add Note form HTML
	 *
	 * @since 1.17
	 *
	 * @return string HTML of the Add Note form
	 */
	public static function get_add_note_part() {

		ob_start();
		GravityView_View::getInstance()->get_template_part( 'note', 'row-add-note' );
		$add_note_html = ob_get_clean();

		$entry_slug = gravityview_is_single_entry();
		$nonce_field = wp_nonce_field( 'gv_add_note_' . $entry_slug, 'gv_add_note', false );
		$emails_dropdown = self::get_emails_dropdown();
		$add_note_html = str_replace( '{entry_slug}', $entry_slug, $add_note_html );
		$add_note_html = str_replace( '{nonce_field}', $nonce_field, $add_note_html );
		$add_note_html = str_replace( '{emails_dropdown}', $emails_dropdown, $add_note_html );

		return $add_note_html;
	}

	/**
	 * Generate a HTML dropdown of email values based on email fields from the current form
	 *
	 * @since 1.17
	 *
	 * @param array $note_emails
	 *
	 * @return string HTML output
	 */
	private static function get_emails_dropdown() {

		$gravityview_view = GravityView_View::getInstance();
		//getting email values
		$email_fields = GFCommon::get_email_fields( $gravityview_view->getForm() );
		$lead = $gravityview_view->getCurrentEntry();
		$note_emails = array();

		foreach ( $email_fields as $email_field ) {
			if ( ! empty( $lead[ $email_field->id ] ) ) {
				$note_emails[] = $lead[ $email_field->id ];
			}
		}

		ob_start();

		/** @todo Cleanup and move to JS */
		if ( ! empty( $note_emails ) ) { ?>
			<div>
				<select name="gv_entry_email_notes_to" onchange="if(jQuery(this).val() != '')
			{jQuery('.gv-entry-note-email-subject-container').css('display', 'inline');}
			else{jQuery('.gv-entry-note-email-subject-container').css('display', 'none');}">
					<option value=""><?php esc_html_e( 'Also email this note to', 'gravityview' ) ?></option>
					<?php foreach ( $note_emails as $email ) { ?>
						<option value="<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></option>
					<?php } ?>
				</select>

            <span class='gv-entry-note-email-subject-container'>
                <label for="gentry_email_subject"><?php esc_html_e( 'Subject:', 'gravityview' ) ?></label>
                <input type="text" name="gentry_email_subject" id="gentry_email_subject" value="" style="width:35%"/>
            </span>
			</div>
		<?php }

		// TODO: Add a filter
		return ob_get_clean();
	}

	function maybe_send_entry_notes( $entry, $data ) {
		//emailing notes if configured
		if ( rgpost( 'gv_entry_email_notes_to' ) ) {
			$current_user = wp_get_current_user();

			$form = GFAPI::get_form( $entry['form_id'] );

			GFCommon::log_debug( 'GFEntryDetail::lead_detail_page(): Preparing to email entry notes.' );
			$email_to      = $data['gv_entry_email_notes_to'];
			$email_from    = $current_user->user_email;
			$email_subject = stripslashes( $data['gentry_email_subject'] );
			$body = stripslashes( $data['note-content'] );

			$headers = "From: \"$email_from\" <$email_from> \r\n";
			GFCommon::log_debug( "GFEntryDetail::lead_detail_page(): Emailing notes - TO: $email_to SUBJECT: $email_subject BODY: $body HEADERS: $headers" );
			$is_success  = wp_mail( $email_to, $email_subject, $body, $headers );
			$result = is_wp_error( $is_success ) ? $is_success->get_error_message() : $is_success;
			GFCommon::log_debug( "GFEntryDetail::lead_detail_page(): Result from wp_mail(): {$result}" );
			if ( ! is_wp_error( $is_success ) && $is_success ) {
				GFCommon::log_debug( 'GFEntryDetail::lead_detail_page(): Mail was passed from WordPress to the mail server.' );
			} else {
				GFCommon::log_error( 'GFEntryDetail::lead_detail_page(): The mail message was passed off to WordPress for processing, but WordPress was unable to send the message.' );
			}

			if ( has_filter( 'phpmailer_init' ) ) {
				GFCommon::log_debug( __METHOD__ . '(): The WordPress phpmailer_init hook has been detected, usually used by SMTP plugins, it can impact mail delivery.' );
			}

			/**
			 * Fires after a note is attached to an entry and sent as an email
			 *
			 * @param string $result The Error message or success message when the entry note is sent
			 * @param string $email_to The email address to send the entry note to
			 * @param string $email_from The email address from which the email is sent from
			 * @param string $email_subject The subject of the email that is sent
			 * @param mixed $body The Full body of the email containing the message after the note is sent
			 * @param array $form The current form object
			 * @param array $lead The Current lead object
			 */
			do_action( 'gform_post_send_entry_note', $result, $email_to, $email_from, $email_subject, $body, $form, $lead );
		}
	}
}

new GravityView_Field_Notes;
