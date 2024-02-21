<?php
/**
 * @file class-gravityview-field-unsubscribe.php
 * @since 2.5
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Unsubscribe extends GravityView_Field {

	var $name = 'unsubscribe';

	var $group = 'pricing';

	var $is_searchable = false;

	var $contexts = array( 'single', 'multiple' );

	var $icon = 'dashicons-cart';

	public function __construct() {
		$this->label       = esc_html__( 'Unsubscribe', 'gk-gravityview' );
		$this->description = esc_attr__( 'Unsubscribe from a Payment-based entry.', 'gk-gravityview' );

		$this->add_hooks();

		parent::__construct();
	}

	/**
	 * Hooks called from constructor.
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_filter( 'gravityview_entry_default_fields', array( $this, 'filter_gravityview_entry_default_field' ), 10, 3 );

		add_filter( 'gravityview/field/is_visible', array( $this, 'maybe_not_visible' ), 10, 2 );

		add_filter( 'gravityview_field_entry_value_unsubscribe', array( $this, 'modify_entry_value_unsubscribe' ), 10, 4 );
	}

	/**
	 * Configure the field options.
	 *
	 * Called from the `gravityview_entry_default_fields` filter.
	 *
	 * Remove the logged in, new window and show as link options.
	 * Add the allow unsubscribe for all admins option.
	 *
	 * @param array            $field_options The options.
	 * @param string           $template_id The template ID.
	 * @param int|string|float $field_id The field ID.
	 * @param string           $context The configuration context (edit, single, etc.)
	 * @param string           $input_type The input type.
	 * @param int              $form_id The form ID.
	 *
	 * @return array The field options.
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		unset( $field_options['only_loggedin'] );

		unset( $field_options['new_window'] );

		unset( $field_options['show_as_link'] );

		$add_options['unsub_all'] = array(
			'type'       => 'checkbox',
			'label'      => __( 'Allow admins to unsubscribe', 'gk-gravityview' ),
			'desc'       => __( 'Allow users with `gravityforms_edit_entries` to cancel subscriptions', 'gk-gravityview' ),
			'value'      => false,
			'merge_tags' => false,
		);

		return $field_options + $add_options;
	}

	/**
	 * Hide the field from the renderer. Perhaps.
	 *
	 * Called from `gravityview/field/is_visible`
	 *
	 * Hide the field for non-logged in users for sure.
	 *
	 * @param bool      $visible Consider visible or not.
	 * @param \GV\Field $field The field.
	 *
	 * @return bool Visible or not.
	 */
	public function maybe_not_visible( $visible, $field ) {
		if ( $this->name !== $field->ID ) {
			return $visible;
		}
		return is_user_logged_in() ? $visible : false;
	}
	/**
	 * Add the unsubsribe to the configuration fields.
	 *
	 * Only if a subscription feed is active for the current form.
	 *
	 * Called from `gravityview_entry_default_fields`
	 *
	 * @param array     $entry_default_fields An array of available for configuration
	 * @param array|int $form                 Form ID or array
	 * @param string    $context              The configuration context (edit, single, etc.)
	 *
	 * @return array The array of available default fields.
	 */
	public function filter_gravityview_entry_default_field( $entry_default_fields, $form, $context ) {

		if ( is_array( $form ) ) {
			return $entry_default_fields;
		}

		$feeds = GFAPI::get_feeds( null, $form );

		if ( is_wp_error( $feeds ) ) {
			return $entry_default_fields;
		}

		static $subscription_addons;

		if ( is_null( $subscription_addons ) ) {

			$registered = GFAddon::get_registered_addons();

			foreach ( $registered as $addon ) {
				if ( method_exists( $addon, 'cancel_subscription' ) && is_callable( array( $addon, 'get_instance' ) ) ) {
					$addon                                     = $addon::get_instance();
					$subscription_addons[ $addon->get_slug() ] = $addon;
				}
			}
		}

		if ( empty( $subscription_addons ) ) {
			return $entry_default_fields;
		}

		foreach ( $feeds as $feed ) {
			if ( isset( $subscription_addons[ $feed['addon_slug'] ] ) && 'subscription' === \GV\Utils::get( $feed, 'meta/transactionType' ) ) {
				if ( ! isset( $entry_default_fields[ "{$this->name}" ] ) && 'edit' !== $context ) {
					$entry_default_fields[ "{$this->name}" ] = array(
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

	/**
	 * Modify the render content.
	 *
	 * Called from `gravityview_field_entry_value_unsubscribe`
	 *
	 * @param string    $output The output.
	 * @param array     $entry The entry.
	 * @param array     $field_settings The field settings.
	 * @param \GV\Field $field The field.
	 *
	 * @return string The content.
	 */
	public function modify_entry_value_unsubscribe( $output, $entry, $field_settings, $field ) {

		if ( ! is_user_logged_in() || ! $entry ) {
			return $output;
		}

		$can_current_user_edit = is_numeric( $entry['created_by'] ) && ( wp_get_current_user()->ID === intval( $entry['created_by'] ) );

		if ( ! $can_current_user_edit ) {
			if ( empty( $field_settings['unsub_all'] ) || ! \GVCommon::has_cap( 'gravityforms_edit_entries', $entry['id'] ) ) {
				return $output;
			}
		}

		if ( ! $status = \GV\Utils::get( $entry, 'payment_status' ) ) {
			return $output;
		}

		// @todo Move to init, or AJAXify, but make sure that the entry is in the View before allowing
		// @todo Also make sure we check caps if moved from here
		// @todo Also make sure test_GravityView_Field_Unsubscribe_unsubscribe_permissions is rewritten
		if ( $entry = $this->maybe_unsubscribe( $entry ) ) {
			if ( $entry['payment_status'] !== $status ) {
				// @todo Probably __( 'Unsubscribed', 'gravityview' );
				return $entry['payment_status'];
			}
		}

		if ( 'active' !== mb_strtolower( $entry['payment_status'] ) ) {
			return $output;
		}

		global $wp;
		$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );

		$link = add_query_arg( 'unsubscribe', wp_create_nonce( 'unsubscribe_' . $entry['id'] ), $current_url );
		$link = add_query_arg( 'uid', $entry['id'], $link );

		return sprintf( '<a href="%s">%s</a>', esc_url( $link ), esc_html__( 'Unsubscribe', 'gk-gravityview' ) );
	}

	/**
	 * Try to unsubscribe from the entry.
	 *
	 * Called during a POST request. Checks nonce, feeds, entry ID.
	 * Does not check user permissions. This is left as an exercise for the caller.
	 *
	 * Entry View inclusion is checked ad-hoc during the rendering of the field.
	 * User permissions are also checked ad-hoc during the rendering process.
	 *
	 * @param array $entry The entry
	 *
	 * @return array $entry The entry
	 */
	private function maybe_unsubscribe( $entry ) {

		if ( ! wp_verify_nonce( \GV\Utils::_REQUEST( 'unsubscribe' ), 'unsubscribe_' . $entry['id'] ) ) {
			return $entry;
		}

		if ( ( ! $uid = \GV\Utils::_REQUEST( 'uid' ) ) || ! is_numeric( $uid ) || ( intval( $uid ) !== intval( $entry['id'] ) ) ) {
			return $entry;
		}

		if ( ! $feeds = gform_get_meta( $uid, 'processed_feeds' ) ) {
			return $entry;
		}

		static $subscription_addons;

		if ( is_null( $subscription_addons ) ) {

			$registered = GFAddon::get_registered_addons();

			foreach ( $registered as $addon ) {
				if ( method_exists( $addon, 'cancel_subscription' ) ) {
					$addon                                     = $addon::get_instance();
					$subscription_addons[ $addon->get_slug() ] = $addon;
				}
			}
		}

		if ( empty( $subscription_addons ) ) {
			return $entry;
		}

		foreach ( $feeds as $slug => $feed_ids ) {

			if ( ! isset( $subscription_addons[ $slug ] ) ) {
				continue;
			}

			foreach ( $feed_ids as $feed_id ) {

				$feed = $subscription_addons[ $slug ]->get_feed( $feed_id );

				if ( $feed && 'subscription' === \GV\Utils::get( $feed, 'meta/transactionType' ) ) {

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

new GravityView_Field_Unsubscribe();
