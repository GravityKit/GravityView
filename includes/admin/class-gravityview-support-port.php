<?php

/**
 * @since 1.15
 */
class GravityView_Support_Port {

	/**
	 * @var string The name of the User Meta option used to store whether a user wants to see the Support Port
	 * @since 1.15
	 */
	const user_pref_name = 'gravityview_support_port';

	/**
	 * @var string The hash key used to generate secure message history
     * @since 2.2.5
	 */
	const beacon_key = 'lCXlwbQR707kipR+J0MCqcxrhGOHjGF0ldD6yNbGM0w=';

	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * @since 1.15
	 */
	private function add_hooks() {
		add_action( 'personal_options', array( $this, 'user_field' ) );
		add_action( 'personal_options_update', array( $this, 'update_user_meta_value' ) );
		add_action( 'edit_user_profile_update', array( $this, 'update_user_meta_value' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_script' ), 1000 );
	}

	/**
	 * Enqueue Support Port script if user has it enabled and we're on a GravityView plugin page
	 *
	 * @uses \GV\Admin_Request::is_admin()
	 * @uses wp_enqueue_script()
	 * @since 1.15
	 *
	 * @return void
	 */
	public static function maybe_enqueue_script( $hook ) {
		global $pagenow;

		// Don't show if not GravityView page, or if we're on the Widgets page
		if ( ! gravityview()->request->is_admin( $hook ) || $pagenow === 'widgets.php' ) {
			return;
		}

		/**
		 * @filter `gravityview/support_port/display` Whether to display Support Port
		 * @since 1.15
		 * @param boolean $display_beacon Default: `true`
		 */
		$display_support_port = apply_filters( 'gravityview/support_port/display', self::show_for_user() );

		if ( empty( $display_support_port ) ) {
			gravityview()->log->debug( 'Not showing Support Port' );

			return;
		}

		$script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'gravityview-support', plugins_url( 'assets/js/support' . $script_debug . '.js', GRAVITYVIEW_FILE ), array(), \GV\Plugin::$version, true );

		self::_localize_script();
	}

	/**
	 * Localize the Support Port script
	 *
	 * @uses wp_localize_script()
	 * @since 1.15
	 * @return void
	 */
	private static function _localize_script() {

		$translation = array(
			'agentLabel'                => __( 'GravityView Support', 'gravityview' ),
			'searchLabel'               => __( 'Search GravityView Docs', 'gravityview' ),
			'docsSearchErrorText'          => __( 'Your search timed out. Please double-check your internet connection and try again.', 'gravityview' ),
			'noResultsLabel'            => _x( 'No results found for', 'a support form search has returned empty for the following word', 'gravityview' ),
			'contactLabel'              => __( 'Contact Support', 'gravityview' ),
			'attachAFile'               => __( 'Attach a screenshot or file', 'gravityview' ),
			'attachmentSizeErrorText'   => __( 'The maximum file size is 10 MB', 'gravityview' ),
			'nameLabel'                 => __( 'Your Name', 'gravityview' ),
			'nameError'                 => __( 'Please enter your name', 'gravityview' ),
			'emailLabel'                => __( 'Email address', 'gravityview' ),
			'emailValidationLabel'      => __( 'Please enter a valid email address', 'gravityview' ),
			'subjectLabel'              => __( 'Subject', 'gravityview' ),
			'subjectError'              => _x( 'Please enter a subject', 'Error shown when submitting support request and there is no subject provided', 'gravityview' ),
			'messageLabel'              => __( 'How can we help you?', 'gravityview' ),
			'messageError'              => _x( 'Please enter a message', 'Error shown when submitting support request and there is no message provided', 'gravityview' ),
			'weAreOnIt'                 => __( 'Message sent!', 'gravityview' ),
			'contactSuccessDescription' => __( 'Thanks for reaching out! Someone from the GravityView team will get back to you soon.', 'gravityview' ),
		);

		$response = gravityview()->plugin->settings->get( 'license_key_response' );

		$response = wp_parse_args( $response, array(
			'license'          => '',
			'message'          => '',
			'license_key'      => '',
			'license_limit'    => '',
			'expires'          => '',
			'activations_left' => '',
			'site_count'       => '',
			'payment_id'       => '',
			'customer_name'    => '',
			'customer_email'   => '',
            'price_id'         => '0',
		) );

		// This is just HTML we don't need.
		unset( $response['message'] );

		switch ( intval( $response['price_id'] ) ) {
			default:
			case 1:
				$package = 'Core';
				break;
			case 2:
				$package = 'Extensions';
				break;
			case 3:
				$package = 'All Access';
				break;
            case 4:
                $package = 'Lifetime';
                break;
		}

		$current_user = wp_get_current_user();
		$no_conflict_mode = gravityview()->plugin->settings->get( 'no-conflict-mode' );

		// Prevent any PHP warnings
		ob_start();

		$data = array(
			'email'                 => $current_user->user_email,
			'name'                  => mb_substr( $current_user->display_name, 0, 80 ),
			'signature'             => hash_hmac( 'sha256', $current_user->user_email, self::beacon_key ),
			'License Key'           => $response['license_key'] . ' (' . ucwords( $response['license'] ) . ')',
			'License Level'         => $package,
			'Alt Emails'            => sprintf( "Admin: %s, GV Support: %s", get_bloginfo( 'admin_email' ), gravityview()->plugin->settings->get( 'support-email' ) ),
			'Payment Details'       => $response['customer_name'] . ' ' . $response['customer_email'],
			'WordPress Version'     => get_bloginfo( 'version', 'display' ),
			'PHP Version'           => phpversion() . ' on ' . esc_html( $_SERVER['SERVER_SOFTWARE'] ),
			'No-Conflict Mode'      => empty( $no_conflict_mode ) ? 'Disabled' : 'Enabled',
			'GravityView Version'   => \GV\Plugin::$version,
			'Gravity Forms Version' => GFForms::$version,
		);

		// End prevent any PHP warnings
		ob_get_clean();

		// Help Scout length limit is 200 characters
		foreach( $data as $key => $value ) {
			if ( ! is_string( $value ) ) {
                continue;
			}
		    $data[ $key ] = mb_substr( $value, 0, 200 );
        }

		$localization_data = array(
			'contactEnabled' => (int)GVCommon::has_cap( 'gravityview_contact_support' ),
			'data' => $data,
			'translation' => $translation,
            'suggest' => array(),
		);

		/**
         * @filter `gravityview/support_port/localization_data` Filter data passed to the Support Port, before localize_script is run
		 * @since 2.0
         * @param array $localization_data {
         *   @type int $contactEnabled Can the user contact support?
         *   @type array $data Support/license info
         *   @type array $translation i18n strings
         *   @type array $suggest Article IDs to recommend to the user (per page in the admin
         * }
		 */
		$localization_data = apply_filters( 'gravityview/support_port/localization_data', $localization_data );

		wp_localize_script( 'gravityview-support', 'gvSupport', $localization_data );

		unset( $localization_data, $data, $translation, $response, $package );
	}

	/**
	 * Check whether to show Support for a user
	 *
	 * If the user doesn't have the `gravityview_support_port` capability, returns false; then
	 * If global setting is "hide", returns false; then
     * If user preference is not set, return global setting; then
     * If user preference is set, return that setting.
	 *
	 * @since 1.15
     * @since 1.17.5 Changed behavior to respect global setting
	 *
	 * @param int $user Optional. ID of the user to check, defaults to 0 for current user.
	 *
	 * @return bool Whether to show GravityView support port
	 */
	static public function show_for_user( $user = 0 ) {

		if ( ! GVCommon::has_cap( 'gravityview_support_port' ) ) {
			return false;
		}

		$global_setting = gravityview()->plugin->settings->get( 'support_port' );

		if ( empty( $global_setting ) ) {
            return false;
		}

		// Get the per-user Support Port setting
		$user_pref = get_user_option( self::user_pref_name, $user );

		// Not configured; default to global setting (which is true at this point)
		if ( false === $user_pref ) {
			$user_pref = $global_setting;
		}

		return ! empty( $user_pref );
	}


	/**
	 * Update User Profile preferences for GravityView Support
	 *
	 * @since 1.5
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function update_user_meta_value( $user_id ) {
		if ( current_user_can( 'edit_user', $user_id ) && isset( $_POST[ self::user_pref_name ] ) ) {
			update_user_meta( $user_id, self::user_pref_name, intval( $_POST[ self::user_pref_name ] ) );
		}
	}

	/**
	 * Modify User Profile
	 *
	 * Modifies the output of profile.php to add GravityView Support preference
	 *
	 * @since 1.15
     * @since 1.17.5 Only show if global setting is active
	 *
	 * @param WP_User $user Current user info
	 *
	 * @return void
	 */
	public function user_field( $user ) {

		$global_setting = gravityview()->plugin->settings->get( 'support_port' );

		if ( empty( $global_setting ) ) {
            return;
		}

		/**
		 * @filter `gravityview/support_port/show_profile_setting` Should the "GravityView Support Port" setting be shown on user profiles?
		 * @since 1.15
		 * @param boolean $allow_profile_setting Default: `true`, if the user has the `gravityview_support_port` capability, which defaults to true for Contributors and higher
		 * @param WP_User $user Current user object
		 */
		$allow_profile_setting = apply_filters( 'gravityview/support_port/show_profile_setting', GVCommon::has_cap( 'gravityview_support_port' ), $user );

		if ( $allow_profile_setting && current_user_can( 'edit_user', $user->ID ) ) {
			?>
			<table class="form-table">
				<tbody>
					<tr class="user-gravityview-support-button-wrap">
						<th scope="row"><?php
							/* translators: "Support Port" can be translated as "Support Portal" or "Support Window" */
							_e( 'GravityView Support Port', 'gravityview' );
						?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php
										/* translators: "Support Port" can be translated as "Support Portal" or "Support Window" */
										_e( 'GravityView Support Port', 'gravityview' );
								?></span></legend>
								<label>
									<input name="<?php echo esc_attr( self::user_pref_name ); ?>" type="hidden" value="0"/>
									<input name="<?php echo esc_attr( self::user_pref_name ); ?>" type="checkbox" value="1" <?php checked( self::show_for_user( $user->ID ) ); ?> />
									<?php esc_html_e( 'Show GravityView Support Port when on a GravityView-related page', 'gravityview' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
		<?php }
	}
}

new GravityView_Support_Port;