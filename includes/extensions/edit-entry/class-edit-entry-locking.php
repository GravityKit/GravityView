<?php

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * An entry locking class that syncs with GFEntryLocking.
 *
 * @since 2.5
 */
class GravityView_Edit_Entry_Locking {
	/**
	 * Load extension entry point.
	 *
	 * @since 2.5
	 *
	 * @return void
	 */
	public function load() {
		if ( ! has_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}

	/**
	 * Enqueue the required scripts and styles from Gravity Forms.
	 *
	 * Called via load() and `wp_enqueue_scripts`
	 *
	 * @since 2.5
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

		if ( ! $entry = gravityview()->request->is_edit_entry() ) {
			return;
		}

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
	 * @since 2.5
	 *
	 * @see GravityView_Edit_Entry_Locking::check_lock
	 *
	 * @param int $user_id The User ID that has the current lock. Will be empty if entry is not locked
	 *                     or is locked to the current user. See self::check_lock
	 *
	 * @return string The Lock UI dialog box, etc.
	 */
	public function get_lock_ui( $user_id ) {
		$user = get_userdata( $user_id );

		$locked = $user_id && $user;

		$hidden = $locked ? '' : ' hidden';
		if ( $locked ) {

			$message = '<div class="gform-locked-message">
                            <div class="gform-locked-avatar">' . get_avatar( $user->ID, 64 ) . '</div>
                            <p class="currently-editing" tabindex="0">' . sprintf( $this->get_string( 'currently_locked' ), $user->display_name ) . '</p>
                            <p>

                                <a id="gform-take-over-button" style="display:none" class="button button-primary wp-tab-first" href="' . esc_url( add_query_arg( 'get-edit-lock', '1' ) ) . '">' . __( 'Take Over', 'gravityforms' ) . '</a>
                                <button id="gform-lock-request-button" class="button button-primary wp-tab-last">' . __( 'Request Control', 'gravityforms' ) . '</button>
                                <a class="button" onclick="history.back(-1); return false;">' . $this->get_string( 'cancel' ) . '</a>
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
                                <a id="gform-release-lock-button" class="button button-primary wp-tab-last"  href="' . esc_url( add_query_arg( 'release-edit-lock', '1' ) ) . '">' . $this->get_string( 'accept' ) . '</a>
                                <button id="gform-reject-lock-request-button" style="display:none"  class="button button-primary wp-tab-last">' . __( 'Reject Request', 'gravityforms' ) . '</button>
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
	 * @since 2.5
	 *
	 * @return array An array of translations.
	 */
	public function get_strings() {
		$translations = array(
			'currently_locked'  => __( 'This entry is currently locked. Click on the "Request Control" button to let %s know you\'d like to take over.', 'gravityforms' ), 'currently_editing' => '%s is currently editing this entry',
			'taken_over'        => __( '%s has taken over and is currently editing this entry.', 'gravityforms' ),
			'lock_requested'    => __( '%s has requested permission to take over control of this entry.', 'gravityforms' ),
			'accept'            => __( 'Accept', 'gravityforms' ),
			'cancel'            => __( 'Cancel', 'gravityforms' ),
			'currently_editing' => __( '%s is currently editing', 'gravityforms' ),
			'taken_over'        => __( '%s has taken over and is currently editing.', 'gravityforms' ),
			'gained_control'    => __( 'You now have control', 'gravityforms' ),
			'request_pending'   => __( 'Pending', 'gravityforms' ),
			'no_response'       => __( 'No response', 'gravityforms' ),
			'request_again'     => __( 'Request again', 'gravityforms' ),
			'request_error'     => __( 'Error', 'gravityforms' ),
			'request_rejected'  => __( 'Your request was rejected', 'gravityforms' ),
		);

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
	 * @since 2.5
	 *
	 * @param int $entry_id The entry ID.
	 *
	 * @return int|false Locked or not.
	 */
	public function set_lock( $entry_id ) {

		// Todo: check caps

		if ( 0 == ( $user_id = get_current_user_id() ) ) {
			return false;
		}

		$this->update_lock_meta( $entry_id, $user_id );

		return $user_id;
	}
}
