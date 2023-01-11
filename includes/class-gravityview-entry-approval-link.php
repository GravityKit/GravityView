<?php
/**
 * @file class-gravityview-entry-approval-link.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
 * @link      https://gravityview.co
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 2.14.8
 */

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Handles approval links
 *
 * @since 2.14.8
 */
class GravityView_Entry_Approval_Link {

	/**
	 * Default value for the expiration modifier.
	 */
	const EXPIRATION_HOURS = 24;

	/**
	 * Default value for the expiration_unit modifier.
	 */
	const EXPIRATION_UNIT = 'hours';

	/**
	 * Default value for the privacy modifier.
	 */
	const DEFAULT_PRIVACY = 'private';

	const URL_ARG = 'gv_approval_link_result';

	/**
	 * Initialization.
	 */
	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * Adds actions and filters related to entry approval links
	 *
	 * @return void
	 */
	private function add_hooks() {
		add_filter( 'gform_form_settings_fields', array( $this, '_filter_gform_form_settings_fields' ), 10, 2 );
		add_filter( 'gform_custom_merge_tags', array( $this, '_filter_gform_custom_merge_tags' ), 10, 4 );
		add_filter( 'gform_replace_merge_tags', array( $this, '_filter_gform_replace_merge_tags' ), 10, 7 );
		add_action( 'init', array( $this, '_action_init' ) );
	}

	/**
	 * Filters existing GF Form Settings Fields
	 *
	 * @since 2.14.8
	 *
	 * @param array $fields Array of sections and settings fields
	 * @param array $form GF Form
	 *
	 * @return array Modified array of sections and settings fields
	 */
	public function _filter_gform_form_settings_fields( $fields = array(), $form = array() ) {

		$fields = $this->add_form_settings( $fields, $form );

		return $fields;
	}

	/**
	 * Adds new form settings
	 *
	 * @since 2.14.8
	 *
	 * @param array $fields Array of sections and settings fields
	 * @param array $form GF Form
	 *
	 * @return array Modified array of sections and settings fields
	 */
	protected function add_form_settings( $fields = array(), $form = array() ) {

		$fields['restrictions']['fields'][] = array(
			'name'       => 'publicApprovalLink',
			'type'       => 'radio',
			'horizontal' => true,
			'label'      => __( 'Public Approval Link', 'gravityview' ),
			'tooltip'    => __( 'Set this to ON to enable public modifier on approval merge tags.', 'gravityview' ),
			'choices'    => array(
				array(
					'label' => _x( 'On', 'Setting: On or off', 'gravityview' ),
					'value' => '1',
				),
				array(
						'label' => _x( 'Off', 'Setting: On or off', 'gravityview' ),
						'value' => '0',
				),
			),
			'default_value' => gravityview()->plugin->settings->get( 'public-approval-link', '0' ),
		);

		return $fields;
	}

	/**
	 * Adds custom merge tags to merge tag options.
	 *
	 * @since 2.14.8
	 *
	 * @param array $custom_merge_tags
	 * @param int $form_id GF Form ID
	 * @param GF_Field[] $fields Array of fields in the form
	 * @param string $element_id The ID of the input that Merge Tags are being used on
	 *
	 * @return array Modified merge tags
	 */
	public function _filter_gform_custom_merge_tags( $custom_merge_tags = array(), $form_id = 0, $fields = array(), $element_id = '' ) {

		$form = GVCommon::get_form( $form_id );

		$field_merge_tags = $this->custom_merge_tags( $form, $fields );

		return array_merge( $custom_merge_tags, $field_merge_tags );
	}

	/**
	 * Adds custom Merge Tags to Merge Tag options, if custom Merge Tags exist
	 *
	 * @since 2.14.8
	 *
	 * @param array $form GF Form array
	 * @param GF_Field[] $fields Array of fields in the form
	 *
	 * @return array Merge tag array with `label` and `tag` keys
	 */
	protected function custom_merge_tags( $form = array(), $fields = array() ) {

		$merge_tags = array(
			array(
				'label' => __( 'Link to approve an entry', 'gravityview' ),
				'tag' => '{gv_approve_entry}',
			),
			array(
				'label' => __( 'Link to disapprove an entry', 'gravityview' ),
				'tag' => '{gv_disapprove_entry}',
			),
			array(
				'label' => __( 'Link to unapprove an entry', 'gravityview' ),
				'tag' => '{gv_unapprove_entry}',
			),
		);

		return $merge_tags;
	}

	/**
	 * Matches the merge tag in replacement text for the field.
	 *
	 * @see replace_merge_tag Override replace_merge_tag() to handle any matches
	 *
	 * @since 2.14.8
	 *
	 * @param string $text Text to replace
	 * @param array $form Gravity Forms form array
	 * @param array $entry Entry array
	 * @param bool $url_encode Whether to URL-encode output
	 *
	 * @return string Original text if {_custom_merge_tag} isn't found. Otherwise, replaced text.
	 */
	public function _filter_gform_replace_merge_tags( $text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false  ) {

		$matches = array();
		preg_match_all( '/{gv_((?:dis|un)?approve)_entry:?(?:(\d+)([d|h|m|s]))?:?(public)?}/', $text, $matches, PREG_SET_ORDER );

		// If there are no matches, return original text
		if ( empty( $matches ) ) {
			return $text;
		}

		if ( ! isset( $form['publicApprovalLink'] ) ) {
			$form['publicApprovalLink'] = gravityview()->plugin->settings->get( 'public-approval-link' );
		}

		return $this->replace_merge_tag( $matches, $text, $form, $entry, $url_encode, $esc_html );
	}

	/**
	 * Replaces merge tags
	 *
	 * @since 2.14.8
	 *
	 * @param array $matches Array of Merge Tag matches found in text by preg_match_all
	 * @param string $text Text to replace
	 * @param array|bool $form Gravity Forms form array. When called inside {@see GFCommon::replace_variables()} (now deprecated), `false`
	 * @param array|bool $entry Entry array.  When called inside {@see GFCommon::replace_variables()} (now deprecated), `false`
	 * @param bool $url_encode Whether to URL-encode output
	 * @param bool $esc_html Whether to apply `esc_html()` to output
	 *
	 * @return mixed
	 */
	protected function replace_merge_tag( $matches = array(), $text = '', $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		foreach( $matches as $match ) {

			$full_tag         = $match[0];
			$action           = $match[1];
			$expiration_value = ! empty( $match[2] ) ? (int) $match[2] : self::EXPIRATION_HOURS;
			$expiration_unit = ! empty( $match[3] ) ? $match[3] : self::EXPIRATION_UNIT;
			$privacy = isset( $match[4] ) ? $match[4] : self::DEFAULT_PRIVACY;

			switch ( $expiration_unit ) {
				case 'd':
					$expiration_unit = 'days';
					break;
				case 'h':
				default:
					$expiration_unit = 'hours';
					break;
				case 'm':
					$expiration_unit = 'minutes';
					break;
				case 's': // Seconds should really only be used for testing purposes :-) But it's here if you need it.
					$expiration_unit = 'seconds';
					break;
			}

			if ( false === (bool) $form['publicApprovalLink'] ) {
				$privacy = self::DEFAULT_PRIVACY;
			}

			$token = $this->get_token( $action, $expiration_value, $expiration_unit, $privacy, $entry );

			if ( ! $token ) {
				continue;
			}

			$link_url = $this->get_link_url( $token, $privacy );


			if ( self::EXPIRATION_HOURS > (int) $expiration_hours ) {
				$link_url = add_query_arg( array( 'nonce' => wp_create_nonce( 'gv_token' ) ), $link_url );
			}

			$link_labels = array(
				'approve'    => _x( 'Approve', 'Change entry approval status to approved.', 'gravityview' ),
				'disapprove' => _x( 'Disapprove', 'Change entry approval status to disapproved.', 'gravityview' ),
				'unapprove'  => _x( 'Unapprove', 'Change entry approval status to unapproved.', 'gravityview' ),
			);

			$link = sprintf( '<a href="%s">%s</a>', esc_url_raw( $link_url ), esc_html( $link_labels[ $action ] ) );

			$text = str_replace( $full_tag, $link, $text );
		}

		return $text;
	}

	/**
	 * Generates token from merge tag parameters
	 *
	 * @since 2.14.8
	 *
	 * @param string|bool $action Action to be taken by the merge tag.
	 * @param int         $expiration_value Amount of hours the approval link is valid.
	 * @param string      $expiration_unit Unit of time for $expiration_value. Accepts time units allowed by {@see strtotime()} (`weeks`, `days`, `hours`, `minutes`, `seconds`).
	 * @param string      $privacy Approval link privacy. Accepted values are 'private' or 'public'.
	 * @param array       $entry Entry array.
	 *
	 * @return array Encrypted hash
	 */
	protected function get_token( $action = false, $expiration_value = 24, $expiration_unit = 'hours', $privacy = 'private', $entry = array() ) {

		if ( ! $action || ! $entry['id'] ) {
			return false;
		}

		if ( ! $expiration_value ) {
			$expiration_value = self::EXPIRATION_HOURS;
		}

		if ( ! $privacy ) {
			$privacy = self::DEFAULT_PRIVACY;
		}

		$approval_status = $this->get_approval_status( $action );

		if ( ! $approval_status ) {
			return false;
		}

		$scopes = array(
			'entry_id'         => $entry['id'],
			'approval_status'  => $approval_status,
			'expiration_hours' => $expiration_value,
			'privacy'          => $privacy,
		);

		$jti                  = uniqid();
		$expiration_timestamp = strtotime( '+' . (int) $expiration_value . ' ' . $expiration_unit );

		$token_array = array(
			'iat'    => time(),
			'exp'    => $expiration_timestamp,
			'scopes' => $scopes,
			'jti'    => $jti,
		);

		$token = rawurlencode( base64_encode( json_encode( $token_array ) ) );

		$secret = get_option( 'gravityview_token_secret' );
		if ( empty( $secret ) ) {
			$secret = wp_salt( 'nonce' );
			update_option( 'gravityview_token_secret', $secret, false );
		}

		$sig = hash_hmac( 'sha256', $token, $secret );

		$token .= '.' . $sig;

		return $token;
	}

	/**
	 * Returns an approval status based on the provided action
	 *
	 * @since 2.14.8
	 *
	 * @param string|bool $action
	 *
	 * @return int Value of respective approval status
	 */
	protected function get_approval_status( $action = false ) {

		if ( ! $action ) {
			return false;
		}

		$key    = GravityView_Entry_Approval_Status::get_key( $action . 'd' );
		$values = GravityView_Entry_Approval_Status::get_values();

		return $values[ $key ];
	}

	/**
	 * Generates an approval link URL
	 *
	 * @since 2.14.8
	 *
	 * @param string|bool $token
	 * @param string      $privacy Approval link privacy. Accepted values are 'private' or 'public'.
	 *
	 * @return string Approval link URL
	 */
	protected function get_link_url( $token = false, $privacy = 'private' ) {

		if ( 'public' === $privacy ) {
			$base_url = home_url( '/' );
		} else {
			$base_url = admin_url( 'admin.php?page=gf_entries' );
		}

		$query_args = array();

		if ( ! empty( $token ) ) {
			$query_args['gv_token'] = $token;
		}

		$url = add_query_arg( $query_args, $base_url );

		return $url;
	}

	/**
	 * Checks page load for known parameters
	 *
	 * @since 2.14.8
	 *
	 * Expects a $_GET request with the following $_GET keys and values:
	 *
	 * @global array $_GET {
	 * @type string $gv_token Approval link token
	 * @type string $nonce (optional) Nonce hash to be validated. Only available if $expiration_hours is smaller than 24.
	 * }
	 *
	 * @return void
	 */
	public function _action_init() {

		if ( ! GV\Utils::_GET( 'gv_token' ) && ! GV\Utils::_GET( self::URL_ARG ) ) {
			return;
		}

		$this->maybe_update_approved();

		add_action( 'admin_notices', array( $this, 'maybe_show_approval_notice' ) );
		add_action( 'template_redirect', array( $this, 'maybe_show_approval_notice' ) );
	}

	/**
	 * Checks page load for approval link token then maybe process it
	 *
	 * @since 2.14.8
	 *
	 * Expects a $_GET request with the following $_GET keys and values:
	 *
	 * @global array $_GET {
	 * @type string $gv_token Approval link token
	 * @type string $nonce (optional) Nonce hash to be validated. Only available if $expiration_hours is smaller than 24.
	 * }
	 *
	 * @return void
	 */
	protected function maybe_update_approved() {

		if ( ! GV\Utils::_GET( 'gv_token' ) ) {
			return;
		}

		$token_array = $this->decode_token( GV\Utils::_GET( 'gv_token' ) );

		if ( is_wp_error( $token_array ) ) {
			echo \GVCommon::generate_notice( $token_array->get_error_message(), 'gv-error' );

			return;
		}

		if ( empty( $token_array ) ) {
			echo \GVCommon::generate_notice( __( 'Invalid request.', 'gravityview' ) , 'gv-error' );

			return;
		}

		$scopes = $token_array['scopes'];

		if ( empty( $scopes['entry_id'] ) || empty( $scopes['approval_status'] ) || empty( $scopes['privacy'] ) ) {
			echo \GVCommon::generate_notice( __( 'Invalid request.', 'gravityview' ) , 'gv-error' );

			return;
		}

		if ( self::DEFAULT_PRIVACY === $scopes['privacy'] && ! is_user_logged_in() ) {
			echo \GVCommon::generate_notice( __( 'You are not allowed to perform this operation.', 'gravityview' ) , 'gv-error' );

			return;
		}

		$this->update_approved( $scopes );
	}

	/**
	 * Checks page load for approval link result then maybe show notice
	 *
	 * @since 2.14.8
	 *
	 * Expects a $_GET request with the following $_GET keys and values:
	 *
	 * @global array $_GET {
	 * @type string $gv_approval_link_result Approval link result
	 * }
	 *
	 * @return void
	 */
	public function maybe_show_approval_notice() {

		if ( ! GV\Utils::_GET( self::URL_ARG ) ) {
			return;
		}

		$result = GV\Utils::_GET( self::URL_ARG );

		if ( 'success' === $result ) {
			$message = \GVCommon::generate_notice( wpautop( esc_html__( 'Entry approval updated!', 'gravityview' ) ), 'updated' );
		}

		elseif ( 'error' === $result ) {
			$message = \GVCommon::generate_notice( wpautop( esc_html__( 'Error updating approval.', 'gravityview' ) ), 'error' );
		}

		if ( is_admin() ) {
			echo $message;
		} else {
			wp_die( $message );
		}

	}

	/**
	 * Decodes received token to its original form.
	 *
	 * @since 2.14.8
	 *
	 * @param string|bool $token
	 *
	 * @return array|WP_Error Original scopes or WP Error object
	 */
	protected function decode_token( $token = false ) {

		if ( ! $token ) {
			return false;
		}

		$token_is_valid = $this->validate_token( $token );

		if ( is_wp_error( $token_is_valid ) ) {

			gravityview()->log->error( 'Security check failed.', array( 'data' => $token ) );

			return new WP_Error( 'securiy_check_failed', __( 'Security check failed.', 'gravityview' ) );
		}

		$parts = explode( '.', $token );

		if ( count( $parts ) < 2 ) {
			return false;
		}

		$body_64 = $parts[0];

		$body_json = base64_decode( $body_64 );

		if ( empty( $body_json ) ) {
			return false;
		}

		// TODO: Determine why/if this is necessary.
		if ( empty( json_decode( $body_json, true ) ) ) {
			$body_json = base64_decode( urldecode( $body_64 ) );
		}

		return json_decode( $body_json, true );
	}

	/**
	 * Validates an approval token
	 *
	 * @since 2.14.8
	 *
	 * @param string|boold $token
	 *
	 * @return true|WP_Error Token is valid or there was an error.
	 */
	protected function validate_token( $token = false ) {

		if ( ! $token ) {
			return false;
		}

		$parts = explode( '.', $token );
		if ( count( $parts ) < 2 ) {
			return false;
		}

		/**
		 * @param string $body_64 $parts[0]
		 * @param string $sig $parts[1]
		 */
		list( $body_64, $sig ) = $parts;

		if ( empty( $sig ) ) {
			return new WP_Error( 'approve_link_no_signature', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
		}

		$secret = get_option( 'gravityview_token_secret' );

		if ( empty( $secret ) ) {
			return new WP_Error( 'approve_link_no_scopes', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
		}

		$verification_sig  = hash_hmac( 'sha256', $body_64, $secret );
		$verification_sig2 = hash_hmac( 'sha256', rawurlencode( $body_64 ), $secret );

		if ( ! hash_equals( $sig, $verification_sig ) && ! hash_equals( $sig, $verification_sig2 ) ) {
			return new WP_Error( 'approve_link_failed_signature_verification', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
		}

		$body_json = base64_decode( $body_64 );
		if ( empty( $body_json ) || empty( json_decode( $body_json, true ) ) ) {
			$body_json = base64_decode( urldecode( $body_64 ) );
			if ( empty( $body_json ) ) {
				return new WP_Error( 'approve_link_failed_base64_decode', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
			}
		}

		$token = json_decode( $body_json, true );

		if ( ! isset( $token['jti'] ) ) {
			return new WP_Error( 'approve_link_no_jti', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
		}

		if ( ! isset( $token['exp'] ) || $token['exp'] < time() ) {
			return new WP_Error( 'approve_link_expired', esc_html__( 'The link has expired.', 'gk-gravityview' ) );
		}

		if ( ! isset( $token['scopes'] ) ) {
			return new WP_Error( 'approve_link_no_scopes', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
		}

		if ( ! isset( $token['scopes']['expiration_hours'] ) ) {
			return new WP_Error( 'approve_link_no_expiration', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
		}

		if ( self::EXPIRATION_HOURS > $token['scopes']['expiration_hours'] ) {

			if ( ! isset( $_GET['nonce'] ) ) {
				return new WP_Error( 'approve_link_no_nonce', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
			}

			if ( ! wp_verify_nonce( GV\Utils::_GET( 'nonce' ), 'gv_token' ) ) {
				return new WP_Error( 'approve_link_invalid_nonce', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
			}
		}

		return true;
	}

	/**
	 * Updates approval status
	 *
	 * @since 2.14.8
	 *
	 * @param array $scopes
	 *
	 * @return void Output success or error messages to user on redirect.
	 */
	protected function update_approved( $scopes = array() ) {

		if ( empty( $scopes ) ) {
			return false;
		}

		$entry_id        = $scopes['entry_id'];
		$approval_status = $scopes['approval_status'];

		$entry      = GFAPI::get_entry( $entry_id );
		$form_id    = $entry['form_id'];
		$return_url = self::DEFAULT_PRIVACY === $scopes['privacy'] ? admin_url( '/admin.php?page=gf_entries&id=' . $form_id ) : home_url( '/' );

		// Valid status
		if ( ! GravityView_Entry_Approval_Status::is_valid( $approval_status ) ) {

			gravityview()->log->error( 'Invalid approval status', array( 'data' => $scopes ) );

			wp_safe_redirect( add_query_arg( array( self::URL_ARG => 'error' ), $return_url ) );
			exit;

		}

		// Valid values
		elseif ( empty( $entry_id ) || empty( $form_id ) ) {

			gravityview()->log->error( 'entry_id or form_id are empty.', array( 'data' => $scopes ) );

			wp_safe_redirect( add_query_arg( array( self::URL_ARG => 'error' ), $return_url ) );
			exit;

		}

		// Has capability
		elseif ( self::DEFAULT_PRIVACY === $scopes['privacy'] && ! GVCommon::has_cap( 'gravityview_moderate_entries', $entry_id ) ) {

			gravityview()->log->error( 'User does not have the `gravityview_moderate_entries` capability.' );

			wp_safe_redirect( add_query_arg( array( self::URL_ARG => 'error' ), $return_url ) );
			exit;

		}

		$result = GravityView_Entry_Approval::update_approved( $entry_id, $approval_status, $form_id );

		$return_url = add_query_arg( array( self::URL_ARG => $result ? 'success' : 'error' ), $return_url );

		wp_safe_redirect( esc_url_raw( $return_url ) );
		exit;
	}
}

new GravityView_Entry_Approval_Link;
