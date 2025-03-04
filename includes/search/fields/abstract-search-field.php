<?php

namespace GV\Search\Fields;

use GravityView_Widget_Search;
use GV\Search\Search_Field_Collection;

/**
 * Represents a single Search Field.
 *
 * @since $ver$
 * @template T The type of the value.
 */
abstract class Search_Field extends \GravityView_Admin_View_Item {
	/**
	 * Holds the initialized fields.
	 *
	 * @since $ver$
	 */
	private static $initialized_fields = [];

	/**
	 * The position.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	public string $position = '';

	/**
	 * A unique identifier for the Search Field type.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected static string $type = 'unknown';

	/**
	 * The search field type.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected static string $field_type = 'text';

	/**
	 * The Field description.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $description = '';

	/**
	 * The icon.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $icon = '';

	/**
	 * The value.
	 *
	 * @since $ver$
	 *
	 * @var T
	 */
	protected $value;

	/**
	 * Creates the base field instance.
	 *
	 * @since $ver$
	 */
	public function __construct( ?string $label = null, array $data = [] ) {
		parent::__construct(
			$label ?? $this->get_label(),
			$this->get_type(),
			$data,
			array_intersect_key( $data, array_flip( [ 'custom_label', 'show_label' ] ) )
		);

		$this->item['icon']        = $this->icon ? $this->icon : $this->item['icon'];
		$this->item['description'] = $this->get_description();

		$this->init();
	}

	/**
	 * Returns the icon for the field.
	 *
	 * @since $ver$
	 *
	 * @return string
	 */
	public function icon_html(): string {
		if ( ! $this->icon ) {
			return '';
		}

		return sprintf( '<i class="dashicons %s"></i>', $this->icon );
	}

	/**
	 * Returns the Field instance based on the data.
	 *
	 * @since $ver$
	 *
	 * @param array $data The field data.
	 *
	 * @return static|null The field instance.
	 */
	public static function from_configuration( array $data ): ?Search_Field {
		// Can't instantiate the abstract class, but we can use it as a factory.
		if ( static::class === self::class ) {
			$fields = Search_Field_Collection::available_fields( (int) ( $data['form_id'] ?? 0 ) );
			$class  = $fields->get_class_by_type( $data['id'] );
			if ( ! is_a( $class, self::class, true ) ) {
				return null;
			}

			return $class::from_configuration( $data );
		}

		$field = new static( $data['label'] ?? null, $data );

		unset( $data['type'] );

		foreach ( $data as $key => $value ) {
			if ( property_exists( $field, $key ) ) {
				$field->{$key} = $value;
			}
		}

		return $field;
	}

	/**
	 * Returns the options for this field.
	 *
	 * @since $ver$
	 *
	 * @return array
	 */
	protected function get_options(): array {
		return [];
	}

	/**
	 * Returns the default settings for every search field.
	 *
	 * @since $ver$
	 *
	 * @return array[]
	 */
	private function get_search_field_options(): array {
		$options = [
			'show_label'   => [
				'type'  => 'checkbox',
				'label' => esc_html__( 'Show label', 'gk-gravityview' ),
				'value' => '1',
			],
			'custom_label' => [
				'type'        => 'text',
				'label'       => esc_html__( 'Custom label', 'gk-gravityview' ),
				'value'       => '',
				'placeholder' => $this->get_default_label(),
				'class'       => 'widefat',
			],
		];

		$input_types_mapping = GravityView_Widget_Search::get_input_types_by_field_type();
		$input_types         = $input_types_mapping[ $this->get_field_type() ] ?? $input_types_mapping['text'];

		if ( $input_types ) {
			$options['input_type'] = [
				'type'  => count( $input_types ) > 1 ? 'select' : 'hidden',
				'label' => esc_html__( 'Input type', 'gk-gravityview' ),
				'value' => current( $input_types ),
				'class' => 'widefat',
			];

			if ( count( $input_types ) > 1 ) {
				$input_type_labels_mapping        = GravityView_Widget_Search::get_search_input_labels();
				$input_type_labels                = array_map(
					static fn( string $input_type ): string => $input_type_labels_mapping[ $input_type ] ?? 'Unknown',
					$input_types,
				);
				$options['input_type']['choices'] = array_combine( $input_types, $input_type_labels );
			}
		}

		return $options;
	}

	/**
	 * Returns the value of the field as the correct type.
	 *
	 * @since $ver$
	 *
	 * @return mixed
	 */
	protected function get_value() {
		return $this->value;
	}

	/**
	 * Returns the field as a configuration array.
	 *
	 * @since $ver$
	 *
	 * @return array The configuration.
	 */
	public function to_configuration(): array {
		return [
			'type'     => $this->get_type(),
			'label'    => $this->title,
			'value'    => $this->get_value(),
			'position' => $this->position,
		];
	}

	/**
	 * Returns the unique type of this field.
	 *
	 * @since $ver$
	 *
	 * @return string The type.
	 */
	protected function get_type(): string {
		return static::$type;
	}

	/**
	 * Returns the field type.
	 *
	 * @since $ver$
	 *
	 * @return string
	 */
	protected function get_field_type(): string {
		return static::$field_type;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_title( string $label ): string {
		// Translators: %s will contain the label of the field.
		return sprintf( __( 'Search Field: %s', 'gk-gravityview' ), $label );
	}

	/**
	 * Returns the label for this field.
	 *
	 * @since $ver$
	 */
	protected function get_label(): string {
		if ( ! ( $this->item['show_label'] ?? true ) ) {
			return '';
		}

		return $this->item['custom_label'] ?? $this->title;
	}

	/**
	 * Returns the default label for this field.
	 *
	 * @since $ver$
	 *
	 * @return string The default label.
	 */
	protected function get_default_label(): string {
		return $this->title;
	}

	/**
	 * Returns the description for this field.
	 *
	 * @since $ver$
	 */
	public function get_description(): string {
		return '';
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 * @return array{value: string, label?: string, class?: string, hide_in_picker?: bool}
	 */
	protected function additional_info(): array {
		$description = $this->get_description();
		if ( ! $description ) {
			return [];
		}

		$field_info_items = [
			[ 'value' => $this->item['description'] ],
		];

		return $field_info_items;
	}

	/**
	 * Registers the required hooks.
	 *
	 * @since $ver$
	 */
	protected function init(): void {
		if ( ! isset( self::$initialized_fields[ $this->get_type() ] ) ) {
			add_filter( 'gravityview_template_search_options', [ $this, 'set_search_field_options' ], 10, 3 );
			self::$initialized_fields[ $this->get_type() ] = true;
		}
	}

	/**
	 * Returns whether the search field is of the provided type.
	 *
	 * @since $ver$
	 *
	 * @param string $type The type.
	 *
	 * @return bool Whether the search field is of the provided type.
	 */
	protected static function is_of_type( string $type ): bool {
		return $type === static::$type;
	}

	/**
	 * Add the settings for this field.
	 *
	 * @param array  $options  The original options.
	 * @param string $template The template ID.
	 * @param string $type     The field type.
	 *
	 * @return array The updated options.
	 */
	final public function set_search_field_options( $options = [], $template = '', $type = '' ): array {
		if ( ! static::is_of_type( $type ) ) {
			return (array) $options;
		}

		return array_merge( $this->get_search_field_options(), $this->get_options() );
	}
}
