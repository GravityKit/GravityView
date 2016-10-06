<?php
/**
 * @file class-gravityview-entry-approval-status.php
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      https://gravityview.co
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
	 * @return array
	 */
	private static function get_choices() {
		return array(
			self::DISAPPROVED => esc_html__( 'Disapproved', 'gravityview' ),
			self::APPROVED    => esc_html__( 'Approved', 'gravityview' ),
			self::UNAPPROVED => esc_html__( 'Unapproved', 'gravityview' ),
		);
	}

	/**
	 * Get the status values as an array
	 *
	 * @return array Array of values for approval status choices
	 */
	public static function get_values() {

		$values = self::get_choices();

		return array_keys( $values );
	}

	/**
	 * Convert previously-used values to the current values, for backward compatibility
	 *
	 * @param string $old_value The status
	 *
	 * @return int|string Current value, possibly converted from old value
	 */
	public static function maybe_convert_status( $old_value = '' ) {

		$new_value = $old_value;

		switch ( (string) $old_value ) {

			// Approved values
			case 'Approved':
			case '1':
				$new_value = self::APPROVED;
				break;

			//Disapproved values
			case '0':
			case '2':
				$new_value = self::DISAPPROVED;
				break;

			case '3':
			case false: // Meta value does not exist yet
				$new_value = self::UNAPPROVED;
				break;
		}

		return $new_value;
	}

	/**
	 * Check whether the passed value is one of the defined values for entry approval
	 *
	 * @param mixed $value
	 *
	 * @return bool True: value is valid; false: value is not valid
	 */
	public static function is_valid( $value ) {

		$value = self::maybe_convert_status( $value );

		return in_array( $value, self::get_values(), true );
	}

	/**
	 * @param mixed $status Value to check approval of
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
	 * @return bool True: passed $status matches disapproved value
	 */
	public static function is_disapproved( $status ) {

		$status = self::maybe_convert_status( $status );

		return ( self::DISAPPROVED === $status );
	}

	/**
	 * @param mixed $status Value to check approval of
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
	 * @return array Array of labels for the status choices ("Approved", "Disapproved")
	 */
	public static function get_labels() {

		$values = self::get_choices();

		return array_values( $values );
	}

	/**
	 * Get the label for a specific approval value
	 *
	 * @param string $value Valid approval value
	 *
	 * @return string|false Label of value ("Approved"). If invalid value, return false.
	 */
	public static function get_label( $value ) {

		$values = self::get_choices();

		return rgar( $values, $value, false );
	}
}
