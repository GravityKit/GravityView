<?php

/** If this file is called directly, abort. */

use GV\Utils;
use GV\View_Collection;

if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * An entry locking class that syncs with GFEntryLocking.
 *
 * @since 2.5.2
 */
class GravityView_Edit_Entry_Locking {
	const LOCK_CACHE_KEY_PREFIX = 'lock_entry_';

	/**
	 * Load extension entry point.
	 *
	 * DO NOT RENAME this method. Required by the class-edit-entry.php component loader.
	 *
	 * @since 2.5.2
	 *
	 * @see   GravityView_Edit_Entry::load_components()
	 *
	 * @return void
	 */
	public function load() {
		if ( ! has_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_scripts' ] ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_scripts' ] );
		}

		add_filter( 'heartbeat_received', [ $this, 'heartbeat_refresh_nonces' ], 10, 2 );
		add_filter( 'heartbeat_received', [ $this, 'heartbeat_check_locked_objects' ], 10, 2 );
		add_filter( 'heartbeat_received', [ $this, 'heartbeat_refresh_lock' ], 10, 2 );
		add_filter( 'heartbeat_received', [ $this, 'heartbeat_request_lock' ], 10, 2 );

		add_action( 'wp_ajax_gf_lock_request_entry', [ $this, 'ajax_lock_request' ], 1 );
		add_action( 'wp_ajax_gf_reject_lock_request_entry', [ $this, 'ajax_reject_lock_request' ], 1 );
		add_action( 'wp_ajax_nopriv_gf_lock_request_entry', [ $this, 'ajax_lock_request' ] );
		add_action( 'wp_ajax_nopriv_gf_reject_lock_request_entry', [ $this, 'ajax_reject_lock_request' ] );
	}

	/**
	 * Get the lock request meta for an object.
	 *
	 * @since 2.34.1
	 *
	 * @param int $object_id The object ID.
	 *
	 * @return int|null The User ID or null.
	 */
	protected function get_lock_request_meta( $object_id ) {
		return GFCache::get( 'lock_request_entry_' . $object_id );
	}

	// TODO: Convert to extending Gravity Forms
	public function ajax_lock_request() {
		$object_id = rgget( 'object_id' );

		$response = $this->request_lock( $object_id );

		wp_send_json( $response );
	}

	// TODO: Convert to extending Gravity Forms
	public function ajax_reject_lock_request() {
		$object_id = rgget( 'object_id' );

		$response = $this->delete_lock_request_meta( $object_id );

		wp_send_json( $response );
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

		$result = [];

		if ( ! $lock_holder_user_id ) {
			$this->set_lock( $object_id );

			$result['html']   = __( 'You now have control', 'gk-gravityview' );
			$result['status'] = 'lock_obtained';

			return $result;
		}

		if ( GVCommon::has_cap( 'gravityforms_edit_entries' ) ) {
			$user = get_userdata( $lock_holder_user_id );

			$result['html'] = sprintf( __( 'Your request has been sent to %s.', 'gk-gravityview' ), $user->display_name );
		} else {
			$result['html'] = __( 'Your request has been sent.', 'gk-gravityview' );
		}

		$this->update_lock_request_meta( $object_id, $user_id );

		$result['status'] = 'lock_requested';

		return $result;
	}

	/**
	 * Updates the lock request meta for an object.
	 *
	 * @since 2.34
	 *
	 * @param string $object_id
	 * @param string $lock_request_value
	 *
	 * @return void
	 */
	protected function update_lock_request_meta( $object_id, $lock_request_value ) {
		GFCache::set( 'lock_request_entry_' . $object_id, $lock_request_value, true, 120 );
	}

	/**
	 * Checks whether to enqueue scripts based on:
	 *
	 * - Is it Edit Entry?
	 * - Is the entry connected to a View that has `edit_locking` enabled?
	 * - Is the entry connected to a form connected to a currently-loaded View?
	 * - Does the user have the capability to edit the entry?
	 * - Is the nonce valid?
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

		$entry = $entry->as_entry();

		/**
		 * Overrides whether to load the entry lock UI assets.
		 * This filter runs before checking whether if the edit entry link is valid, user has the capability to edit the entry, etc.
		 *
		 * Filter: `gk/gravityview/edit-entry/renderer/enqueue-entry-lock-assets`
		 *
		 * @since 2.34.1
		 *
		 * @param bool  $load  Whether to load the entry lock UI assets. Default: false.
		 * @param array $entry The entry.
		 */
		if ( apply_filters( 'gk/gravityview/edit-entry/renderer/enqueue-entry-lock-assets', false, $entry ) ) {
			$this->enqueue_scripts( $entry );
		}

		if ( ! $post || ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		$views = View_Collection::from_post( $post );

		// If any Views being loaded have entry locking, enqueue the scripts.
		foreach ( $views->all() as $view ) {
			// Make sure the View has edit locking enabled
			if ( ! $view->settings->get( 'edit_locking' ) ) {
				continue;
			}

			// Make sure that the entry belongs to the View form.
			if ( $view->form->ID !== (int) $entry['form_id'] ) {
				continue;
			}

			// Check user capabilities.
			if ( ! GravityView_Edit_Entry::check_user_cap_edit_entry( $entry, $view ) ) {
				continue;
			}

			// Check the nonce.
			if ( ! ( new GravityView_Edit_Entry_Render( GravityView_Edit_Entry::getInstance() ) )->verify_nonce() ) {
				continue;
			}

			$this->enqueue_scripts( $entry );

			break;
		}
	}

	/**
	 * Enqueues the required scripts and styles from Gravity Forms.
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
		$lock_user_id = $this->check_lock( $entry['id'] );

		// Gravity forms locking checks if #wpwrap exist in the admin dashboard, 
		// So we have to add the lock UI to the body before the gforms locking script is loaded.
		wp_add_inline_script( 'heartbeat', '
			jQuery(document).ready(function($) {
				if ($("#wpwrap").length === 0) {
					var lockUI = ' . json_encode( $this->get_lock_ui( $lock_user_id, $entry ) ) . ';
					$("body").prepend(lockUI);
				}
			});
		' );

		$min          = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';
		$locking_path = GFCommon::get_base_url() . '/includes/locking/';

		wp_enqueue_script( 'gforms_locking', $locking_path . "js/locking{$min}.js", [ 'jquery', 'heartbeat' ], GFCommon::$version );
		wp_enqueue_style( 'gforms_locking_css', $locking_path . "css/locking{$min}.css", [ 'edit' ], GFCommon::$version );

		// add inline css to hide notification-dialog-wrap if it has the hidden class
		wp_add_inline_style( 'gforms_locking_css', '
			.notification-dialog-wrap.hidden {
				display: none;
			}
		' );

		$translations = array_map( 'wp_strip_all_tags', $this->get_strings() );

		$strings = [
			'noResponse'    => $translations['no_response'],
			'requestAgain'  => $translations['request_again'],
			'requestError'  => $translations['request_error'],
			'gainedControl' => $translations['gained_control'],
			'rejected'      => $translations['request_rejected'],
			'pending'       => $translations['request_pending'],
		];

		$lock_user_id = $this->check_lock( $entry['id'] );

		$vars = [
			'hasLock'    => ! $lock_user_id ? 1 : 0,
			'lockUI'     => $this->get_lock_ui( $lock_user_id, $entry ),
			'objectID'   => $entry['id'],
			'objectType' => 'entry',
			'strings'    => $strings,
		];

		wp_localize_script( 'gforms_locking', 'gflockingVars', $vars );
	}

	/**
	 * Returns a string with the Lock UI HTML markup.
	 *
	 * Called script enqueuing, added to JavaScript `gforms_locking` global variable.
	 *
	 * @since 2.5.2
	 *
	 * @see   GravityView_Edit_Entry_Locking::check_lock
	 *
	 * @param int   $user_id The User ID that has the current lock. Will be empty if entry is not locked
	 *                       or is locked to the current user.
	 * @param array $entry   The entry array.
	 *
	 * @return string The Lock UI dialog box, etc.
	 */
	public function get_lock_ui( $user_id, $entry ) {
		$user = get_userdata( $user_id );

		$locked = $user_id && $user;

		$hidden = $locked ? '' : ' hidden';

		if ( $locked ) {
			if ( GVCommon::has_cap( 'gravityforms_edit_entries' ) || $entry['created_by'] == get_current_user_id() ) {
				$avatar              = get_avatar( $user->ID, 64 );
				$person_editing_text = $user->display_name;
			} else {
				$current_user        = wp_get_current_user();
				$avatar              = get_avatar( $current_user->ID, 64 );
				$person_editing_text = _x( 'the person who is editing the entry', 'Referring to the user who is currently editing a locked entry', 'gk-gravityview' );
			}

			$message = '<div class="gform-locked-message">
                            <div class="gform-locked-avatar">' . $avatar . '</div>
                            <p class="currently-editing" tabindex="0">' . esc_html( sprintf( $this->get_string( 'currently_locked' ), $person_editing_text ) ) . '</p>
                            <p>
                                <a id="gform-take-over-button" style="display:none" class="button button-primary wp-tab-first" href="' . esc_url( add_query_arg( 'get-edit-lock', '1' ) ) . '">' . esc_html__( 'Take Over', 'gk-gravityview' ) . '</a>
                                <button id="gform-lock-request-button" class="button button-primary wp-tab-last">' . esc_html__( 'Request Control', 'gk-gravityview' ) . '</button>
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
                                <button id="gform-reject-lock-request-button" style="display:none"  class="button button-primary wp-tab-last">' . esc_html__( 'Reject Request', 'gk-gravityview' ) . '</button>
                            </p>
                        </div>';

		}

		$html = '<div id="gform-lock-dialog" class="notification-dialog-wrap' . $hidden . '">
                    <div class="notification-dialog-background"></div>
                    <div class="notification-dialog">' . $message . '</div>';
		$html .= '</div>';

		/**
		 * Modifies the edit entry lock UI markup.
		 *
		 * @filter `gk/gravityview/edit-entry/renderer/entry-lock-dialog-markup`
		 *
		 * @since  2.34.1
		 *
		 * @param string $html The HTML markup.
		 */
		return apply_filters( 'gk/gravityview/edit-entry/renderer/entry-lock-dialog-markup', $html );
	}

	/**
	 * Returns localized text strings used in the UI.
	 *
	 * @since 2.5.2
	 *
	 * @return array An array of translations.
	 */
	public function get_strings() {
		$translations = [
			'currently_locked'  => __( 'This entry is currently locked. Click on the "Request Control" button to let %s know you\'d like to take over.', 'gk-gravityview' ),
			'currently_editing' => __( '%s is currently editing this entry', 'gk-gravityview' ),
			'taken_over'        => __( '%s has taken over and is currently editing this entry.', 'gk-gravityview' ),
			'lock_requested'    => __( '%s has requested permission to take over control of this entry.', 'gk-gravityview' ),
			'accept'            => __( 'Accept', 'gk-gravityview' ),
			'cancel'            => __( 'Cancel', 'gk-gravityview' ),
			'gained_control'    => __( 'You now have control', 'gk-gravityview' ),
			'request_pending'   => __( 'Pending', 'gk-gravityview' ),
			'no_response'       => __( 'No response', 'gk-gravityview' ),
			'request_again'     => __( 'Request again', 'gk-gravityview' ),
			'request_error'     => __( 'Error', 'gk-gravityview' ),
			'request_rejected'  => __( 'Your request was rejected', 'gk-gravityview' ),
		];

		$translations = array_map( 'wp_strip_all_tags', $translations );

		return $translations;
	}

	/**
	 * Returns a localized string.
	 *
	 * @param string $string The string to get.
	 *
	 * @return string A localized string. See self::get_strings()
	 */
	public function get_string( $string ) {
		return Utils::get( $this->get_strings(), $string, '' );
	}

	/**
	 * Locks the entry... maybe.
	 *
	 * Has 3 modes of locking:
	 *  - acquire (get), which reloads the page after locking the entry
	 *  - release, which reloads the page after unlocking the entry
	 *  - default action to lock on load if not locked
	 *
	 * @since 2.34
	 *
	 * @param int $entry_id The entry ID.
	 *
	 * @return void
	 */
	public function maybe_lock_object( $entry_id ) {
		$current_url = home_url( add_query_arg( null, null ) );

		if ( isset( $_GET['get-edit-lock'] ) ) {
			$this->set_lock( $entry_id );

			echo '<script>window.location = ' . json_encode( remove_query_arg( 'get-edit-lock', $current_url ) ) . ';</script>';

			exit();
		} elseif ( isset( $_GET['release-edit-lock'] ) ) {
			$this->delete_lock_meta( $entry_id );

			$current_url = remove_query_arg( 'edit', $current_url );

			echo '<script>window.location = ' . json_encode( remove_query_arg( 'release-edit-lock', $current_url ) ) . ';</script>';

			exit();
		} elseif ( ! $this->check_lock( $entry_id ) ) {
			$this->set_lock( $entry_id );
		}
	}

	/**
	 * Checks if this entry is locked to some other user.
	 *
	 * @since 2.34.1
	 *
	 * @param int $entry_id The entry ID.
	 *
	 * @return boolean Yes or no.
	 */
	public function check_lock( $entry_id ) {
		$user_id = $this->get_lock_meta( $entry_id );

		if ( ! $user_id || $user_id == get_current_user_id() ) {
			return false;
		}

		return $user_id;
	}

	/**
	 * Check if the current user has a lock request for an object.
	 *
	 * @since 2.34.1
	 *
	 * @param int $object_id The object ID.
	 *
	 * @return int|false The User ID or false.
	 */
	protected function check_lock_request( $object_id ) {
		$user_id = (int) $this->get_lock_request_meta( $object_id );

		if ( ! $user_id || $user_id === get_current_user_id() ) {
			return false;
		}

		return $user_id;
	}

	/**
	 * Returns the lock status by leveraging GF's persistent caching mechanism.
	 *
	 * @since 2.34.1
	 *
	 * @param int $entry_id The entry ID.
	 *
	 * @return int|null The User ID or null.
	 */
	public function get_lock_meta( $entry_id ) {
		return GFCache::get( $this->get_lock_cache_key_for_entry( $entry_id ) );
	}

	/**
	 * Sets the lock for an entry.
	 *
	 * @since 2.34.1
	 *
	 * @param int $entry_id The entry ID.
	 * @param int $user_id  The user ID to lock the entry to.
	 *
	 * @return void
	 */
	public function update_lock_meta( $entry_id, $user_id ) {
		GFCache::set( $this->get_lock_cache_key_for_entry( $entry_id ), $user_id, true, 1500 );
	}

	/**
	 * Returns the cache key used to retrieve/save the lock status for an entry.
	 *
	 * @since 2.34.1
	 *
	 * @param int $entry_id
	 *
	 * @return string
	 */
	public function get_lock_cache_key_for_entry( $entry_id ) {
		return self::LOCK_CACHE_KEY_PREFIX . $entry_id;
	}

	/**
	 * Releases the lock for an entry.
	 *
	 * @since 2.34.1
	 *
	 * @param int $entry_id The entry ID.
	 *
	 * @return void
	 */
	public function delete_lock_meta( $entry_id ) {
		GFCache::delete( $this->get_lock_cache_key_for_entry( $entry_id ) );
	}

	/**
	 * Locks the entry to the current user.
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

	/**
	 * Checks if the objects are locked.
	 *
	 * @since 2.34.1
	 *
	 * @param array $response The response array.
	 * @param array $data     The data array.
	 *
	 * @return array The response array.
	 */
	public function heartbeat_check_locked_objects( $response, $data ) {
		$checked = [];

		$heartbeat_key = 'gform-check-locked-objects-entry';

		if ( array_key_exists( $heartbeat_key, $data ) && is_array( $data[ $heartbeat_key ] ) ) {
			foreach ( $data[ $heartbeat_key ] as $object_id ) {
				if ( ( $user_id = $this->check_lock( $object_id ) ) && ( $user = get_userdata( $user_id ) ) ) {
					$send = [ 'text' => sprintf( __( $this->get_string( 'currently_editing' ), 'gk-gravityview' ), $user->display_name ) ];

					if ( ( $avatar = get_avatar( $user->ID, 18 ) ) && preg_match( "|src='([^']+)'|", $avatar, $matches ) ) {
						$send['avatar_src'] = $matches[1];
					}

					$checked[ $object_id ] = $send;
				}
			}
		}

		if ( ! empty( $checked ) ) {
			$response[ $heartbeat_key ] = $checked;
		}

		return $response;
	}

	/**
	 * Refreshes the lock for an entry.
	 *
	 * @since 2.34.1
	 *
	 * @param array $response The response array.
	 * @param array $data     The data array.
	 *
	 * @return array The response array.
	 */
	public function heartbeat_refresh_lock( $response, $data ) {
		$heartbeat_key = 'gform-refresh-lock-entry';

		if ( array_key_exists( $heartbeat_key, $data ) ) {
			$received = $data[ $heartbeat_key ];

			$send = [];

			if ( ! isset( $received['objectID'] ) ) {
				return $response;
			}

			$object_id = $received['objectID'];

			if ( ( $user_id = $this->check_lock( $object_id ) ) && ( $user = get_userdata( $user_id ) ) ) {
				$error = [
					'text' => sprintf( __( $this->get_string( 'taken_over' ), 'gk-gravityview' ), $user->display_name ),
				];

				$avatar = get_avatar( $user->ID, 64 );

				if ( $avatar && preg_match( "|src='([^']+)'|", $avatar, $matches ) ) {
					$error['avatar_src'] = $matches[1];
				}

				$send['lock_error'] = $error;
			} elseif ( $new_lock = $this->set_lock( $object_id ) ) {
				$send['new_lock'] = $new_lock;

				if ( ( $lock_requester = $this->check_lock_request( $object_id ) ) && ( $user = get_userdata( $lock_requester ) ) ) {
					$lock_request = [
						'text' => sprintf( __( $this->get_string( 'lock_requested' ), 'gk-gravityview' ), $user->display_name ),
					];

					$avatar = get_avatar( $user->ID, 64 );

					if ( $avatar && preg_match( "|src='([^']+)'|", $avatar, $matches ) ) {
						$lock_request['avatar_src'] = $matches[1];
					}

					$send['lock_request'] = $lock_request;
				}
			}

			$response[ $heartbeat_key ] = $send;
		}

		return $response;
	}

	/**
	 * Requests the lock for an entry.
	 *
	 * @since 2.34.1
	 *
	 * @param array  $response  The response array.
	 * @param array  $data      The data array.
	 * @param string $screen_id The screen ID.
	 *
	 * @return array The response array.
	 */
	public function heartbeat_request_lock( $response, $data ) {
		$heartbeat_key = 'gform-request-lock-entry';

		if ( ! array_key_exists( $heartbeat_key, $data ) ) {
			return $response;
		}

		$received = $data[ $heartbeat_key ];

		$send = [];

		if ( ! isset( $received['objectID'] ) ) {
			return $response;
		}

		$object_id = $received['objectID'];

		$user_id = $this->check_lock( $object_id );

		if ( $user_id && get_userdata( $user_id ) ) {
			$send['status'] = $this->get_lock_request_meta( $object_id ) ? 'pending' : 'deleted';
		} elseif ( $this->set_lock( $object_id ) ) {
			$send['status'] = 'granted';
		}

		$response[ $heartbeat_key ] = $send;

		return $response;
	}

	/**
	 * Refreshes nonces for an entry.
	 *
	 * @since 2.34.1
	 *
	 * @param array $response The response array.
	 * @param array $data     The data array.
	 *
	 * @return array The response array.
	 */
	public function heartbeat_refresh_nonces( $response, $data ) {
		if ( ! array_key_exists( 'gform-refresh-nonces', $data ) ) {
			return $response;
		}

		$received = $data['gform-refresh-nonces'];

		$response['gform-refresh-nonces'] = [ 'check' => 1 ];

		if ( ! isset( $received['objectID'] ) ) {
			return $response;
		}

		$object_id = $received['objectID'];

		if ( ! GVCommon::has_cap( 'gravityforms_edit_entries' ) || empty( $received['post_nonce'] ) ) {
			return $response;
		}

		if ( 2 === wp_verify_nonce( $received['object_nonce'], 'update-contact_' . $object_id ) ) {
			$response['gform-refresh-nonces'] = [
				'replace'        => [
					'_wpnonce' => wp_create_nonce( 'update-object_' . $object_id ),
				],
				'heartbeatNonce' => wp_create_nonce( 'heartbeat-nonce' ),
			];
		}

		return $response;
	}
}
