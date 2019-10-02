<?php
/**
 * @file class-gravityview-field-unsubscribe.php
 * @since develop
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Unsubscribe extends GravityView_Field {

	var $name = 'unsubscribe';

	var $group = 'gravityview';

	var $is_searchable = false;

	var $contexts = array( 'single', 'multiple' );

	public function __construct() {
		$this->label = esc_html__( 'Unsubscribe', 'gravityview' );
		$this->description =  esc_attr__( 'Unsubscribe from a Payment-based entry.', 'gravityview' );
		
		$this->add_hooks();
		
		parent::__construct();
	}
	
	function add_hooks() {
		add_filter( 'gravityview_entry_default_fields', array( $this, 'filter_gravityview_entry_default_field' ), 10, 3 );

		add_filter( 'gravityview/field/is_visible', array( $this, 'maybe_not_visible' ), 10, 2 );

		add_filter( 'gravityview_field_entry_value_unsubscribe', array( $this, 'modify_entry_value_unsubscribe' ), 10, 4 );
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		unset( $field_options['only_loggedin'] );

		unset( $field_options['new_window'] );

		unset( $field_options['show_as_link'] );

		$add_options['unsub_all'] = array(
			'type'       => 'checkbox',
			'label'      => __( 'Allow admins to unsubscribe', 'gravityview' ),
			'desc'       => __( 'Allow users with `gravityforms_edit_entries` to cancel subscriptions', 'gravityview' ),
			'value'      => false,
			'merge_tags' => false,
		);

		return $field_options + $add_options;
	}

	public function maybe_not_visible( $visible, $field ) {
		return is_user_logged_in() ? $visible : false;
	}

	public function filter_gravityview_entry_default_field( $entry_default_fields, $form, $context ) {
		if ( is_wp_error( $feeds = GFAPI::get_feeds( null, $form ) ) ) {
			return $entry_default_fields;
		}

		static $subscription_addons;

		if ( is_null( $subscription_addons ) ) {
			foreach ( $registered = GFAddon::get_registered_addons() as $addon ) {
				if ( method_exists( $addon, 'cancel_subscription' ) ) {
					$addon = $addon::get_instance();
					$subscription_addons[ $addon->get_slug() ] = $addon;
				}
			}
		}

		foreach ( $feeds as $feed ) {
			if ( isset( $subscription_addons[ $feed['addon_slug'] ] ) && \GV\Utils::get( $feed, 'meta/transactionType' ) == 'subscription' ) {
				if ( ! isset( $entry_default_fields["{$this->name}"] ) && 'edit' !== $context ) {
					$entry_default_fields["{$this->name}"] = array(
						'label' => $this->label,
						'desc'  => $this->description,
						'type'  => $this->name,
					);

					break; // Feed found, field added
				}
			}
		}

		return $entry_default_fields;
	}

	public function modify_entry_value_unsubscribe( $output, $entry, $field_settings, $field ) {
		if ( ! is_user_logged_in() ) {
			return $output;
		}

		$can_current_user_edit = wp_get_current_user()->ID == $entry['created_by'];

		if ( ! $can_current_user_edit ) {
			if ( empty( $field_settings['unsub_all'] ) || ! \GVCommon::has_cap( 'gravityforms_edit_entries', $entry['id'] ) ) {
				return $output;
			}
		}

		$status = $entry['payment_status'];
		// @todo Move to init, or AJAXify, but make sure that the entry is in the View before allowing
		if ( $entry = $this->maybe_unsubscribe( $entry ) ) {
			if ( $entry['payment_status'] != $status ) {
				// @todo Probably __( 'Unsubscribed', 'gravityview' );
				return $entry['payment_status'];
			}
		}

		if ( strtolower( $entry['payment_status'] ) != 'active' ) {
			return $output;
		}

		global $wp;
		$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );

		$link = add_query_arg( 'unsubscribe', wp_create_nonce( 'unsubscribe_' . $entry['id'] ), $current_url );
		$link = add_query_arg( 'uid', urlencode( $entry['id'] ), $link );

		return sprintf( '<a href="%s">%s</button>', $link, __( 'Unsubscribe', 'gravityview' ) );
	}

	private function maybe_unsubscribe( $entry ) {
		if ( ! wp_verify_nonce( \GV\Utils::_REQUEST( 'unsubscribe' ), 'unsubscribe_' . $entry['id'] ) ) {
			return;
		}

		if ( ( ! $uid = \GV\Utils::_REQUEST( 'uid' ) ) || $uid != $entry['id'] ) {
			return;
		}

		if ( ! $feeds = gform_get_meta( $uid, 'processed_feeds' ) ) {
			return;
		}

		static $subscription_addons;

		if ( is_null( $subscription_addons ) ) {
			foreach ( $registered = GFAddon::get_registered_addons() as $addon ) {
				if ( method_exists( $addon, 'cancel_subscription' ) ) {
					$addon = $addon::get_instance();
					$subscription_addons[ $addon->get_slug() ] = $addon;
				}
			}
		}

		foreach ( $feeds as $slug => $feed_ids ) {
			if ( ! isset( $subscription_addons[ $slug ] ) ) {
				continue;
			}

			foreach ( $feed_ids as $feed_id ) {
				if ( ( $feed = $subscription_addons[ $slug ]->get_feed( $feed_id ) ) && \GV\Utils::get( $feed, 'meta/transactionType' ) == 'subscription' ) {
					if ( $subscription_addons[ $slug ]->cancel( $entry, $feed ) ) {
						$subscription_addons[ $slug ]->cancel_subscription( $entry, $feed );
						return \GFAPI::get_entry( $entry['id'] );
					}
				}
			}
		}

		return $entry;
	}
}

new GravityView_Field_Unsubscribe;
