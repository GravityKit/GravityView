<?php
/**
 * @file class-gravityview-entry-approval-status.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      https://www.gravitykit.com
 * @copyright Copyright 2016, Katz Web Services, Inc.
 *
 * @since 1.18
 */

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * There are specific values of entry approval that are valid. This class holds them and manages access to them.
 *
 * @since 1.18
 */
final class GravityView_Entry_Approval_Status {

	/**
	 * @var int The value of the "Approved" status
	 */
	const APPROVED = 1;

	/**
	 * @var int The value of the "Disapproved" status
	 */
	const DISAPPROVED = 2;

	/**
	 * @var int Placeholder value for "Unapproved" status; in reality, it's not stored in the DB; the meta gets deleted.
	 */
	const UNAPPROVED = 3;

	/**
	 * GravityView_Entry_Approval_Status constructor.
	 */
	private function __construct() {}

	/**
	 * Match values to the labels
	 *
	 * @since 1.18
	 *
	 * @return array
	 */
	private static function get_choices() {
		$choices = array(
			'disapproved' => array(
				'value'  => self::DISAPPROVED,
				'label'  => esc_html__( 'Disapproved', 'gk-gravityview' ),
				'action' => esc_html__( 'Disapprove', 'gk-gravityview' ),
				'title'  => esc_html__( 'Entry not approved for directory viewing. Click to approve this entry.', 'gk-gravityview' ),
			),
			'approved'    => array(
				'value'         => self::APPROVED,
				'label'         => esc_html__( 'Approved', 'gk-gravityview' ),
				'action'        => esc_html__( 'Approve', 'gk-gravityview' ),
				'title'         => esc_html__( 'Entry approved for directory viewing. Click to disapprove this entry.', 'gk-gravityview' ),
				'title_popover' => esc_html__( 'Entry approved for directory viewing. Click to disapprove this entry.', 'gk-gravityview' ),
			),
			'unapproved'  => array(
				'value'  => self::UNAPPROVED,
				'label'  => esc_html__( 'Unapproved', 'gk-gravityview' ),
				'action' => esc_html__( 'Reset Approval', 'gk-gravityview' ),
				'title'  => esc_html__( 'Entry not yet reviewed. Click to approve this entry.', 'gk-gravityview' ),
			),
		);

		/**
		 * Modify the entry approval status choices.
		 *
		 * Do not modify the array keys or the `value` key! Only modify the `label`, `action`, and `title` keys!
		 *
		 * @since 2.24
		 *
		 * @param array $choices Array of entry approval statuses.
		 */
		$choices = apply_filters( 'gk/gravityview/entry-approval/choices', $choices );

		return $choices;
	}

	/**
	 * Return array of status options
	 *
	 * @see GravityView_Entry_Approval_Status::get_choices
	 *
	 * @return array Associative array of available statuses
	 */
	public static function get_all() {
		return self::get_choices();
	}

	/**
	 * Get the status values as an array
	 *
	 * @since 1.18
	 *
	 * @return array Array of values for approval status choices
	 */
	public static function get_values() {

		$choices = self::get_choices();

		$values = wp_list_pluck( $choices, 'value' );

		return $values;
	}

	/**
	 * Convert previously-used values to the current values, for backward compatibility.
	 *
	 * @since 1.18
	 *
	 * @param string $old_value The status
	 *
	 * @return int Status value (`1` for approved, `2` for disapproved, or `3` for unapproved).
	 */
	public static function maybe_convert_status( $old_value = '' ) {

		$new_value = $old_value;

		// Meta value does not exist yet
		if ( false === $old_value ) {
			return self::UNAPPROVED;
		}

		// Meta value does not exist yet
		if ( true === $old_value ) {
			return self::APPROVED;
		}

		switch ( (string) $old_value ) {

			// Approved values
			case 'Approved':
			case '1':
				$new_value = self::APPROVED;
				break;

			// Disapproved values
			case '0':
			case '2':
				$new_value = self::DISAPPROVED;
				break;

			// Unapproved values
			case '3':
			case '':
				$new_value = self::UNAPPROVED;
				break;
		}

		return $new_value;
	}

	/**
	 * Check whether the passed value is one of the defined values for entry approval
	 *
	 * @since 1.18
	 *
	 * @param mixed $value
	 *
	 * @return bool True: value is valid; false: value is not valid
	 */
	public static function is_valid( $value = null ) {

		if ( ! is_scalar( $value ) || is_null( $value ) ) {
			return false;
		}

		$value = self::maybe_convert_status( $value );

		return in_array( $value, self::get_values(), true );
	}

	/**
	 * @param mixed $status Value to check approval of
	 *
	 * @since 1.18
	 *
	 * @return bool True: passed $status matches approved value
	 */
	public static function is_approved( $status ) {

		$status = self::maybe_convert_status( $status );

		return ( self::APPROVED === $status );
	}

	/**
	 * @param mixed $status Value to check approval of
	 *
	 * @since 1.18
	 *
	 * @return bool True: passed $status matches disapproved value
	 */
	public static function is_disapproved( $status ) {

		$status = self::maybe_convert_status( $status );

		return ( self::DISAPPROVED === $status );
	}

	/**
	 * @param mixed $status Value to check approval of
	 *
	 * @since 1.18
	 *
	 * @return bool True: passed $status matches unapproved value
	 */
	public static function is_unapproved( $status ) {

		$status = self::maybe_convert_status( $status );

		return ( self::UNAPPROVED === $status );
	}

	/**
	 * Get the labels for the status choices
	 *
	 * @since 1.18
	 *
	 * @return array Array of labels for the status choices ("Approved", "Disapproved")
	 */
	public static function get_labels() {

		$choices = self::get_choices();

		$labels = wp_list_pluck( $choices, 'label' );

		return $labels;
	}


	/**
	 * Pluck a certain field value from a status array
	 *
	 * Examples:
	 *
	 * <code>
	 * self::choice_pluck( 'disapproved', 'value' ); // Returns `2`
	 * self::choice_pluck( 'approved', 'label' ); // Returns `Approved`
	 * </code>
	 *
	 * @since 1.18
	 *
	 * @param int|string $status Valid status value or key (1 or "approved")
	 * @param string     $attr_key Key name for the "value", "label", "action", "title". If "key", returns the matched key instead of value.
	 *
	 * @return false|string False if match isn't not found
	 */
	private static function choice_pluck( $status, $attr_key = '' ) {
		$choices = self::get_choices();

		foreach ( $choices as $key => $choice ) {

			// Is the passed status value the same as the choice value or key?
			if ( $status === $choice['value'] || $status === $key ) {
				if ( 'key' === $attr_key ) {
					return $key;
				} else {
					return \GV\Utils::get( $choice, $attr_key, false );
				}
			}
		}

		return false;
	}

	/**
	 * Get the label for a specific approval value
	 *
	 * @since 1.18
	 *
	 * @param int|string $value_or_key Valid status value or key (1 or "approved")
	 *
	 * @return string|false Label of value ("Approved"). If invalid value, return false.
	 */
	public static function get_label( $value_or_key ) {
		return self::choice_pluck( $value_or_key, 'label' );
	}

	/**
	 * Get the label for a specific approval value
	 *
	 * @since 2.17
	 *
	 * @param int|string $value_or_key Valid status value or key (1 or "approved")
	 *
	 * @return string|false Action of value (eg: "Reset Approval"). If invalid value, return false.
	 */
	public static function get_action( $value_or_key ) {
		return self::choice_pluck( $value_or_key, 'action' );
	}

	/**
	 * Get the label for a specific approval value
	 *
	 * @since 1.18
	 *
	 * @param int|string $value_or_key Valid status value or key (1 or "approved")
	 *
	 * @return string|false Label of value ("Approved"). If invalid value, return false.
	 */
	public static function get_string( $value_or_key, $string_key = '' ) {
		return self::choice_pluck( $value_or_key, $string_key );
	}

	/**
	 * Get the label for a specific approval value
	 *
	 * @since 1.18
	 *
	 * @param int|string $value_or_key Valid status value or key (1 or "approved")
	 *
	 * @return string|false Label of value ("Approved"). If invalid value, return false.
	 */
	public static function get_title_attr( $value_or_key ) {
		return self::choice_pluck( $value_or_key, 'title' );
	}

	/**
	 * Get the status key for a value
	 *
	 * @param int $value Status value (1, 2, 3)
	 *
	 * @return string|false The status key at status $value, if exists. If not exists, false.
	 */
	public static function get_key( $value ) {
		return self::choice_pluck( $value, 'key' );
	}
}
