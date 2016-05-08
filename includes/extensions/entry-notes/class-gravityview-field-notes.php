<?php

/**
 * Add Entry Notes
 * @since 1.17
 */
class GravityView_Field_Notes extends GravityView_Field {

	/**
	 * @var string Current __FILE__
	 * @since 1.17
	 */
	static $file;

	/**
	 * @var string plugin_dir_path() of the current field file
	 * @since 1.17
	 */
	static $path;

	/**
	 * @var bool Are we doing an AJAX request?
	 * @since 1.17
	 */
	private $doing_ajax = false;

	/**
	 * The name of the GravityView field type
	 * @var string
	 */
	var $name = 'notes';

	function __construct() {

		self::$path = plugin_dir_path( __FILE__ );
		self::$file = __FILE__;

		$this->doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

		$this->add_hooks();

		parent::__construct();
	}
	
	/**
	 * Add AJAX hooks, [gv_note_add] shortcode, and template loading paths
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
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

		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts') );
		add_action( 'gravityview/field/notes/scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Register scripts and styles used by the Notes field
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	public function register_scripts() {
		wp_register_style( 'gravityview-notes', plugins_url( '/assets/css/entry-notes.css', GravityView_Field_Notes::$file ), array(), GravityView_Plugin::version );
		wp_register_script( 'gravityview-notes', plugins_url( '/assets/js/entry-notes.js', GravityView_Field_Notes::$file ), array( 'jquery' ), GravityView_Plugin::version, true );
	}

	/**
	 * Enqueue, localize field scripts and styles
	 * 
	 * @since 1.17
	 * 
	 * @return void
	 */
	public function enqueue_scripts() {
		global $wp_actions;

		if( ! wp_script_is( 'gravityview-notes', 'enqueued' ) ) {
			wp_enqueue_style( 'gravityview-notes' );
			wp_enqueue_script( 'gravityview-notes' );
		}

		if( ! wp_script_is( 'gravityview-notes', 'done' ) ) {

			$strings = self::strings();

			wp_localize_script( 'gravityview-notes', 'GVEntryNotes', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'text' => array(
					'processing' => $strings['processing'],
					'delete_confirm' => $strings['delete-confirm'],
					'error_invalid' => $strings['error-invalid'],
					'error_empty_note' => $strings['error-empty-note'],
				),
			) );
		}
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

		if( ! GVCommon::has_cap( 'gravityview_add_entry_notes' ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ': The user isnt allowed to add entry notes.' );
			return;
		}

		if( 'gv_note_add' === rgpost('action') ) {

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
	 *  @type string $gv-note-content Note content
	 *  @type string $add_note Submit button value ('Add Note')
	 * }
	 *
	 * @return void
	 */
	private function process_add_note( $data ) {

		$error = false;
		$success = false;

		if( empty( $data['entry-slug'] ) ) {

			$error = self::strings('error-invalid');
			do_action( 'gravityview_log_error', __METHOD__ . ': The note is missing an Entry ID.' );

		} else {

			$valid = wp_verify_nonce( $data['gv_note_add'], 'gv_note_add_' . $data['entry-slug'] );
			
			$has_cap = GVCommon::has_cap( 'gravityview_add_entry_notes' );

			if( ! $has_cap ) {
				$error = self::strings( 'error-cap-add' );
				do_action( 'gravityview_log_error', __METHOD__ . ': Adding a note failed: the user does not have the "gravityview_add_entry_notes" capability.' );
			} elseif ( $valid ) {

				$entry = gravityview_get_entry( $data['entry-slug'], false );

				$added = $this->add_note( $entry, $data );

				// Error adding note
				if ( is_wp_error( $added ) ) {

					$error = $added->get_error_message();

				} else {

					// Confirm the note was added, because GF doesn't return note ID on success
					$note = GravityView_Entry_Notes::get_note( $added );

					// Possibly email peeps about this great new note
					$this->maybe_send_entry_notes( $note, $entry, $data );

					if ( $note ) {
						$success = self::display_note( $note, true );
						do_action( 'gravityview_log_debug', __METHOD__ . ': The note was successfully created', compact('note', 'data') );
					} else {
						$error = self::strings('error-add-note');
						do_action( 'gravityview_log_error', __METHOD__ . ': The note was not successfully created', compact('note', 'data') );
					}
				}
			} else {
				$error = self::strings('error-invalid');
				do_action( 'gravityview_log_error', __METHOD__ . ': Nonce validation failed; the note was not created' );
			}
		}


		if( $this->doing_ajax ) {
			if( $success ) {
				wp_send_json_success( array( 'html' => $success ) );
			} else {
				$error = $error ? $error : self::strings( 'error-invalid' );
				wp_send_json_error( array( 'error' => esc_html( $error ) ) );
			}
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

		if ( ! GVCommon::has_cap( 'gravityview_delete_entry_notes' ) ) {
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
	 *  @type int[]  $note  Array of Note IDs to be deleted
	 * }
	 *
	 * @return void
	 */
	function process_delete_notes( $data ) {

		$valid = wp_verify_nonce( $data['gv_delete_notes'], 'gv_delete_notes_' . $data['entry-slug'] );
		$has_cap = GVCommon::has_cap( 'gravityview_delete_entry_notes' );
		$success = false;

		if ( $valid && $has_cap ) {
			GravityView_Entry_Notes::delete_notes( $data['note'] );
			$success = true;
		}

		if( $this->doing_ajax ) {

			if( $success ) {
				wp_send_json_success();
			} else {
				if ( ! $valid ) {
					$error_message = self::strings( 'error-invalid' );
				} else {
					$error_message = self::strings( 'error-permission-delete' );
				}

				wp_send_json_error( array( 'error' => $error_message ) );
			}
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
	 * Get strings used by the Entry Notes field
	 *
	 * Use `gravityview/field/notes/strings` filter to modify the strings
	 *
	 * @param string $key If set, return the string with the key of $key
	 *
	 * @return array|string Array of strings with keys and values. If $key is set, returns string. If missing $strings[ $key ], empty string.
	 */
	static public function strings( $key = '' ) {

		$strings = array(
			'delete' => __( 'Delete', 'gravityview' ),
			'delete-confirm' => __( 'Are you sure you want to delete the selected notes?', 'gravityview' ),
			'caption' => __( 'Notes for this entry', 'gravityview' ),
			'toggle-notes' => __( 'Toggle all notes', 'gravityview' ),
			'no-notes' => __( 'There are no notes.', 'gravityview' ),
			'processing' => __( 'Processing&hellip;', 'gravityview' ),
			'other-email' => __( 'Other email address', 'gravityview' ),
			'email-label' => __( 'Email address', 'gravityview' ),
			'email-placeholder' => _x('you@example.com', 'Example email address used as a placeholder', 'gravityview'),
			'subject-label' => __( 'Subject', 'gravityview' ),
			'subject' => __( 'Email subject', 'gravityview' ),
			'also-email' => __( 'Also email this note to', 'gravityview' ),
			'error-add-note' => __( 'There was an error adding the note.', 'gravityview' ),
			'error-invalid' => __( 'The request was invalid. Refresh the page and try again.', 'gravityview' ),
			'error-empty-note' => _x( 'Note cannot be blank.', 'Message to display when submitting a note without content.', 'gravityview' ),
			'error-cap-delete' => __( 'You don\'t have the ability to delete notes.', 'gravityview' ),
			'error-cap-add' => __( 'You don\'t have the ability to add notes.', 'gravityview' ),
		);

		/**
		 * @filter `gravityview/field/notes/strings` Modify the text used in the Entry Notes field. Sanitized by `esc_html` after return.
		 * @since 1.17
		 * @param array $strings Text in key => value pairs
		 */
		$strings = gv_map_deep( apply_filters( 'gravityview/field/notes/strings', $strings ), 'esc_html' );

		if( $key ) {
			return isset( $strings[ $key ] ) ? $strings[ $key ] : '';
		}

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
		if ( $is_editable && GVCommon::has_cap( 'gravityview_delete_entry_notes' ) ) {
			$note_row_template = 'row-editable';
		}

		ob_start();
		GravityView_View::getInstance()->get_template_part( 'note', $note_row_template );
		$note_row = ob_get_clean();

		$replacements = array(
			'{note_id}' => $note_content['id'],
			'{row_class}' => 'gv-note',
			'{note_detail}' => $note_detail_html
		);

		// Strip extra whitespace in template
		$output = normalize_whitespace( $note_row );

		foreach ( $replacements as $tag => $replacement ) {
			$output = str_replace( $tag, $replacement, $output );
		}

		return $output;
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

		$note_content = wp_unslash( trim( $data['gv-note-content'] ) );

		if( empty( $note_content ) ) {
			return new WP_Error( 'gv-add-note-empty', __( 'The note is empty.', 'gravityview' ) );
		}

		$return = GravityView_Entry_Notes::add_note( $entry['id'], $current_user->ID, $user_data->display_name, $note_content, 'gravityview/frontend' );

		return $return;
	}

	/**
	 * Get the Add Note form HTML
	 *
	 * @since 1.17
	 *
	 * @return string HTML of the Add Note form, or empty string if the user doesn't have the `gravityview_add_entry_notes` cap
	 */
	public static function get_add_note_part() {

		if( ! GVCommon::has_cap( 'gravityview_add_entry_notes' ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ': User does not have permission to add entry notes ("gravityview_add_entry_notes").' );
			return '';
		}

		ob_start();
		GravityView_View::getInstance()->get_template_part( 'note', 'add-note' );
		$add_note_html = ob_get_clean();

		$entry = GravityView_View::getInstance()->getCurrentEntry();
		$entry_slug = GravityView_API::get_entry_slug( $entry['id'], $entry );
		$nonce_field = wp_nonce_field( 'gv_note_add_' . $entry_slug, 'gv_note_add', false, false );
		$emails_dropdown = self::get_emails_dropdown( $entry_slug );
		$add_note_html = str_replace( '{entry_slug}', $entry_slug, $add_note_html );
		$add_note_html = str_replace( '{nonce_field}', $nonce_field, $add_note_html );
		$add_note_html = str_replace( '{emails_dropdown}', $emails_dropdown, $add_note_html );

		return $add_note_html;
	}

	/**
	 * Get array of emails addresses from the stored entry
	 *
	 * @since 1.17
	 *
	 * @return array Array of email addresses connected to the entry
	 */
	private static function get_note_emails_array() {

		$gravityview_view = GravityView_View::getInstance();

		//getting email values
		$email_fields = GFCommon::get_email_fields( $gravityview_view->getForm() );

		$entry = $gravityview_view->getCurrentEntry();

		$note_emails = array();

		foreach ( $email_fields as $email_field ) {
			if ( ! empty( $entry["{$email_field->id}"] ) && is_email( $entry["{$email_field->id}"] ) ) {
				$note_emails[] = $entry["{$email_field->id}"];
			}
		}

		/**
		 * @filter `gravityview/field/notes/emails` Modify the dropdown values displayed in the "Also email note to" dropdown
		 * @since 1.17
		 * @param array $note_emails Array of email addresses connected to the entry
		 * @param array $entry Current entry
		 */
		$note_emails = apply_filters( 'gravityview/field/notes/emails', $note_emails, $entry );

		return (array) $note_emails;
	}

	/**
	 * Generate a HTML dropdown of email values based on email fields from the current form
	 *
	 * @uses get_note_emails_array
	 *
	 * @since 1.17
	 *
	 * @param int|string $entry_slug Current entry unique ID
	 *
	 * @return string HTML output
	 */
	private static function get_emails_dropdown( $entry_slug = '' ) {

		if( ! GVCommon::has_cap( 'gravityview_email_entry_notes' ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ': User does not have permission to email entry notes ("gravityview_email_entry_notes").' );
			return '';
		}

		$entry_slug_esc = esc_attr( $entry_slug );

		$note_emails = self::get_note_emails_array( $entry_slug );

		$strings = self::strings();

		/**
		 * @filter `gravityview/field/notes/custom-email` Whether to include a Custom Email option for users to define a custom email to mail notes to
		 * @param bool $include_custom Default: true
		 */
		$include_custom = apply_filters( 'gravityview/field/notes/custom-email', true );

		ob_start();

		if ( ! empty( $note_emails ) || $include_custom ) { ?>
			<div class="gv-note-email-container">
				<label for="gv-note-email-to-<?php echo $entry_slug_esc; ?>"></label>
				<select class="gv-note-email-to" name="gv-note-to" id="gv-note-email-to-<?php echo $entry_slug_esc; ?>">
					<option value=""><?php echo $strings['also-email'];  ?></option>
					<?php foreach ( $note_emails as  $email ) {
						?>
						<option value="<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></option>
					<?php }
					if( $include_custom ) { ?>
					<option value="custom"><?php echo self::strings('other-email'); ?></option>
					<?php } ?>
				</select>
				<fieldset>
					<?php if( $include_custom ) { ?>
					<div class='gv-note-to-custom-container'>
						<label for="gv-note-email-to-custom-<?php echo $entry_slug_esc; ?>"><?php echo $strings['email-label']; ?></label>
						<input type="text" name="gv-note-to-custom" placeholder="<?php echo $strings['example-placeholder']; ?>" id="gv-note-to-custom-<?php echo $entry_slug_esc; ?>" value="" />
					</div>
					<?php } ?>
		            <div class='gv-note-subject-container'>
		                <label for="gv-note-subject-<?php echo $entry_slug_esc; ?>"><?php echo $strings['subject-label']; ?></label>
		                <input type="text" name="gv-note-subject" placeholder="<?php echo $strings['subject']; ?>" id="gv-note-subject-<?php echo $entry_slug_esc; ?>" value="" />
		            </div>
				</fieldset>
			</div>
		<?php }

		// TODO: Add a filter
		return ob_get_clean();
	}

	/**
	 * @param false|object $note If note was created, object. Otherwise, false.
	 * @param array $entry Entry data
	 * @param array $data $_POST data
	 */
	private function maybe_send_entry_notes( $note = false, $entry, $data ) {

		if( ! $note || ! GVCommon::has_cap('gravityview_email_entry_notes') ) {
			do_action( 'gravityview_log_debug', __METHOD__ . ': User doesnt have "gravityview_email_entry_notes" cap, or $note is empty', $note );
			return;
		}

		do_action( 'gravityview_log_debug', __METHOD__ . ': $data', $data );

		//emailing notes if configured
		if ( ! empty( $data['gv-note-to'] ) ) {

			$default_data = array(
				'gv-note-to' => '',
				'gv-note-to-custom' => '',
				'gv-note-subject' => '',
				'gv-note-content' => '',
			);

			$current_user  = wp_get_current_user();
			$email_data = wp_parse_args( $data, $default_data );

			$from    = $current_user->user_email;
			$to = $email_data['gv-note-to'];

			// TODO: Check whether custom is allowed
			$include_custom = apply_filters( 'gravityview/field/notes/custom-email', true );

			if( 'custom' === $to && $include_custom ) {
				$to = $email_data['gv-note-to-custom'];
				do_action( 'gravityview_log_debug', __METHOD__ . ': Sending note to a custom email address: ' . $to );
			}

			if ( ! is_email( $to ) ) {
				do_action( 'gravityview_log_error', __METHOD__ . ': $to not a valid email address: ' . $to, $email_data );
				return;
			}

			$bcc = false;
			$reply_to = $from;
			$subject = stripslashes_deep( $email_data['gv-note-subject'] );
			$message = stripslashes_deep( $email_data['gv-note-content'] );
			$from_name     = $current_user->display_name;
			$message_format = 'html';

			/**
			 * @filter `gravityview/field/notes/email_content` Modify the values passed when sending a note email
			 * @see GVCommon::send_email
			 * @since 1.17
			 * @param[in,out] array $email_settings Values being passed to the GVCommon::send_email() method: 'from', 'to', 'bcc', 'reply_to', 'subject', 'message', 'from_name', 'message_format', 'entry'
			 */
			$email_content = apply_filters( 'gravityview/field/notes/email_content', compact( 'from', 'to', 'bcc', 'reply_to', 'subject', 'message', 'from_name', 'message_format', 'entry' ) );

			extract( $email_content );

			GVCommon::send_email( $from, $to, $bcc, $reply_to, $subject, $message, $from_name, $message_format, '', $entry, false );

			$form  = isset( $entry['form_id'] ) ? GFAPI::get_form( $entry['form_id'] ) : array();

			/**
			 * @see https://www.gravityhelp.com/documentation/article/10146-2/ It's here for compatibility with Gravity Forms
			 */
			do_action( 'gform_post_send_entry_note', __METHOD__, $to, $from, $subject, $message, $form, $entry );
		}
	}
}

new GravityView_Field_Notes;
