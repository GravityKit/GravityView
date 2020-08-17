<?php

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * An entry locking class that syncs with GFEntryLocking.
 *
 * @since 2.5.2
 */
class GravityView_Edit_Entry_Locking {

	/**
	 * Load extension entry point.
	 *
	 * DO NOT RENAME this method. Required by the class-edit-entry.php component loader.
	 * @see GravityView_Edit_Entry::load_components()
	 *
	 * @since 2.5.2
	 *
	 * @return void
	 */
	public function load() {
		if ( ! has_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_scripts' ) ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_scripts' ) );
		}

		add_action( 'wp_ajax_gf_lock_request_entry', array( $this, 'ajax_lock_request' ), 1 );
		add_action( 'wp_ajax_gf_reject_lock_request_entry', array( $this, 'ajax_reject_lock_request' ), 1 );
		add_action( 'wp_ajax_nopriv_gf_lock_request_entry', array( $this, 'ajax_lock_request' ) );
		add_action( 'wp_ajax_nopriv_gf_reject_lock_request_entry', array( $this, 'ajax_reject_lock_request' ) );
	}

	// TODO: Convert to extending Gravity Forms
	public function ajax_lock_request() {
		$object_id = rgget( 'object_id' );
		$response  = $this->request_lock( $object_id );
		echo json_encode( $response );
		die();
	}

	// TODO: Convert to extending Gravity Forms
	public function ajax_reject_lock_request() {
		$object_id = rgget( 'object_id' );
		$response  = $this->delete_lock_request_meta( $object_id );
		echo json_encode( $response );
		die();
	}

	// TODO: Convert to extending Gravity Forms
	protected function delete_lock_request_meta( $object_id ) {
		GFCache::delete( 'lock_request_entry_' . $object_id );

		return true;
	}

	// TODO: Convert to extending Gravity Forms
	protected function request_lock( $object_id ) {
		if ( 0 == ( $user_id = get_current_user_id() ) ) {
			return false;
		}

		$lock_holder_user_id = $this->check_lock( $object_id );

		$result = array();
		if ( ! $lock_holder_user_id ) {
			$this->set_lock( $object_id );
			$result['html']   = __( 'You now have control', 'gravityview' );
			$result['status'] = 'lock_obtained';
		} else {

			if( GVCommon::has_cap( 'gravityforms_edit_entries' ) ) {
				$user = get_userdata( $lock_holder_user_id );
				$result['html']   = sprintf( __( 'Your request has been sent to %s.', 'gravityview' ), $user->display_name );
			} else {
				$result['html']   = __( 'Your request has been sent.', 'gravityview' );
			}

			$this->update_lock_request_meta( $object_id, $user_id );

			$result['status'] = 'lock_requested';
		}

		return $result;
	}

	protected function update_lock_request_meta( $object_id, $lock_request_value ) {
		GFCache::set( 'lock_request_entry_' . $object_id, $lock_request_value, true, 120 );
	}

	/**
	 * Checks whether to enqueue scripts based on:
	 *
	 * - Is it Edit Entry?
	 * - Is the entry connected to a View that has `edit_locking` enabled?
	 * - Is the entry connected to a form connected to a currently-loaded View?
	 *
	 * @internal
	 * @since 2.7
	 *
	 * @global WP_Post $post
	 *
	 * @return void
	 */
	public function maybe_enqueue_scripts() {
		global $post;

		if ( ! $entry = gravityview()->request->is_edit_entry() ) {
			return;
		}

		if ( ! $post || ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		$views = \GV\View_Collection::from_post( $post );

		$entry_array = $entry->as_entry();

		$continue_enqueuing = false;

		// If any Views being loaded have entry locking, enqueue the scripts
		foreach( $views->all() as $view ) {

			// Make sure the View has edit locking enabled
			if( ! $view->settings->get( 'edit_locking' ) ) {
				continue;
			}

			// Make sure that the entry belongs to one of the forms connected to one of the Views in this request
			$joined_forms = $view::get_joined_forms( $view->ID );

			$entry_form_id = $entry_array['form_id'];

			if( ! isset( $joined_forms[ $entry_form_id ] ) ) {
				continue;
			}

			$continue_enqueuing = true;

			break;
		}

		if( ! $continue_enqueuing ) {
			return;
		}

		$this->enqueue_scripts( $entry_array );
	}

	/**
	 * Enqueue the required scripts and styles from Gravity Forms.
	 *
	 * Called via load() and `wp_enqueue_scripts`
	 *
	 * @since 2.5.2
	 *
	 * @param array $entry Gravity Forms entry array
	 *
	 * @return void
	 */
	protected function enqueue_scripts( $entry ) {

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';
		$locking_path = GFCommon::get_base_url() . '/includes/locking/';

		wp_enqueue_script( 'gforms_locking', $locking_path . "js/locking{$min}.js", array( 'jquery', 'heartbeat' ), GFCommon::$version );
		wp_enqueue_style( 'gforms_locking_css', $locking_path . "css/locking{$min}.css", array( 'edit' ), GFCommon::$version );

		$translations = array_map( 'wp_strip_all_tags', $this->get_strings() );

		$strings = array(
			'noResponse'    => $translations['no_response'],
			'requestAgain'  => $translations['request_again'],
			'requestError'  => $translations['request_error'],
			'gainedControl' => $translations['gained_control'],
			'rejected'      => $translations['request_rejected'],
			'pending'       => $translations['request_pending'],
		);

		$lock_user_id = $this->check_lock( $entry['id'] );

		$vars = array(
			'hasLock'    => ! $lock_user_id ? 1 : 0,
			'lockUI'     => $this->get_lock_ui( $lock_user_id ),
			'objectID'   => $entry['id'],
			'objectType' => 'entry',
			'strings'    => $strings,
		);

		wp_localize_script( 'gforms_locking', 'gflockingVars', $vars );
	}

	/**
	 * Returns a string with the Lock UI HTML markup.
	 *
	 * Called script enqueuing, added to JavaScript gforms_locking global variable.
	 *
	 * @since 2.5.2
	 *
	 * @see GravityView_Edit_Entry_Locking::check_lock
	 *
	 * @param int $user_id The User ID that has the current lock. Will be empty if entry is not locked
	 *                     or is locked to the current user.
	 *
	 * @return string The Lock UI dialog box, etc.
	 */
	public function get_lock_ui( $user_id ) {
		$user = get_userdata( $user_id );

		$locked = $user_id && $user;

		$hidden = $locked ? '' : ' hidden';
		if ( $locked ) {

			if( GVCommon::has_cap( 'gravityforms_edit_entries' ) ) {
				$avatar = get_avatar( $user->ID, 64 );
				$person_editing_text = $user->display_name;
			} else {
				$current_user = wp_get_current_user();
				$avatar = get_avatar( $current_user->ID, 64 );
				$person_editing_text = _x( 'the person who is editing the entry', 'Referring to the user who is currently editing a locked entry', 'gravityview' );
			}

			$message = '<div class="gform-locked-message">
                            <div class="gform-locked-avatar">' . $avatar . '</div>
                            <p class="currently-editing" tabindex="0">' . esc_html( sprintf( $this->get_string( 'currently_locked' ), $person_editing_text ) ) . '</p>
                            <p>

                                <a id="gform-take-over-button" style="display:none" class="button button-primary wp-tab-first" href="' . esc_url( add_query_arg( 'get-edit-lock', '1' ) ) . '">' . esc_html__( 'Take Over', 'gravityview' ) . '</a>
                                <button id="gform-lock-request-button" class="button button-primary wp-tab-last">' . esc_html__( 'Request Control', 'gravityview' ) . '</button>
                                <a class="button" onclick="history.back(-1); return false;">' . esc_html( $this->get_string( 'cancel' ) ) . '</a>
                            </p>
                            <div id="gform-lock-request-status">
                                <!-- placeholder -->
                            </div>
                        </div>';

		} else {

			$message = '<div class="gform-taken-over">
                            <div class="gform-locked-avatar"></div>
                            <p class="wp-tab-first" tabindex="0">
                                <span class="currently-editing"></span><br>
                            </p>
                            <p>
                                <a id="gform-release-lock-button" class="button button-primary wp-tab-last"  href="' . esc_url( add_query_arg( 'release-edit-lock', '1' ) ) . '">' . esc_html( $this->get_string( 'accept' ) ) . '</a>
                                <button id="gform-reject-lock-request-button" style="display:none"  class="button button-primary wp-tab-last">' . esc_html__( 'Reject Request', 'gravityview' ) . '</button>
                            </p>
                        </div>';

		}
		$html = '<div id="gform-lock-dialog" class="notification-dialog-wrap' . $hidden . '">
                    <div class="notification-dialog-background"></div>
                    <div class="notification-dialog">';
		$html .= $message;

		$html .= '   </div>
                 </div>';

		return $html;
	}

	/**
	 * Localized string for the UI.
	 *
	 * Uses gravityforms textdomain unchanged.
	 *
	 * @since 2.5.2
	 *
	 * @return array An array of translations.
	 */
	public function get_strings() {
		$translations = array(
			'currently_locked'  => __( 'This entry is currently locked. Click on the "Request Control" button to let %s know you\'d like to take over.', 'gravityview' ),
			'currently_editing' => __( '%s is currently editing this entry', 'gravityview' ),
			'taken_over'        => __( '%s has taken over and is currently editing this entry.', 'gravityview' ),
			'lock_requested'    => __( '%s has requested permission to take over control of this entry.', 'gravityview' ),
			'accept'            => __( 'Accept', 'gravityview' ),
			'cancel'            => __( 'Cancel', 'gravityview' ),
			'gained_control'    => __( 'You now have control', 'gravityview' ),
			'request_pending'   => __( 'Pending', 'gravityview' ),
			'no_response'       => __( 'No response', 'gravityview' ),
			'request_again'     => __( 'Request again', 'gravityview' ),
			'request_error'     => __( 'Error', 'gravityview' ),
			'request_rejected'  => __( 'Your request was rejected', 'gravityview' ),
		);

		$translations = array_map( 'wp_strip_all_tags', $translations );

		return $translations;
	}

	/**
	 * Get a localized string.
	 *
	 * @param string $string The string to get.
	 *
	 * @return string A localized string. See self::get_strings()
	 */
	public function get_string( $string ) {
		return \GV\Utils::get( $this->get_strings(), $string, '' );
	}

	/**
	 * Lock the entry... maybe.
	 *
	 * Has 3 modes of locking:
	 *
	 *  - acquire (get), which reloads the page after locking the entry
	 *  - release, which reloads the page after unlocking the entry
	 *  - default action to lock on load if not locked
	 *
	 * @param int $entry_id The entry ID.
	 *
	 * @return void
	 */
	public function maybe_lock_object( $entry_id ) {
		global $wp;

		$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );

		if ( isset( $_GET['get-edit-lock'] ) ) {
			$this->set_lock( $entry_id );
			echo '<script>window.location = ' . json_encode( remove_query_arg( 'get-edit-lock', $current_url ) ) . ';</script>';
			exit();
		} else if ( isset( $_GET['release-edit-lock'] ) ) {
			$this->delete_lock_meta( $entry_id );
			$current_url = remove_query_arg( 'edit', $current_url );
			echo '<script>window.location = ' . json_encode( remove_query_arg( 'release-edit-lock', $current_url ) ) . ';</script>';
			exit();
		} else {
			if ( ! $user_id = $this->check_lock( $entry_id ) ) {
				$this->set_lock( $entry_id );
			}
		}
	}

	/**
	 * Is this entry locked to some other user?
	 *
	 * @param int $entry_id The entry ID.
	 *
	 * @return boolean Yes or no.
	 */
	public function check_lock( $entry_id ) {
		if ( ! $user_id = $this->get_lock_meta( $entry_id ) ) {
			return false;
		}

		if ( $user_id != get_current_user_id() ) {
			return $user_id;
		}

		return false;
	}

	/**
	 * The lock for an entry.
	 *
	 * Leverages Gravity Forms' persistent caching mechanisms.
	 *
	 * @param int $entry_id The entry ID.
	 *
	 * @return int|null The User ID or null.
	 */
	public function get_lock_meta( $entry_id ) {
		return GFCache::get( 'lock_entry_' . $entry_id );
	}

	/**
	 * Set the lock for an entry.
	 *
	 * @param int $entry_id The entry ID.
	 * @param int $user_id The user ID to lock the entry to.
	 *
	 * @return void
	 */
	public function update_lock_meta( $entry_id, $user_id ) {
		GFCache::set( 'lock_entry_' . $entry_id, $user_id, true, 1500 );
	}

	/**
	 * Release the lock for an entry.
	 *
	 * @param int $entry_id The entry ID.
	 *
	 * @return void
	 */
	public function delete_lock_meta( $entry_id ) {
		GFCache::delete( 'lock_entry_' . $entry_id );
	}

	/**
	 * Lock the entry to the current user.
	 *
	 * @since 2.5.2
	 *
	 * @param int $entry_id The entry ID.
	 *
	 * @return int|false Locked or not.
	 */
	public function set_lock( $entry_id ) {

		$entry = GFAPI::get_entry( $entry_id );

		if ( ! GravityView_Edit_Entry::check_user_cap_edit_entry( $entry ) ) {
			return false;
		}

		if ( 0 === ( $user_id = get_current_user_id() ) ) {
			return false;
		}

		$this->update_lock_meta( $entry_id, $user_id );

		return $user_id;
	}
}
