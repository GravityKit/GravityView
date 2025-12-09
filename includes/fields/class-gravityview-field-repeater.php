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
	 *
	 * @since $ver$
	 */
	public $name = 'repeater';

	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public $_gf_field_class_name = 'GF_Field_Repeater';

	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public $group = 'advanced';

	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public $search_operators = [ 'is', 'isnot', 'contains' ];

	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public $is_searchable = false;

	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public $is_sortable = false;

	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	public $icon = 'dashicons-controls-repeat';

	/**
	 * Whether the field is initialized.
	 *
	 * @since $ver$
	 *
	 * @var bool
	 */
	protected static bool $is_initialized = false;

	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public function __construct() {
		$this->label = esc_html__( 'Repeater', 'gk-gravityview' );

		$this->add_hooks();
		parent::__construct();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ): array {
		if ( ! is_array( $field_options ) ) {
			$field_options = [];
		}

		$field_options['max_results'] = [
			'type'     => 'number',
			'label'    => __( 'Maximum results', 'gk-gravityview' ),
			'desc'     => esc_html__(
				'Maximum number of results to show per nesting level. Leave empty to show all results.',
				'gk-gravityview'
			),
			'value'    => '',
			'priority' => 1000,
			'group'    => 'display',
			'contexts' => [ 'multiple', 'single' ],
			'min'      => 0,
		];

		$field_options['hide_nested_repeater_fields'] = [
			'type'         => 'checkbox',
			'label'        => __( 'Hide nested repeater results', 'gk-gravityview' ),
			'value'        => '',
			'priority'     => 1000,
			'group'        => 'display',
			'requires_not' => 'full_width=1',
			'contexts'     => [ 'multiple', 'single' ],
		];

		$field_options['show_more_results'] = [
			'type'         => 'checkbox',
			'label'        => __( 'Show "X more results" text', 'gk-gravityview' ),
			'desc'         => esc_html__(
				'Display a "X more results" text when more results are available.',
				'gk-gravityview'
			),
			'value'        => '',
			'priority'     => 1000,
			'group'        => 'display',
			'contexts'     => [ 'multiple', 'single' ],
			'requires_not' => 'hide_nested_repeater_fields=1',
		];

		return parent::field_options(
			$field_options,
			$template_id,
			$field_id,
			$context,
			$input_type,
			$form_id
		);
	}

	/**
	 * Register the required hooks for this field.
	 *
	 * @since $ver$
	 */
	private function add_hooks(): void {
		if ( static::$is_initialized ) {
			return;
		}

		add_filter( 'gravityview/template/field/class', [ $this, 'maybe_replace_renderer_class' ], 10, 2 );
		add_filter( 'gform_entry_field_value', [ $this, 'remove_gform_styling' ], 10, 2 );
		add_filter( 'gravityview/field/repeater/value', [ $this, 'limit_results' ], 10, 2 );
		add_filter( 'gravityview/sortable/field_blocklist', [ $this, 'prevent_sort_subfields' ], 10, 4 );
	}

	/**
	 * Limits the results of a Repeater field to `max_results`.
	 *
	 * @since $ver$
	 *
	 * @param array $value The value for the repeater field.
	 * @param Field $field The GV Field object.
	 *
	 * @return array The limited results.
	 */
	public function limit_results( $value, Field $field ): array {
		if ( ! is_array( $value ) ) {
			$value = [];
		}
		$config      = $field->as_configuration();
		$max_results = abs( (int) ( $config['max_results'] ?? 0 ) );

		$gf_field = $field->field ?? null;
		if ( ! $gf_field instanceof GF_Field_Repeater ) {
			return $value;
		}

		// To hide any nested repeater field, we need to "remove" their values.
		if ( $config['hide_nested_repeater_fields'] ?? false ) {
			foreach ( $gf_field->fields ?? [] as $child ) {
				if ( $child instanceof GF_Field_Repeater ) {
					foreach ( $value as $i => $_ ) {
						// Unset all nested values.
						$value[ $i ][ $child->id ] = [];
					}
				}
			}
		}

		if ( 0 === $max_results ) {
			return $value;
		}

		return $this->limit_results_recursively( $value, $gf_field, $config );
	}

	/**
	 * Recursively limits the result of a repeater field to `max_results`.
	 *
	 * @since $ver$
	 *
	 * @param array                      $value  The value of the current repeater field.
	 * @param GF_Field|GF_Field_Repeater $field  The repeater field.
	 * @param array                      $config The field configuration.
	 *
	 * @return array
	 */
	protected function limit_results_recursively( array $value, GF_Field $field, array $config ): array {
		$max_results       = (int) abs( $config['max_results'] ?? 0 );
		$show_more_results = (bool) ( $config['show_more_results'] ?? false );

		// First, we cut down the number of results for this level.
		$result_count = count( $value );
		if ( $result_count > $max_results ) {
			$value = array_slice( $value, 0, $max_results );
		}

		// Keep track of the remaining result count (Note: dynamic parameter!).
		$field->more_results_count = $show_more_results ? ( $result_count - $max_results ) : 0;

		// If any subfields are repeaters, limit those as well.
		foreach ( $field->fields ?? [] as $child ) {
			if ( ! $child instanceof GF_Field_Repeater ) {
				continue;
			}

			foreach ( $value as $i => $values ) {
				$value[ $i ][ $child->id ] = $this->limit_results_recursively(
					$values[ $child->id ] ?? [],
					$child,
					$config
				);
			}
		}

		return $value;
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
			! Core::get()->request->is_view()
			|| ! $field instanceof GF_Field_Repeater
		) {
			return $html;
		}

		// remove style tags.
		$html = preg_replace( "/(class='gfield_repeater_value') style='[^']*'/i", '$1', $html );

		// Remove empty repeater cells (where gfield_repeater_items is empty).
		$html = preg_replace(
			"/<div class=['\"]gfield_repeater_cell['\"][^>]*>\s*<div class=['\"]gfield_repeater_value['\"][^>]*>\s*<div class=['\"]gfield_repeater['\"][^>]*>\s*<div class=['\"]gfield_label[^'\"]*['\"][^>]*>[^<]*<\/div>\s*<div class=['\"]gfield_repeater_items['\"][^>]*>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/i",
			'',
			$html
		);

		if ( ! empty( $field->more_results_count ) ) {
			$html .= sprintf(
			// Translators: %d is replaced with the number of remaining results.
				'<span class="gv-more-results">' . esc_html__( '%d more results', 'gk-gravityview' ) . '</span>',
				$field->more_results_count
			);
		}

		// Remove the temporary dynamic parameter.
		unset( $field->more_results_count );

		return $html;
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
		// Single digit as string as a key.
		$field_id = (string) (int) ( $config['id'] ?? 0 );

		return static::has_repeater_parent( $form_id, $field_id );
	}

	/**
	 * Prevents sorting of repeater fields, or it's subfields.
	 *
	 * @since $ver$
	 *
	 * @param string[] $not_sortable The field types that aren't sortable.
	 * @param string   $field_type   The field type.
	 * @param array    $form         The form object.
	 * @param string   $field_id     The field ID, which might be the same as the field type.
	 *
	 * @return array
	 */
	public function prevent_sort_subfields( $not_sortable, $field_type, $form, $field_id ): array {
		if ( ! is_array( $not_sortable ) ) {
			$not_sortable = [];
		}

		// part of a repeater field, so we can't sort by this field.
		if ( static::has_repeater_parent( $form['id'] ?? 0, (int) $field_id ) ) {
			$not_sortable[] = $field_id;
		}

		return $not_sortable;
	}

	/**
	 * Returns whether a field has a repeater field as a parent.
	 *
	 * @since $ver$
	 *
	 * @param int $form_id  The form ID.
	 * @param int $field_id The field ID.
	 *
	 * @return bool Whether the field is a child of a repeater field.
	 */
	private static function has_repeater_parent( int $form_id, int $field_id ): bool {
		if ( ! $form_id || ! $field_id ) {
			return false;
		}
		// Recursively retrieve all field ID's that are part of a repeater field on the form.
		$repeater_field_ids = static::get_repeater_field_ids( $form_id );

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
