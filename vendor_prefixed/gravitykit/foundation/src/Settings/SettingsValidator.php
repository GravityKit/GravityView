<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Settings;

use GravityKit\GravityView\Foundation\Helpers\Core as CoreHelpers;
use GravityKit\GravityView\Foundation\ThirdParty\Illuminate\Validation;
use GravityKit\GravityView\Foundation\ThirdParty\Illuminate\Filesystem;
use GravityKit\GravityView\Foundation\ThirdParty\Illuminate\Translation;

class ValidatorException extends \Exception { }

class SettingsValidator {
	/**
	 * @since 1.0.0
	 *
	 * @var Filesystem\Filesystem Required dependency for Illuminate\Validation.
	 */
	private $filesystem;

	/**
	 * @since 1.0.0
	 *
	 * @var Translation\FileLoader Required dependency for Illuminate\Validation.
	 */
	private $file_loader;

	/**
	 * @since 1.0.0
	 *
	 * @var Translation\Translator Required dependency for Illuminate\Validation.
	 */
	private $translator;

	/**
	 * @since 1.0.0
	 *
	 * @var Validation\Factory Validator instance.
	 */
	private $validator_factory;

	public function __construct() {
		$this->filesystem        = new Filesystem\Filesystem();
		$this->file_loader       = new Translation\FileLoader( $this->filesystem, '' );
		$this->translator        = new Translation\Translator( $this->file_loader, '' );
		$this->validator_factory = new Validation\Factory( $this->translator );

		$this->add_custom_validation_rules();
	}

	/**
	 * Adds custom validation rules (these match custom Yup rules added in the UI).
	 *
	 * @since 1.0.0
	 * @see   `UI/src/lib/validation.js`
	 *
	 */
	private function add_custom_validation_rules() {
		$this->validator_factory->extend( 'is', function ( $attribute, $value, $parameters ) {
			if ( ! is_array( $parameters ) ) {
				return false;
			}

			return $value === $parameters[0];
		} );

		$this->validator_factory->extend( 'isNot', function ( $attribute, $value, $parameters ) {
			if ( ! is_array( $parameters ) ) {
				return false;
			}

			return $value !== $parameters[0];
		} );

		// Works for array or `multiple_checkboxes` type.
		$this->validator_factory->extend( 'has', function ( $attribute, $value, $parameters ) {
			if ( ! is_array( $parameters ) ) {
				return false;
			}
			

			return in_array( $parameters[0], $value );
		} );

		$this->validator_factory->extend( 'matches', function ( $attribute, $value, $parameters ) {
			if ( ! is_array( $parameters ) ) {
				return false;
			}

			return preg_match( '/' . $parameters[0] . '/', $value );
		} );
	}

	/**
	 * Performs validation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $rule  Validation rule (see https://laravel.com/docs/5.4/validation#available-validation-rules).
	 * @param string $value Validation value.
	 *
	 * @throws ValidatorException
	 *
	 * @return bool
	 */
	private function run_validator( $rule, $value ) {
		$validator = $this->validator_factory->make(
			[ 'value' => $value ], // Value to validate.
			[ 'value' => $rule ], // Rule.
			[] // Validation messages; not used.
		);

		try {
			if ( ! $validator->fails() ) {
				return true;
			}
		} catch ( \Exception $e ) {
			throw new ValidatorException( $e->getMessage() );
		}

		return false;
	}

	/**
	 * Validates settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin               Plugin ID.
	 * @param array  $original_settings    Flattened settings object (i.e., not split by sections) as defined by the plugin (see `gk/foundation/settings/data/plugins` filter).
	 * @param array  $settings_to_validate Setting/value pair to validate.
	 *
	 * @throws ValidatorException
	 *
	 * @return bool
	 */
	public function validate( $plugin, array $original_settings, array $settings_to_validate ) {
		$validated_settings = [];

		$missing_settings = array_keys( array_diff_key( $original_settings, $settings_to_validate ) );

		if ( $missing_settings ) {
			$missing_settings_title = array_map( function ( $setting ) use ( $original_settings ) {
				return $original_settings[ $setting ]['title'];
			}, $missing_settings );

			throw new ValidatorException(
				strtr(
					esc_html_x( 'Missing settings: [settings].', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
					[ '[settings]' => implode( ', ', $missing_settings_title ) ]
				)
			);
		}

		foreach ( $original_settings as $setting ) {
			$value_to_validate = $settings_to_validate[ $setting['id'] ];

			if ( empty( $setting['validation'] ) ) {
				/**
				 * @action `gk/foundation/settings/{plugin}/validation/{setting_id}` Runs when validation rules are not specified and before the setting is marked as validated.
				 *
				 * @since  1.0.0
				 *
				 * @param array  $setting           Original setting.
				 * @param string $value_to_validate Value to validate.
				 */
				do_action( "gk/foundation/settings/${plugin}/validation/${setting['id']}", $setting, $value_to_validate );

				$validated_settings[ $setting['id'] ] = $value_to_validate;

				continue;
			}

			// Validation can be a callback.
			if ( CoreHelpers::is_callable_function( $setting['validation'] ) ) {
				call_user_func( $setting['validation'], $setting, $value_to_validate );

				$validated_settings[ $setting['id'] ] = $value_to_validate;

				continue;
			}

			// Convert validation object to a multidimensional array.
			$validation_rules = empty( $setting['validation'][0] ) ? [ $setting['validation'] ] : $setting['validation'];

			$is_valid = true;
			foreach ( $validation_rules as $validation_rule ) {
				if ( empty( $validation_rule['rule'] ) ) {
					throw new ValidatorException(
						strtr(
							esc_html_x( 'Validation rule for setting [setting] is missing.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
							[ '[setting]' => $setting['id'] ]

						)
					);
				}

				try {
					if ( ! $this->run_validator( $validation_rule['rule'], $value_to_validate ) ) {
						$is_valid = false;

						break;
					}
				} catch ( ValidatorException $e ) {
					throw new ValidatorException(
						strtr(
							esc_html_x( 'Validation for setting [setting] failed: [reason].', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
							[
								'[setting]' => $setting['id'],
								'[reason]'  => $e->getMessage()
							]
						)
					);
				}
			}

			if ( $is_valid ) {
				$validated_settings[ $setting['id'] ] = $value_to_validate;
			}
		}

		$settings_failed_validation = array_keys( array_diff_key( $settings_to_validate, $validated_settings ) );

		if ( ! empty( $settings_failed_validation ) ) {
			throw new ValidatorException(
				strtr(
					esc_html_x( 'Settings that failed validation: [settings].', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' ),
					[ '[settings]' => implode( ', ', $settings_failed_validation ) ]
				)
			);
		}

		return true;
	}
}
