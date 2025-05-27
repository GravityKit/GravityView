<?php

/**
 * Add Hooks required for Image Hopper.
 *
 * @file      class-gravityview-plugin-hooks-image-hopper.php
 * @since     $ver$
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2015, Katz Web Services, Inc.
 *
 * @package   GravityView
 */
final class GravityView_Plugin_Hooks_Image_Hopper extends GravityView_Plugin_and_Theme_Hooks {
	/**
	 * @inheritDoc
	 * @since $ver$
	 * @var string
	 */
	protected $class_name = Image_Hopper_Gravity_Forms_AddOn_Bootstrap::class;

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function add_hooks(): void {
		parent::add_hooks();

		// Registering with priority `0`, so default hook priority (10) will be able to overwrite.
		add_filter(
			'gk/gravityview/edit-entry/record-file-removal',
			[ $this, 'disable_recording_file_removal' ],
			0,
			2
		);

		add_filter( 'gk/gravityview/edit_entry/custom-validation-value', [ $this, 'custom_validation_value' ], 0, 2 );
	}

	/**
	 * Disables recording file removal for Image Hopper fields.
	 *
	 * @since $ver$
	 *
	 * @param bool     $should_record Whether to record a field for file removal.
	 * @param GF_Field $field         The field.
	 *
	 * @return bool
	 */
	public function disable_recording_file_removal( bool $should_record, GF_Field $field ): bool {
		if ( ! self::is_image_hopper_field( $field ) ) {
			return $should_record;
		}

		return false;
	}

	/**
	 * Overwrites the value used during validation on Edit Entry.
	 *
	 * This will prevent falling back to the value on the Entry, as Image Hopper uses different logic to validate the
	 * field.
	 *
	 * @since $ver$
	 *
	 * @param mixed    $value The current value.
	 * @param GF_Field $field The field instance.
	 *
	 * @return mixed The updated value.
	 */
	public function custom_validation_value( $value, GF_Field $field ) {
		if ( ! self::is_image_hopper_field( $field ) ) {
			return $value;
		}

		// Retrieve value from post values.
		return GFFormsModel::get_field_value( $field );
	}

	/**
	 * Returns whether the provided field is an Image Hopper field.
	 *
	 * @since $ver$
	 *
	 * @param GF_Field $field The field instance.
	 *
	 * @return bool Whether the field is an Image Hopper field.
	 */
	private static function is_image_hopper_field( GF_Field $field ): bool {
		return 'image_hopper' === $field->type;
	}
}

new GravityView_Plugin_Hooks_Image_Hopper();
