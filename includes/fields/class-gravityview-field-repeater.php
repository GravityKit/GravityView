<?php

use GV\Core;
use GV\Field;
use GV\Field_HTML_Template;

/**
 * @file       class-gravityview-field-repeater.php
 * @package    GravityView
 * @subpackage includes\fields
 */
class GravityView_Field_Repeater extends GravityView_Field {
	/**
	 * {@inheritDoc}
	 * @since $ver$
	 */
	public $name = 'repeater';

	/**
	 * {@inheritDoc}
	 * @since $ver$
	 */
	public $_gf_field_class_name = 'GF_Field_Repeater';

	/**
	 * {@inheritDoc}
	 * @since $ver$
	 */
	public $group = 'advanced';

	/**
	 * {@inheritDoc}
	 * @since $ver$
	 */
	public $search_operators = [ 'is', 'isnot', 'contains' ];

	/**
	 * {@inheritDoc}
	 * @since $ver$
	 */
	public $is_searchable = false;

	/**
	 * {@inheritDoc}
	 * @since $ver$
	 * @var string
	 */
	public $icon = 'dashicons-forms';

	/**
	 * {@inheritDoc}
	 * @sinve $ver$
	 */
	public function __construct() {
		$this->label = esc_html__( 'Repeater', 'gk-gravityview' );

		$this->add_hooks();
		parent::__construct();
	}

	/**
	 * Register the required hooks for this field.
	 *
	 * @since $ver$
	 */
	private function add_hooks(): void {
		add_filter( 'gravityview/template/field/class', [ $this, 'maybe_replace_renderer_class' ], 10, 2 );
		add_filter( 'gform_entry_field_value', [ $this, 'remove_gform_styling' ], 10, 2 );
	}

	/**
	 * Remove styling from repeater fields if this is a View Rendering.
	 *
	 * @since $ver$
	 *
	 * @param string        $html  The HTML of the field.
	 * @param GF_Field|null $field The field instance.
	 *
	 * @return string The updated HTML.
	 */
	public function remove_gform_styling( string $html, $field = null ): string {
		if (
			Core::get()->request->is_view
			|| ! $field instanceof GF_Field_Repeater
		) {
			return $html;
		}

		return preg_replace( "/(class='gfield_repeater_value') style='[^']*'/i", '$1', $html );
	}

	/**
	 * Replaces the renderer class for a Repeater field.
	 *
	 * @since $ver$
	 *
	 * @param string $class The original class.
	 * @param Field  $field The Field object.
	 *
	 * @return string The required renderer class.
	 */
	public function maybe_replace_renderer_class( string $class, Field $field ): string {
		if (
			$class !== "\\" . Field_HTML_Template::class
			|| ! static::is_part_of_repeater_field( $field )
		) {
			return $class;
		}

		return 'GravityView_Repeater_Field_HTML_Template';
	}

	/**
	 * Cache for repeater field IDs per form.
	 *
	 * @since $ver$
	 *
	 * @var array<int, array<int, int[]>>
	 */
	private static array $repeater_field_cache = [];

	/**
	 * Returns whether this field is part of a repeater field.
	 *
	 * @since $ver$
	 *
	 * @param Field $field The GV Field object.
	 *
	 * @return bool Whether this field is part of a repeater field.
	 */
	public static function is_part_of_repeater_field( Field $field ): bool {
		$config  = $field->as_configuration();
		$form_id = (int) ( $config['form_id'] ?? 0 );

		if ( ! $form_id ) {
			return false;
		}
		// Recursively retrieve all field ID's that are part of a repeater field on the form.
		$repeater_field_ids = static::get_repeater_field_ids( $form_id );

		// Single digit as string as a key.
		$field_id = (string) (int) ( $config['id'] ?? 0 );

		return array_key_exists( $field_id, $repeater_field_ids );
	}

	/**
	 * Recursively retrieves all field IDs that are part of repeater fields.
	 *
	 * Returns an array with field IDs as keys and their parent repeater IDs as values.
	 *
	 * @since $ver$
	 *
	 * @param int   $form_id The form ID (used for caching).
	 * @param array $fields  The fields to search through.
	 * @param int[] $parents The parent repeater IDs for the current recursion level.
	 *
	 * @return array<int, int[]> Array of field IDs mapped to their parent repeater IDs.
	 */
	public static function get_repeater_field_ids( int $form_id, array $fields = [], array $parents = [] ): array {
		// Return cached result if available (only for top-level calls).
		if ( empty( $parents ) && isset( self::$repeater_field_cache[ $form_id ] ) ) {
			return self::$repeater_field_cache[ $form_id ];
		}

		if ( ! $fields && ! $parents ) {
			$form   = GFAPI::get_form( $form_id );
			$fields = $form['fields'] ?? [];
		}

		$ids = [];

		foreach ( $fields as $field ) {
			if ( ! $field instanceof GF_Field_Repeater ) {
				continue;
			}

			$repeater_id     = (int) $field->id;
			$current_parents = [ ...$parents, $repeater_id ];
			$nested_fields   = $field->fields ?? [];

			foreach ( $nested_fields as $nested_field ) {
				// Force single digit.
				$nested_id = (int) $nested_field->id;
				// Set key as string, to avoid re-indexing.
				$ids[ (string) $nested_id ] = $current_parents;

				// If the nested field is also a repeater, recurse into it.
				if ( $nested_field instanceof GF_Field_Repeater ) {
					$ids += static::get_repeater_field_ids( $form_id, [ $nested_field ], $current_parents );
				}
			}
		}

		// Cache the result for top-level calls.
		if ( empty( $parents ) ) {
			self::$repeater_field_cache[ $form_id ] = $ids;
		}

		return $ids;
	}

	/**
	 * Clears the repeater field cache.
	 *
	 * @since $ver$
	 *
	 * @param int|null $form_id Optional form ID to clear cache for. If null, clears all cache.
	 */
	public static function clear_cache( ?int $form_id = null ): void {
		if ( $form_id === null ) {
			self::$repeater_field_cache = [];
		} else {
			unset( self::$repeater_field_cache[ $form_id ] );
		}
	}
}

new GravityView_Field_Repeater();
