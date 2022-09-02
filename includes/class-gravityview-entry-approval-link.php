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
 * Handle approval links
 *
 * @since 2.14.8
 */
class GravityView_Entry_Approval_Link {

	public function __construct() {
		$this->add_hooks();
	}

	/**
	 * Add actions and filters related to entry approval links
	 *
	 * @return void
	 */
	private function add_hooks() {
		add_filter( 'gform_custom_merge_tags', array( $this, '_filter_gform_custom_merge_tags' ), 10, 4 );
		add_filter( 'gform_replace_merge_tags', array( $this, '_filter_gform_replace_merge_tags' ), 10, 7 );
		add_action( 'init', array( $this, '_filter_init' ) );
	}

	/**
	 * Add custom merge tags to merge tag options. DO NOT OVERRIDE.
	 *
	 * @internal Not to be overridden by fields
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
	 * Add custom Merge Tags to Merge Tag options, if custom Merge Tags exist
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
	 * Match the merge tag in replacement text for the field.  DO NOT OVERRIDE.
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
		preg_match_all( '/{gv_(.+)_entry:?([0-9]+)?:?(.+)?}/', $text, $matches, PREG_SET_ORDER );

		// If there are no matches, return original text
		if ( empty( $matches ) ) {
			return $text;
		}

		return $this->replace_merge_tag( $matches, $text, $form, $entry, $url_encode, $esc_html );
	}

	/**
	 * Run GravityView filters when using GFCommon::replace_variables()
	 *
	 * Instead of adding multiple hooks, add all hooks into this one method to improve speed
	 *
	 * @since 2.14.8
	 *
	 * @see GFCommon::replace_variables()
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

			list( $full_tag, $action, $expiration_hours, $permissions ) = $match;

			$token = $this->get_token( $action, $expiration_hours, $permissions, $entry );

			if ( ! $token ) {
				continue;
			}

			$link_url = $this->get_link_url( $token, $privacy );

			$link = sprintf( '<a href="%s">%s</a>', esc_url( $link_url ), ucfirst( $action ) );

			$text = str_replace( $full_tag, $link, $text );
		}

		return $text;
	}

	/**
	protected function get_token( $action = false, $expiration_hours = 24, $privacy = 'private', $entry = array() ) {

		if ( ! $action || ! $entry['id'] ) {
			return false;
		}

		if ( ! $expiration_hours ) {
			$expiration_hours = 24;
		}

		if ( ! $privacy ) {
			$privacy = 'private';
		}

		$approval_status = $this->get_approval_status( $action );

		if ( ! $approval_status ) {
			return false;
		}

		$scopes = array(
			'entry_id'         => $entry['id'],
			'approval_status'  => $approval_status,
			'expiration_hours' => $expiration_hours,
			'privacy'          => $privacy,
		);

		$jti                  = uniqid();
		$expiration_timestamp = strtotime( '+' . (int) $expiration_hours . ' hours' );

		$token_array = array(
			'iat'    => time(),
			'exp'    => $expiration_timestamp,
			'scopes' => $scopes,
			'jti'    => $jti,
		);

		$token = rawurlencode( base64_encode( json_encode( $token_array ) ) );

		$secret = get_option( 'gravityview_token_secret' );
		if ( empty( $secret ) ) {
			$secret = wp_generate_password( 64 );
			update_option( 'gravityview_token_secret', $secret );
		}

		$sig = hash_hmac( 'sha256', $token, $secret );

		$token .= '.' . $sig;

		return $token;
	}

	/**
	protected function get_approval_status( $action = false ) {

		if ( ! $action ) {
			return false;
		}

		$key    = GravityView_Entry_Approval_Status::get_key( $action . 'd' );
		$values = GravityView_Entry_Approval_Status::get_values();

		return $values[ $key ];
	}

	/**
	 * Generate approval link URL
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
			$base_url = admin_url( '/' );
		}

		if ( ! empty( $token ) ) {
			$query_args['gv_token'] = $token;
		}

		$url = add_query_arg( $query_args, $base_url );

		return $url;
	}
	public function _filter_init() {

		if ( GV\Utils::_GET( 'gv_token' ) ) {
			$token_array = $this->decode_token( GV\Utils::_GET( 'gv_token' ) );

			if ( empty( $token_array ) || is_wp_error( $token_array ) ) {
				return false;
			}

			$scopes = $token_array['scopes'];

			if ( empty( $scopes['entry_id'] ) || empty( $scopes['approval_status'] ) || empty( $scopes['privacy'] ) ) {
				return false;
			}

			if ( 'private' === $scopes['privacy'] && ! is_user_logged_in() ) {
				return false;
			}
	protected function decode_token( $token = false ) {

		if ( ! $token ) {
			return false;
		}

		if ( ! $this->validate_token( $token ) ) {

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

		if ( empty( json_decode( $body_json, true ) ) ) {
			$body_json = base64_decode( urldecode( $body_64 ) );
		}

		return json_decode( $body_json, true );
	}
	protected function validate_token( $token = false ) {

		if ( ! $token ) {
			return false;
		}

		$parts = explode( '.', $token );
		if ( count( $parts ) < 2 ) {
			return false;
		}

		list( $body_64, $sig ) = $parts;

		if ( empty( $sig ) ) {
			return false;
		}

		$secret = get_option( 'gravityview_token_secret' );
		if ( empty( $secret ) ) {
			return false;
		}


		$verification_sig  = hash_hmac( 'sha256', $body_64, $secret );
		$verification_sig2 = hash_hmac( 'sha256', rawurlencode( $body_64 ), $secret );

		if ( ! hash_equals( $sig, $verification_sig ) && ! hash_equals( $sig, $verification_sig2 ) ) {
			return false;
		}

		$body_json = base64_decode( $body_64 );
		if ( empty( $body_json ) || empty( json_decode( $body_json, true ) ) ) {
			$body_json = base64_decode( urldecode( $body_64 ) );
			if ( empty( $body_json ) ) {
				return false;
			}
		}

		$token = json_decode( $body_json, true );

		if ( ! isset( $token['jti'] ) ) {
			return false;
		}

		if ( ! isset( $token['exp'] ) || $token['exp'] < time() ) {
			return false;
		}

		if ( ! isset( $token['scopes'] ) ) {
			return false;
		}

		if ( ! isset( $token['scopes']['expiration_hours'] ) ) {
			return false;
		}

		if ( 24 > $token['scopes']['expiration_hours'] ) {

			if ( ! isset( $_GET['nonce'] ) ) {
				return false;
			}

			if ( ! wp_verify_nonce( $_GET['nonce'], 'gv_token' ) ) {
				return false;
			}
		}

		return true;
	}
		}
	}
	}
		}
}

new GravityView_Entry_Approval_Link;
