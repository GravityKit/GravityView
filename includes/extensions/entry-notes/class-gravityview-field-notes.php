<?php
/**
 * Notes Field
 *
 * @package     GravityView
 * @license     GPL2+
 * @since       1.17
 * @author      Katz Web Services, Inc.
 * @link        https://gravityview.co
 * @copyright   Copyright 2016, Katz Web Services, Inc.
 */

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
		
		add_filter( 'gravityview_entry_default_fields', array( $this, 'add_entry_default_field' ), 10, 3 );
	}


	/**
	 * Add Entry Notes to the Add Field picker in Edit View
	 *
	 * @see GravityView_Admin_Views::get_entry_default_fields()
	 *
	 * @since 1.17
	 *
	 * @param array $entry_default_fields Fields configured to show in the picker
	 * @param array $form Gravity Forms form array
	 * @param string $zone Current context: `directory`, `single`, `edit`
	 *
	 * @return array Fields array with notes added, if in Multiple Entries or Single Entry context
	 */
	public function add_entry_default_field( $entry_default_fields, $form, $zone ) {

		if( in_array( $zone, array( 'directory', 'single' ) ) ) {
			$entry_default_fields['notes'] = array(
				'label' => __( 'Entry Notes', 'gravityview' ),
				'type'  => 'notes',
				'desc'  => __( 'Display, add, and delete notes for an entry.', 'gravityview' ),
			);
		}

		return $entry_default_fields;
	}

	/**
	 * Register scripts and styles used by the Notes field
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	public function register_scripts() {
		$css_file = gravityview_css_url( 'entry-notes.css', GravityView_Field_Notes::$path . 'assets/css/' );
		wp_register_style( 'gravityview-notes', $css_file, array(), GravityView_Plugin::version );
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

			wp_localize_script( 'gravityview-notes', 'GVNotes', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'text' => array(
					'processing' => $strings['processing'],
					'delete_confirm' => $strings['delete-confirm'],
					'note_added' => $strings['added-note'],
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

		if( ! isset( $_POST['action'] ) ) {
			return;
		}

		if( 'gv_note_add' === $_POST['action'] ) {

			$post = wp_unslash( $_POST );

			if( $this->doing_ajax ) {
				parse_str( $post['data'], $data );
			} else {
				$data = $post;
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

				$entry = gravityview_get_entry( $data['entry-slug'], true, false );

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
						$success = self::display_note( $note, ! empty( $data['show-delete'] ) );
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

			$post = wp_unslash( $_POST );
			if ( $this->doing_ajax ) {
				parse_str( $post['data'], $data );
			} else {
				$data = $post;
			}

			$required_args = array(
				'gv_delete_notes' => '',
				'entry-slug' => '',
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

		$notes_options = array(
			'notes' => array(
				'type' => 'checkboxes',
				'label' => __('Note Settings', 'gravityview'),
				'desc' => sprintf( _x('Only users with specific capabilities will be able to view, add and delete notes. %sRead more%s.', '%s is opening and closing HTML link', 'gravityview' ), '<a href="https://docs.gravityview.co/article/311-gravityview-capabilities">', '</a>' ),
				'options' => array(
					'view' => array(
						'label' => __( 'Display notes?', 'gravityview' ),
					),
					'view_loggedout' => array(
						'label' => __( 'Display notes to users who are not logged-in?', 'gravityview' ),
						'requires' => 'view',
					),
					'add' => array(
						'label' => __( 'Enable adding notes?', 'gravityview' ),
					),
					'email' => array(
						'label' => __( 'Allow emailing notes?', 'gravityview' ),
						'requires' => 'add',
					),
					'delete' => array(
						'label' => __( 'Allow deleting notes?', 'gravityview' ),
					),
				),
				'value' => array( 'view' => 1, 'add' => 1, 'email' => 1 ),
			),
		);

		return $notes_options + $field_options;
	}

	/**
	 * Get strings used by the Entry Notes field
	 *
	 * Use `gravityview/field/notes/strings` filter to modify the strings
	 *
	 * @since 1.17
	 *
	 * @param string $key If set, return the string with the key of $key
	 *
	 * @return array|string Array of strings with keys and values. If $key is set, returns string. If missing $strings[ $key ], empty string.
	 */
	static public function strings( $key = '' ) {

		$strings = array(
			'add-note' => __( 'Add Note', 'gravityview' ),
			'added-note' => __( 'Note added.', 'gravityview' ),
			'content-label' => __( 'Note Content', 'gravityview' ),
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
			'default-email-subject' => __( 'New entry note', 'gravityview' ),
            'email-footer' => __( 'This note was sent from {url}', 'gravityview' ),
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

	/**
	 * Generate HTML output for a single note
	 *
	 * @since 1.17
	 *
	 * @param object $note Note object with id, user_id, date_created, value, note_type, user_name, user_email vars
	 * @param bool $show_delete Whether to show the bulk delete inputs
	 *
	 * @return string HTML
	 */
	static public function display_note( $note, $show_delete = false ) {

		if( ! is_object( $note ) ) {
			return '';
		}

		$note_content = array(
			'avatar'                 => get_avatar( $note->user_id, 48 ),
			'user_name'              => $note->user_name,
			'user_email'             => $note->user_email,
			'added_on'               => esc_html__( 'added on {date_created_formatted}', 'gravityview' ),
			'value'                  => wpautop( esc_html( $note->value ) ),
			'date_created'           => $note->date_created,
			'date_created_formatted' => GFCommon::format_date( $note->date_created, false ),
			'user_id'                => intval( $note->user_id ),
			'note_type'              => $note->note_type,
			'note_id'                => intval( $note->id ),
		);

		/**
		 * @filter `gravityview/field/notes/content` Modify the note content before rendering in the template
		 * @since 1.17
		 * @param array $note_content Array of note content that will be replaced in template files
		 * @param object $note Note object with id, user_id, date_created, value, note_type, user_name, user_email vars
		 * @param boolean $show_delete True: Notes are editable. False: no editing notes.
		 */
		$note_content = apply_filters( 'gravityview/field/notes/content', $note_content, $note, $show_delete );

		ob_start();
		GravityView_View::getInstance()->get_template_part( 'note', 'detail' );
		$note_detail_html = ob_get_clean();

		foreach ( $note_content as $tag => $value ) {
			$note_detail_html = str_replace( '{' . $tag . '}', $value, $note_detail_html );
		}

		$note_row_template = ( $show_delete && GVCommon::has_cap( 'gravityview_delete_entry_notes' ) ) ? 'row-editable' : 'row';

		ob_start();
		GravityView_View::getInstance()->get_template_part( 'note', $note_row_template );
		$note_row = ob_get_clean();

		$replacements = array(
			'{note_id}' => $note_content['note_id'],
			'{row_class}' => 'gv-note',
			'{note_detail}' => $note_detail_html
		);

		// Strip extra whitespace in template
		$output = gravityview_strip_whitespace( $note_row );

		foreach ( $replacements as $tag => $replacement ) {
			$output = str_replace( $tag, $replacement, $output );
		}

		return $output;
	}

	/**
	 * Add a note.
	 *
	 * @since 1.17
	 *
	 * @see GravityView_Entry_Notes::add_note This method is mostly a wrapper
	 *
	 * @param array $entry
	 * @param array $data Note details array
	 *
	 * @return int|WP_Error
	 */
	private function add_note( $entry, $data ) {
		global $current_user, $wpdb;

		$user_data = get_userdata( $current_user->ID );

		$note_content = trim( $data['gv-note-content'] );

		if( empty( $note_content ) ) {
			return new WP_Error( 'gv-add-note-empty', __( 'The note is empty.', 'gravityview' ) );
		}

		$return = GravityView_Entry_Notes::add_note( $entry['id'], $user_data->ID, $user_data->display_name, $note_content, 'gravityview/field/notes' );

		return $return;
	}

	/**
	 * Get the Add Note form HTML
	 *
	 * @todo Allow passing entry_id as a shortcode parameter to set entry from shortcode
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

		$gravityview_view = GravityView_View::getInstance();

		ob_start();
		$gravityview_view->get_template_part( 'note', 'add-note' );
		$add_note_html = ob_get_clean();

		// Strip extra whitespace in template
		$add_note_html = gravityview_strip_whitespace( $add_note_html );

		$visibility_settings = $gravityview_view->getCurrentFieldSetting( 'notes' );
		$entry = $gravityview_view->getCurrentEntry();
		$entry_slug = GravityView_API::get_entry_slug( $entry['id'], $entry );
		$nonce_field = wp_nonce_field( 'gv_note_add_' . $entry_slug, 'gv_note_add', false, false );

		// Only generate the dropdown if the field settings allow it
		$email_fields = '';
		if( ! empty( $visibility_settings['email'] ) ) {
			$email_fields = self::get_note_email_fields( $entry_slug );
		}

		$add_note_html = str_replace( '{entry_slug}', $entry_slug, $add_note_html );
		$add_note_html = str_replace( '{nonce_field}', $nonce_field, $add_note_html );
		$add_note_html = str_replace( '{show_delete}', intval( $visibility_settings['delete'] ), $add_note_html );
		$add_note_html   = str_replace( '{email_fields}', $email_fields, $add_note_html );
		$add_note_html = str_replace( '{url}', esc_url_raw( add_query_arg( array() ) ), $add_note_html );

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
	private static function get_note_email_fields( $entry_slug = '' ) {

		if( ! GVCommon::has_cap( 'gravityview_email_entry_notes' ) ) {
			do_action( 'gravityview_log_error', __METHOD__ . ': User does not have permission to email entry notes ("gravityview_email_entry_notes").' );
			return '';
		}

		$entry_slug_esc = esc_attr( $entry_slug );

		$note_emails = self::get_note_emails_array();

		$strings = self::strings();

		/**
		 * @filter `gravityview/field/notes/custom-email` Whether to include a Custom Email option for users to define a custom email to mail notes to
		 * @since 1.17
		 * @param bool $include_custom Default: true
		 */
		$include_custom = apply_filters( 'gravityview/field/notes/custom-email', true );

		ob_start();

		if ( ! empty( $note_emails ) || $include_custom ) { ?>
			<div class="gv-note-email-container">
				<label for="gv-note-email-to-<?php echo $entry_slug_esc; ?>" class="screen-reader-text"><?php echo $strings['also-email'];  ?></label>
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
				<fieldset class="gv-note-to-container">
					<?php if( $include_custom ) { ?>
					<div class='gv-note-to-custom-container'>
						<label for="gv-note-email-to-custom-<?php echo $entry_slug_esc; ?>"><?php echo $strings['email-label']; ?></label>
						<input type="text" name="gv-note-to-custom" placeholder="<?php echo $strings['email-placeholder']; ?>" id="gv-note-to-custom-<?php echo $entry_slug_esc; ?>" value="" />
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
	 * If note has an email to send, and the user has the right caps, send it
	 *
	 * @since 1.17
	 *
	 * @param false|object $note If note was created, object. Otherwise, false.
	 * @param array $entry Entry data
	 * @param array $data $_POST data
	 *
	 * @return void Tap in to Gravity Forms' `gform_after_email` action if you want a return result from sending the email.
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
                'current-url' => '',
			);

			$current_user  = wp_get_current_user();
			$email_data = wp_parse_args( $data, $default_data );

			$from    = $current_user->user_email;
			$to = $email_data['gv-note-to'];

			/**
			 * Documented in get_note_email_fields
			 * @see get_note_email_fields
			 */
			$include_custom = apply_filters( 'gravityview/field/notes/custom-email', true );

			if( 'custom' === $to && $include_custom ) {
				$to = $email_data['gv-note-to-custom'];
				do_action( 'gravityview_log_debug', __METHOD__ . ': Sending note to a custom email address: ' . $to );
			}

			if ( ! GFCommon::is_valid_email_list( $to ) ) {
				do_action( 'gravityview_log_error', __METHOD__ . ': $to not a valid email or email list (CSV of emails): ' . print_r( $to, true ), $email_data );
				return;
			}

			$bcc = false;
			$reply_to = $from;
			$subject = trim( $email_data['gv-note-subject'] );

			// We use empty() here because GF uses empty to check against, too. `0` isn't a valid subject to GF
			$subject = empty( $subject ) ? self::strings( 'default-email-subject' ) : $subject;
			$message = $email_data['gv-note-content'];
			$email_footer = self::strings( 'email-footer' );
			$from_name     = $current_user->display_name;
			$message_format = 'html';

			/**
			 * @filter `gravityview/field/notes/email_content` Modify the values passed when sending a note email
			 * @see GVCommon::send_email
			 * @since 1.17
			 * @param[in,out] array $email_settings Values being passed to the GVCommon::send_email() method: 'from', 'to', 'bcc', 'reply_to', 'subject', 'message', 'from_name', 'message_format', 'entry', 'email_footer'
			 */
			$email_content = apply_filters( 'gravityview/field/notes/email_content', compact( 'from', 'to', 'bcc', 'reply_to', 'subject', 'message', 'from_name', 'message_format', 'entry', 'email_footer' ) );

			extract( $email_content );

			$is_html = ( 'html' === $message_format );

			// Add the message footer
			$message .= $this->get_email_footer( $email_footer, $is_html, $email_data );

			/**
             * @filter `gravityview/field/notes/wpautop_email` Should the message content have paragraphs added automatically, if using HTML message format
			 * @since 1.18
             * @param bool $wpautop_email True: Apply wpautop() to the email message if using; False: Leave as entered (Default: true)
			 */
			$wpautop_email = apply_filters( 'gravityview/field/notes/wpautop_email', true );

			if ( $is_html && $wpautop_email ) {
				$message = wpautop( $message );
			}

			GVCommon::send_email( $from, $to, $bcc, $reply_to, $subject, $message, $from_name, $message_format, '', $entry, false );

			$form  = isset( $entry['form_id'] ) ? GFAPI::get_form( $entry['form_id'] ) : array();

			/**
			 * @see https://www.gravityhelp.com/documentation/article/10146-2/ It's here for compatibility with Gravity Forms
			 */
			do_action( 'gform_post_send_entry_note', __METHOD__, $to, $from, $subject, $message, $form, $entry );
		}
	}

	/**
     * Get the footer for Entry Note emails
     *
     * `{url}` is replaced by the URL of the page where the note form was embedded
     *
     * @since 1.18
     * @see GravityView_Field_Notes::strings The default value of $message_footer is set here, with the key 'email-footer'
	 *
	 * @param string $email_footer The message footer value
	 * @param bool $is_html True: Email is being sent as HTML; False: sent as text
	 *
	 * @return string If email footer is not empty, return the message with placeholders replaced with dynamic values
	 */
	private function get_email_footer( $email_footer = '', $is_html = true, $email_data = array() ) {

	    $output = '';

		if( ! empty( $email_footer ) ) {
		    $url = rgar( $email_data, 'current-url' );
			$url = html_entity_decode( $url );
			$url = site_url( $url );

			$content = $is_html ? "<a href='{$url}'>{$url}</a>" : $url;

			$email_footer = str_replace( '{url}', $content, $email_footer );

			$output .= "\n\n$email_footer";
		}

		return $output;
	}
}

new GravityView_Field_Notes;
