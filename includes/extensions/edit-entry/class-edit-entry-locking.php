<?php

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

if ( ! class_exists( 'GFCommon' ) ) {
	return;
}

require_once( GFCommon::get_base_path() . '/includes/locking/class-gf-locking.php' );

/**
 * An entry locking class that syncs with GFEntryLocking.
 *
 * @since 2.5.2
 */
class GravityView_Edit_Entry_Locking extends GFLocking {

	/**
	 * GravityView_Edit_Entry_Locking constructor.
	 *
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct() {}

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

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$this->init_ajax();
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ), 1 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 100 );

		$this->_object_id = $this->get_object_id();
		$this->maybe_lock_object( $this->_object_id );
	}

	public function init_ajax() {

		add_action( 'wp_ajax_nopriv_gf_lock_request_entry', array( $this, 'ajax_lock_request' ) );
		add_action( 'wp_ajax_nopriv_gf_reject_lock_request_entry', array( $this, 'ajax_reject_lock_request' ) );

		parent::init_ajax();

	}

	protected function is_edit_page() {
		return 'edit' === gravityview_get_context();
	}

	/**
	 * Override this method to provide the class with the correct object id.
	 *
	 * @return int
	 */
	protected function get_object_id() {

		if( ! did_action( 'gravityview/loaded') ) {
			return 0;
		}

		$entry = gravityview()->request->is_edit_entry();

		return $entry ? $entry->ID : 0;
	}

	/**
	 * Enqueue the required scripts and styles from Gravity Forms.
	 *
	 * Called via load() and `wp_enqueue_scripts`
	 *
	 * @since 2.6
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

		if ( ! $entry = gravityview()->request->is_edit_entry() ) {
			return;
		}

		$this->_object_id = (int) $entry['id'];
		$this->_object_type = 'entry';
		wp_enqueue_style( 'edit' );
		wp_enqueue_script( 'heartbeat' );

		// Below is a copy of GFLocking::enqueue_scripts()
		// We had to copy/paste because $_object_id and $_object_type aren't overridable because they're private vars

		wp_enqueue_script( 'gforms_locking' );
		wp_enqueue_style( 'gforms_locking_css' );
		$lock_user_id = $this->check_lock( $this->get_object_id() );

		$strings = array(
			'noResponse'    => $this->get_string( 'no_response' ),
			'requestAgain'  => $this->get_string( 'request_again' ),
			'requestError'  => $this->get_string( 'request_error' ),
			'gainedControl' => $this->get_string( 'gained_control' ),
			'rejected'      => $this->get_string( 'request_rejected' ),
			'pending'       => $this->get_string( 'request_pending' )
		);

		$vars = array(
			'hasLock'    => ! $lock_user_id ? 1 : 0,
			'lockUI'     => $this->get_lock_ui( $lock_user_id ),
			'objectID'   => $this->_object_id,
			'objectType' => $this->_object_type,
			'strings'    => $strings,
		);

		wp_localize_script( 'gforms_locking', 'gflockingVars', $vars );
	}

	/**
	 * Returns a string with the Lock UI HTML markup.
	 *
	 * Called script enqueuing, added to JavaScript gforms_locking global variable.
	 *
	 * @since 2.6
	 *
	 * @see check_lock
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

		if( ! $wp ) {
			return;
		}

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
	 * Lock the entry to the current user. Override Gravity Forms permissions check with check_user_cap_edit_entry
	 *
	 * @uses GravityView_Edit_Entry::check_user_cap_edit_entry
	 *
	 * @since 2.6
	 *
	 * @param int $entry_id The entry ID.
	 *
	 * @return int|false User ID the current user if the lock was set. False if not.
	 */
	protected function set_lock( $entry_id ) {

		$entry = GFAPI::get_entry( $entry_id );

		if ( ! GravityView_Edit_Entry::check_user_cap_edit_entry( $entry ) ) {
			return false;
		}

		if ( 0 == ( $user_id = get_current_user_id() ) ) {
			return false;
		}

		$this->update_lock_meta( $entry_id, $user_id );

		return $user_id;
	}
}
