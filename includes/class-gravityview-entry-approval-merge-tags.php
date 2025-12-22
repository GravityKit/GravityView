<?php
/**
 * @file class-gravityview-entry-moderation-merge-tags.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2023, Katz Web Services, Inc.
 *
 * @since 2.17
 */

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Handles approval links
 *
 * @since 2.17
 */
class GravityView_Entry_Approval_Merge_Tags {

	/**
	 * The name of the query arg used to pass token information to the approval URL.
	 * Example: ?gv_token=eyJpYXQiOjE2NzM0ODgw[...]
	 */
	const TOKEN_URL_ARG = 'gv_token';

	/**
	 * Default value for the expiration modifier.
	 */
	const DEFAULT_EXPIRATION_VALUE = 24;

	/**
	 * Default value for the expiration_unit modifier.
	 */
	const DEFAULT_EXPIRATION_UNIT = 'hours';

	const FORM_SETTINGS_KEY = 'gravityview_entry_moderation';

	const NOTICE_URL_ARG = 'gv_approval_link_result';

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

		add_action( 'init', array( $this, 'maybe_update_approved' ) );
		add_action( 'init', array( $this, 'maybe_show_approval_notice' ) );
	}

	/**
	 * Filters existing GF Form Settings Fields
	 *
	 * @since 2.17
	 *
	 * @param array $fields Array of sections and settings fields
	 * @param array $form GF Form
	 *
	 * @return array Modified array of sections and settings fields
	 */
	public function _filter_gform_form_settings_fields( $fields = array(), $form = array() ) {

		$global_default = gravityview()->plugin->settings->get( 'public_entry_moderation', '0' );

		$fields['restrictions']['fields'][] = array(
			'name'          => self::FORM_SETTINGS_KEY,
			'type'          => 'radio',
			'horizontal'    => true,
			// translators: %s is replaced with a setting label
			'label'         => sprintf( esc_html__( 'GravityView - %s', 'gk-gravityview' ), esc_html__( 'Enable Public Entry Moderation', 'gk-gravityview' ) ),
			'description'   => strtr(
				// translators: Do not translate the words inside the {} curly brackets; they are replaced.
				__( 'If enabled, adding {public} to {link}entry moderation merge tags{/link} will allow logged-out users to approve or reject entries. If disabled, all entry moderation actions require the user to be logged-in and have the ability to edit the entry.', 'gk-gravityview' ),
				array(
					'{public}' => '<code style="font-size: .9em">:public</code>',
					'{link}'   => '<a href="https://docs.gravitykit.com/article/904-entry-moderation-merge-tags" target="_blank" rel="noopener noreferrer">',
					'{/link}'  => '<span class="screen-reader-text"> ' . esc_html__( '(This link opens in a new window.)', 'gk-gravityview' ) . '</span></a>',
				)
			),
			'choices'       => array(
				array(
					'label' => _x( 'Enable', 'Setting: On or off', 'gk-gravityview' ),
					'value' => '1',
				),
				array(
					'label' => _x( 'Disable', 'Setting: On or off', 'gk-gravityview' ),
					'value' => '0',
				),
			),
			'default_value' => (string) $global_default,
		);

		return $fields;
	}

	/**
	 * Adds custom merge tags to merge tag options.
	 *
	 * @since 2.17
	 *
	 * @param array      $custom_merge_tags
	 * @param int        $form_id GF Form ID
	 * @param GF_Field[] $fields Array of fields in the form
	 * @param string     $element_id The ID of the input that Merge Tags are being used on
	 *
	 * @return array Modified merge tags
	 */
	public function _filter_gform_custom_merge_tags( $custom_merge_tags = array(), $form_id = 0, $fields = array(), $element_id = '' ) {

		$entry_moderation_merge_tags = array(
			array(
				'label' => __( 'Moderation: Approve entry link', 'gk-gravityview' ),
				'tag'   => '{gv_approve_entry}',
			),
			array(
				'label' => __( 'Moderation: Disapprove entry link', 'gk-gravityview' ),
				'tag'   => '{gv_disapprove_entry}',
			),
			array(
				'label' => __( 'Moderation: Reset entry approval link', 'gk-gravityview' ),
				'tag'   => '{gv_unapprove_entry}',
			),
		);

		return array_merge( $custom_merge_tags, $entry_moderation_merge_tags );
	}

	/**
	 * Matches the merge tag in replacement text for the field.
	 *
	 * @since 2.17
	 *
	 * @see replace_merge_tag Override replace_merge_tag() to handle any matches
	 *
	 * @param string $text Text to replace
	 * @param array  $form Gravity Forms form array
	 * @param array  $entry Entry array
	 * @param bool   $url_encode Whether to URL-encode output
	 *
	 * @return string Original text if {_custom_merge_tag} isn't found. Otherwise, replaced text.
	 */
	public function _filter_gform_replace_merge_tags( $text, $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		$matches = array();
		preg_match_all( '/{gv_((?:dis|un)?approve)_entry:?(?:(\d+)([d|h|m|s]))?:?(public)?:?(url)?}/', $text, $matches, PREG_SET_ORDER );

		// If there are no matches, return original text
		if ( empty( $matches ) ) {
			return $text;
		}

		if ( ! isset( $form[ self::FORM_SETTINGS_KEY ] ) ) {
			$form[ self::FORM_SETTINGS_KEY ] = gravityview()->plugin->settings->get( 'public_entry_moderation' );
		}

		return $this->replace_merge_tag( $matches, $text, $form, $entry, $url_encode, $esc_html );
	}

	/**
	 * Replaces merge tags
	 *
	 * @since 2.17
	 *
	 * @param array      $matches Array of Merge Tag matches found in text by preg_match_all
	 * @param string     $text Text to replace
	 * @param array|bool $form Gravity Forms form array. When called inside {@see GFCommon::replace_variables()} (now deprecated), `false`
	 * @param array|bool $entry Entry array.  When called inside {@see GFCommon::replace_variables()} (now deprecated), `false`
	 * @param bool       $url_encode Whether to URL-encode output
	 * @param bool       $esc_html Whether to apply `esc_html()` to output
	 *
	 * @return mixed
	 */
	protected function replace_merge_tag( $matches = array(), $text = '', $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {

		foreach ( $matches as $match ) {

			$full_tag         = $match[0];
			$action           = $match[1];
			$expiration_value = ! empty( $match[2] ) ? (int) $match[2] : self::DEFAULT_EXPIRATION_VALUE;
			$expiration_unit  = ! empty( $match[3] ) ? $match[3] : self::DEFAULT_EXPIRATION_UNIT;
			$privacy          = isset( $match[4] ) ? $match[4] : 'private';

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

			if ( false === (bool) \GV\Utils::get( $form, self::FORM_SETTINGS_KEY, false ) ) {
				$privacy = 'private';
			}

			$expiration_timestamp = strtotime( "+{$expiration_value} {$expiration_unit}" );
			$expiration_seconds   = $expiration_timestamp - time();

			$token = $this->get_token( $action, $expiration_timestamp, $privacy, $entry );

			if ( ! $token ) {
				continue;
			}

			$approval_link = $this->get_link_url( $token, $expiration_seconds, $privacy );

			$approval_link_text = GravityView_Entry_Approval_Status::get_action( $action . 'd' );

			$approval_link_params = [
				'url'     => $approval_link,
				'text'    => $approval_link_text,
				'form_id' => (int) $form['id'],
				'action'  => $action,
				'format'  => isset( $match[5] ) ? 'text' : 'html',
				'atts'    => [],
			];

			/**
			 * Modifies entry approval link parameters.
			 *
			 * @since 2.23
			 *
			 * @param array{url: string, text: string, form_id: int, action: string, format: string, atts: array} $approval_link_params Entry approval link parameters.
			 */
			$approval_link_params = wp_parse_args(
				apply_filters( 'gk/gravityview/entry/approval-link/params', $approval_link_params ),
				$approval_link_params
			);

			if ( 'text' === $approval_link_params['format'] ) {
				$approval_link = $approval_link_params['url'];
			} else {
				$approval_link = gravityview_get_link( esc_html( $approval_link_params['url'] ), esc_html( $approval_link_params['text'] ), $approval_link_params['atts'] );
			}

			$text = str_replace( $full_tag, $approval_link, $text );
		}

		return $text;
	}

	/**
	 * Generates a JWT token based on the merge tag parameters.
	 *
	 * @see https://jwt.io
	 *
	 * @since 2.17
	 *
	 * @param string|bool $action Action to be taken by the merge tag.
	 * @param int         $expiration_timestamp Timestamp when the token expires.
	 * @param string      $privacy Approval link privacy. Accepted values are 'private' or 'public'.
	 * @param array       $entry Entry array.
	 *
	 * @return string     Encrypted token.
	 */
	protected function get_token( $action = false, $expiration_timestamp = 0, $privacy = 'private', $entry = array() ) {

		if ( ! $action || ! $entry['id'] ) {
			return false;
		}

		if ( ! $expiration_timestamp ) {
			return false;
		}

		if ( ! $privacy ) {
			$privacy = 'private';
		}

		$approval_status = $this->get_approval_status( $action );

		if ( ! $approval_status ) {
			return false;
		}

		$jti                = uniqid();
		$expiration_seconds = $expiration_timestamp - time();

		$scopes = array(
			'entry_id'           => $entry['id'],
			'approval_status'    => $approval_status,
			'expiration_seconds' => $expiration_seconds,
			'privacy'            => $privacy,
		);

		$token_array = array(
			'iat'    => time(),
			'exp'    => $expiration_timestamp,
			'scopes' => $scopes,
			'jti'    => $jti,
		);

		$token = rawurlencode( base64_encode( json_encode( $token_array ) ) );

		$secret = wp_salt( 'nonce' );

		$sig = hash_hmac( 'sha256', $token, $secret );

		$token .= '.' . $sig;

		return $token;
	}

	/**
	 * Returns an approval status based on the provided action
	 *
	 * @since 2.17
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
	 * @since 2.17
	 *
	 * @param string|bool $token
	 * @param string      $privacy Approval link privacy. Accepted values are 'private' or 'public'.
	 *
	 * @return string Approval link URL
	 */
	protected function get_link_url( $token = false, $expiration_seconds = DAY_IN_SECONDS, $privacy = 'private' ) {

		if ( 'public' === $privacy ) {
			$base_url = home_url( '/' );
		} else {
			$base_url = admin_url( 'admin.php?page=gf_entries' );
		}

		$query_args = array();

		if ( ! empty( $token ) ) {
			$query_args[ self::TOKEN_URL_ARG ] = $token;
		}

		if ( 'private' === $privacy && DAY_IN_SECONDS >= (int) $expiration_seconds ) {
			$query_args['nonce'] = wp_create_nonce( self::TOKEN_URL_ARG );
		}

		return add_query_arg( $query_args, $base_url );
	}

	/**
	 * Checks page load for approval link token then maybe process it
	 *
	 * @since 2.17
	 *
	 * Expects a $_GET request with the following $_GET keys and values:
	 *
	 * @return void
	 * @global array $_GET {
	 * @type string $gv_token Approval link token
	 * @type string $nonce (optional) Nonce hash to be validated. Only available if $expiration_seconds is smaller than DAY_IN_SECONDS.
	 * }
	 */
	public function maybe_update_approved() {

		$token_string = GV\Utils::_GET( self::TOKEN_URL_ARG );

		if ( ! $token_string ) {
			return;
		}

		$token = $this->decode_token( $token_string );

		if ( is_wp_error( $token ) ) {

			gravityview()->log->error( 'Decoding the entry approval token failed.', array( 'data' => $token ) );

			wp_die( sprintf( __( 'Entry moderation failed: %s', 'gk-gravityview' ), $token->get_error_message() ) );
		}

		// Ensure the token is formatted properly.
		$is_valid_token = $this->is_token_valid( $token );

		if ( is_wp_error( $is_valid_token ) ) {

			gravityview()->log->error( 'Validating the entry approval token failed.', array( 'data' => $is_valid_token ) );

			wp_die( sprintf( __( 'Entry moderation failed: %s', 'gk-gravityview' ), $is_valid_token->get_error_message() ) );
		}

		$is_request_valid = $this->is_request_valid( $token );

		if ( is_wp_error( $is_request_valid ) ) {

			gravityview()->log->error( 'Validating the entry approval token failed.', array( 'data' => $is_request_valid ) );

			wp_die( sprintf( __( 'Entry moderation failed: %s', 'gk-gravityview' ), $is_request_valid->get_error_message() ) );
		}

		$scopes = $token['scopes'];

		$entry_id        = $scopes['entry_id'];
		$approval_status = $scopes['approval_status'];

		$entry = GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			gravityview()->log->error( 'Entry moderation failed: the entry was not found.', array( 'data' => $entry ) );

			wp_die( $entry->get_error_message() );
		}

		$form_id = $entry['form_id'];

		if ( 'private' === $scopes['privacy'] ) {
			$return_url = admin_url( '/admin.php?page=gf_entries&s=' . $entry_id . '&field_id=entry_id&operator=is&id=' . $form_id );
		} else {
			$return_url = home_url( '/' );
		}

		// Valid status
		if ( ! GravityView_Entry_Approval_Status::is_valid( $approval_status ) ) {

			gravityview()->log->error( 'Invalid approval status', array( 'data' => $scopes ) );

			wp_safe_redirect( add_query_arg( array( self::NOTICE_URL_ARG => 'error' ), $return_url ) );

			exit;
		}

		if ( empty( $entry_id ) || empty( $form_id ) ) {

			gravityview()->log->error( 'entry_id or form_id are empty.', array( 'data' => $scopes ) );

			wp_safe_redirect( add_query_arg( array( self::NOTICE_URL_ARG => 'error' ), $return_url ) );

			exit;
		}

		if ( 'private' === $scopes['privacy'] && ! GVCommon::has_cap( 'gravityview_moderate_entries', $entry_id ) ) {

			gravityview()->log->error( 'User does not have the `gravityview_moderate_entries` capability.' );

			wp_safe_redirect( add_query_arg( array( self::NOTICE_URL_ARG => 'error' ), $return_url ) );

			exit;
		}

		$this->update_approved( $entry_id, $approval_status, $form_id, $scopes, $return_url );
	}

	/**
	 * Verifies the token
	 *
	 * @since 2.17
	 *
	 * @param array $token
	 *
	 * @return bool|WP_Error
	 */
	protected function is_request_valid( $token ) {

		if ( 'private' === $token['scopes']['privacy'] && ! is_user_logged_in() ) {
			return new WP_Error( 'user_not_logged_in', __( 'You are not allowed to perform this operation.', 'gk-gravityview' ) );
		}

		if ( $token['exp'] < time() ) {
			gravityview()->log->error( 'The entry moderation link expired.', array( 'data' => $is_valid_token ) );

			return new WP_Error( 'link_expired', esc_html__( 'The link has expired.', 'gk-gravityview' ) );
		}

		// Since nonces are only valid for 24 hours, we only check the nonce if the token is valid for less than 24 hours.
		if ( 'private' === $token['scopes']['privacy'] && DAY_IN_SECONDS >= $token['scopes']['expiration_seconds'] ) {

			if ( ! isset( $_REQUEST['nonce'] ) ) {
				gravityview()->log->error( 'Entry moderation failed: No nonce was set for entry approval.' );

				return new WP_Error( 'missing_nonce', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
			}

			$nonce_validation = wp_verify_nonce( GV\Utils::_GET( 'nonce' ), self::TOKEN_URL_ARG );

			if ( ! $nonce_validation ) {
				gravityview()->log->error( 'Entry moderation failed: Nonce was invalid.', array( 'data' => $nonce_validation ) );

				return new WP_Error( 'invalid_nonce', esc_html__( 'The link has expired.', 'gk-gravityview' ) );
			}
		}

		return true;
	}

	/**
	 * Checks page load for approval link result then maybe show notice
	 *
	 * @since 2.17
	 *
	 * Expects a $_GET request with the following $_GET keys and values:
	 *
	 * @return void
	 * @global array $_GET {
	 * @type string $gv_approval_link_result Approval link result
	 * }
	 */
	public function maybe_show_approval_notice() {

		$result          = GV\Utils::_GET( self::NOTICE_URL_ARG );
		$approval_status = \GV\Utils::_GET( 'approval_status' );
		$entry_id        = \GV\Utils::_GET( 'entry_id' );

		if ( ! $result || ! $approval_status || ! $entry_id ) {
			return;
		}

		$approval_label = GravityView_Entry_Approval_Status::get_label( (int) $approval_status );
		$approval_label = mb_strtolower( $approval_label );

		if ( 'success' === $result ) {

			// translators: Do not translate the words inside the {} curly brackets; they are replaced.
			$message = esc_html__( 'Success: Entry #{entry_id} has been {approval_label}.', 'gk-gravityview' );

			$css_class = 'updated';
		} else {

			// translators: Do not translate the words inside the {} curly brackets; they are replaced.
			$message = esc_html__( 'There was an error updating entry #{entry_id}.', 'gk-gravityview' );

			$css_class = 'error';
		}

		$message = strtr(
			$message,
			array(
				'{entry_id}'       => esc_html( $entry_id ),
				'{approval_label}' => esc_html( $approval_label ),
			)
		);

		$message = \GVCommon::generate_notice( wpautop( $message ), $css_class );

		if ( is_admin() ) {
			echo $message;
		} else {
			wp_die( $message );
		}
	}

	/**
	 * Decodes received token to its original form.
	 *
	 * @since 2.17
	 *
	 * @param string|bool $token
	 *
	 * @return array|WP_Error Original scopes or WP Error object
	 */
	protected function decode_token( $token = false ) {

		if ( ! $token ) {
			return new WP_Error( 'missing_token', __( 'Invalid security token.', 'gk-gravityview' ) );
		}

		$parts = explode( '.', $token );

		if ( count( $parts ) < 2 ) {
			return new WP_Error( 'missing_period', __( 'Invalid security token.', 'gk-gravityview' ) );
		}

		/**
		 * @param string $body_64 $parts[0]
		 * @param string $sig $parts[1]
		 */
		list( $body_64, $sig ) = $parts;

		if ( empty( $sig ) ) {
			return new WP_Error( 'approve_link_no_signature', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
		}

		$secret            = wp_salt( 'nonce' );
		$verification_sig  = hash_hmac( 'sha256', $body_64, $secret );
		$verification_sig2 = hash_hmac( 'sha256', rawurlencode( $body_64 ), $secret );

		if ( ! hash_equals( $sig, $verification_sig ) && ! hash_equals( $sig, $verification_sig2 ) ) {
			return new WP_Error( 'approve_link_failed_signature_verification', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
		}

		$body_json     = base64_decode( $body_64 );
		$decoded_token = json_decode( $body_json, true );

		if ( empty( $body_json ) || empty( $decoded_token ) ) {
			$decoded_token = base64_decode( urldecode( $body_64 ) );
		}

		if ( empty( $decoded_token ) ) {
			return new WP_Error( 'approve_link_failed_base64_decode', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
		}

		return $decoded_token;
	}

	/**
	 * Validates an approval token
	 *
	 * @since 2.17
	 *
	 * @param array $token
	 *
	 * @return true|WP_Error Token is valid or there was an error.
	 */
	protected function is_token_valid( array $token ) {

		$required_keys = array(
			'jti',
			'exp',
			'scopes',
		);

		foreach ( $required_keys as $required_key ) {
			if ( ! isset( $token[ $required_key ] ) ) {
				return new WP_Error( 'approve_link_no_' . $required_key, esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
			}
		}

		$required_scopes = array(
			'expiration_seconds',
			'privacy',
			'entry_id',
			'approval_status',
		);

		foreach ( $required_scopes as $required_scope ) {
			if ( ! isset( $token['scopes'][ $required_scope ] ) ) {
				return new WP_Error( 'approve_link_no_' . $required_scope . '_scope', esc_html__( 'The link is invalid.', 'gk-gravityview' ) );
			}
		}

		return true;
	}

	/**
	 * Updates the entry approval status and redirects to $return_url.
	 *
	 * @param int    $entry_id Entry ID.
	 * @param int    $approval_status Approval status.
	 * @param int    $form_id Form ID.
	 * @param array  $scopes Token scopes to be passed to the return URL and used in {@see maybe_show_approval_notice()}.
	 * @param string $return_url Url to redirect to once moderation happens.
	 *
	 * @return void
	 */
	protected function update_approved( $entry_id, $approval_status, $form_id, $scopes, $return_url ) {

		$result = GravityView_Entry_Approval::update_approved( $entry_id, $approval_status, $form_id );

		$query_args = $scopes;

		$query_args[ self::NOTICE_URL_ARG ] = $result ? 'success' : 'error';

		$return_url = add_query_arg( $query_args, $return_url );

		/**
		 * Modifies the return URL after entry approval.
		 *
		 * @since 2.18.7
		 *
		 * @param string $return_url      URL to redirect to once moderation happens.
		 * @param int    $entry_id        Entry ID.
		 * @param int    $approval_status Approval status.
		 * @param int    $form_id         Form ID.
		 * @param array  $scopes          Token scopes to be passed to the return URL and used in {@see maybe_show_approval_notice()}.
		 */
		$return_url = apply_filters( 'gk/gravityview/approve-link/return-url', $return_url, $entry_id, $approval_status, $form_id, $scopes );

		wp_safe_redirect( esc_url_raw( $return_url ) );

		exit;
	}
}

new GravityView_Entry_Approval_Merge_Tags();
