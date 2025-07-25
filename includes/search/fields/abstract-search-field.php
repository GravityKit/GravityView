<?php

namespace GV\Search\Fields;

use GF_Field;
use GFFormsModel;
use GravityView_Widget_Search;
use GV\Context;
use GV\Grid;
use GV\Search\Search_Field_Collection;
use GV\View;

/**
 * Represents a single Search Field.
 *
 * @since 2.42
 * @template T The type of the value.
 */
abstract class Search_Field extends \GravityView_Admin_View_Item {
	/**
	 * The position.
	 *
	 * @since 2.42
	 *
	 * @var string
	 */
	public string $position = '';

	/**
	 * A unique identifier for the Search Field type.
	 *
	 * @since 2.42
	 *
	 * @var string
	 */
	protected static string $type = 'unknown';

	/**
	 * The search field type.
	 *
	 * @since 2.42
	 *
	 * @var string
	 */
	protected static string $field_type = 'text';

	/**
	 * The Field description.
	 *
	 * @since 2.42
	 *
	 * @var string
	 */
	protected string $description = '';

	/**
	 * The icon.
	 *
	 * @since 2.42
	 *
	 * @var string
	 */
	protected string $icon = '';

	/**
	 * The View object.
	 *
	 * @since 2.42
	 *
	 * @var View|null
	 */
	protected ?View $view = null;

	/**
	 * Microcache of the connected field instance.
	 *
	 * @since 2.42
	 *
	 * @var GF_Field|null
	 */
	protected ?GF_Field $field = null;

	/**
	 * The Context object.
	 *
	 * @since 2.42
	 *
	 * @var Context|null
	 */
	protected ?Context $context = null;

	/**
	 * The widget args.
	 *
	 * @since 2.42
	 *
	 * @var array
	 */
	protected array $widget_args = [];

	/**
	 * The UID of the widget area.
	 *
	 * @since 2.42
	 *
	 * @var string
	 */
	protected string $UID = '';

	/**
	 * A list of default settings keys.
	 *
	 * @since 2.42
	 */
	protected const DEFAULT_SETTINGS = [
		'custom_label',
		'custom_class',
		'show_label',
		'input_type',
		'only_loggedin',
		'only_loggedin_cap',
	];

	/**
	 * Returns the keys from the data array that are considered settings.
	 *
	 * @since 2.42
	 *
	 * @return string[]
	 */
	protected function setting_keys(): array {
		return array_merge(
			self::DEFAULT_SETTINGS,
			array_keys( $this->get_options() ),
		);
	}

	/**
	 * Creates the base field instance.
	 *
	 * @since 2.42
	 *
	 * @param string|null $label           The name of the field.
	 * @param array       $data            The configuration of the field.
	 * @param bool        $call_initialize Whether to call the `init()` method.
	 */
	public function __construct( ?string $label = null, array $data = [], bool $call_initialize = true ) {
		$data = wp_parse_args(
			$data,
			[
				'show_label' => true,
			]
		);

		parent::__construct(
			$label ?? $this->get_name(),
			$this->get_type(),
			$data,
			array_intersect_key( $data, array_flip( $this->setting_keys() ) ),
		);

		if ( $call_initialize ) {
			$this->init();
		}
	}

	/**
	 * Returns the icon for the field.
	 *
	 * @since 2.42
	 *
	 * @return string
	 */
	public function icon_html(): string {
		$icon = $this->get_icon();
		if ( ! $icon ) {
			return '';
		}

		$is_gf_icon  = ( false !== strpos( $icon, 'gform-icon' ) && gravityview()->plugin->is_GF_25() );
		$is_dashicon = ( false !== strpos( $icon, 'dashicons' ) );

		$html = '<i class="%s"></i>';
		if ( 0 === strpos( $icon, 'data:' ) ) {
			$html = '<i class="dashicons background-icon" style="background-image: url(\'%s\');"></i>';
		} elseif ( $is_gf_icon ) {
			$html = '<i class="gform-icon %s"></i>';
		} elseif ( $is_dashicon ) {
			$html = '<i class="dashicons %s"></i>';
		}

		return sprintf( $html, esc_attr( $icon ) );
	}

	/**
	 * Returns whether this field matches the provided configuration.
	 *
	 * @since 2.42
	 *
	 * @param array $configuration The configuration.
	 *
	 * @return bool Whether this field matches the provided configuration.
	 */
	public function satisfies( array $configuration ): bool {
		if ( self::class === static::class ) {
			return false;
		}

		return ( $configuration['id'] ?? '' ) === $this->get_type();
	}

	/**
	 * Returns the Field instance based on the data.
	 *
	 * @since 2.42
	 *
	 * @param array     $data               The field data.
	 * @param View|null $view               The View object.
	 * @param array     $additional_context Any additional context.
	 *
	 * @return static|null The field instance.
	 */
	public static function from_configuration(
		array $data,
		?View $view = null,
		array $additional_context = []
	): ?Search_Field {
		// Can't instantiate the abstract class, but we can use it as a factory.
		if ( static::class === self::class ) {
			$fields = Search_Field_Collection::available_fields( (int) ( $data['form_id'] ?? 0 ) );

			foreach ( $fields as $field ) {
				if ( ! $field->satisfies( $data ) ) {
					continue;
				}

				$configuration = $field->to_configuration();
				$class         = get_class( $field );
				// Merge default data with explicit data.
				$data = array_merge( $configuration, $data );

				if ( ! is_a( $class, self::class, true ) ) {
					return null;
				}

				return $class::from_configuration( $data, $view, $additional_context );
			}

			return null;
		}

		$field       = new static( $data['label'] ?? null, $data );
		$field->view = $view;

		unset( $data['type'] );

		foreach ( array_merge( $data, $additional_context ) as $key => $value ) {
			if ( property_exists( $field, $key ) ) {
				if ( 'context' === $key && ! $value instanceof Context ) {
					continue;
				}

				$field->{$key} = $value;
			}
		}

		if ( ! $field->UID ) {
			$field->UID = Grid::uid();
		}

		$field->init();

		return $field;
	}

	/**
	 * Returns the options merged onto the existing options array.
	 *
	 * @since 2.42
	 *
	 * @param array $options The existing options.
	 *
	 * @return array The updated options.
	 */
	public function merge_options( array $options ): array {
		if ( isset( $options['custom_label'] ) ) {
			$options['custom_label']['placeholder'] = $this->get_default_label();
		}

		if ( in_array( static::class, [ Search_Field_Submit::class, Search_Field_Search_Mode::class ], true ) ) {
			unset( $options['only_loggedin'], $options['only_loggedin_cap'] );
		}

		$field_options = array_merge( $this->get_search_field_options(), $this->get_options() );
		array_walk(
			$field_options,
			static function ( &$value ) {
				$value['contexts']   ??= [];
				$value['contexts'][] = 'search';
			}
		);

		return array_merge( $options, $field_options );
	}

	/**
	 * Returns the options for this field.
	 *
	 * @since 2.42
	 *
	 * @return array
	 */
	protected function get_options(): array {
		return [];
	}

	/**
	 * Returns the available input types for this search field.
	 *
	 * @since 2.42
	 *
	 * @return string[]
	 */
	protected function get_input_types(): array {
		$input_types_mapping = GravityView_Widget_Search::get_input_types_by_field_type();

		return $input_types_mapping[ $this->get_field_type() ] ?? $input_types_mapping['text'];
	}

	/**
	 * Returns the default settings for every search field.
	 *
	 * @since 2.42
	 *
	 * @return array[]
	 */
	private function get_search_field_options(): array {
		$options = [];

		$input_types = $this->get_input_types();

		if ( $input_types ) {
			$options['input_type'] = [
				'type'     => count( $input_types ) > 1 ? 'select' : 'hidden',
				'label'    => esc_html__( 'Input type', 'gk-gravityview' ),
				'value'    => current( $input_types ),
				'class'    => 'widefat',
				'priority' => 1150,
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
	 * Returns the field as a configuration array.
	 *
	 * @since 2.42
	 *
	 * @return array The configuration.
	 */
	public function to_configuration(): array {
		$configuration = [
			'id'       => $this->get_key(),
			'UID'      => $this->UID,
			'type'     => $this->get_type(),
			'label'    => $this->title,
			'position' => $this->position,
			'form_id'  => $this->form_id,
		];

		foreach ( $this->setting_keys() as $key ) {
			if ( isset( $this->settings[ $key ] ) ) {
				$configuration[ $key ] = $this->settings[ $key ];
			}
		}

		return $configuration;
	}

	/**
	 * Returns the label used on the frontend.
	 *
	 * @since 2.42
	 *
	 * @return string
	 */
	public function get_frontend_label(): string {
		if ( ! ( $this->settings['show_label'] ?? true ) ) {
			return '';
		}

		$label = $this->settings['custom_label'] ?? '';
		if ( ! $label ) {
			$label = $this->get_default_label();
		}

		$form_field     = $this->field ? GFFormsModel::get_field( $this->form_id, $this->get_key() ) : [];
		$field          = $this->to_legacy_format();
		$field['label'] = $label;

		/**
		 * Modify the label for a search field. Supports returning HTML.
		 *
		 * @since 1.17.3 Added $field parameter
		 *
		 * @param string $label      Existing label text, sanitized.
		 * @param array  $form_field Gravity Forms field array, as returned by `GFFormsModel::get_field()`
		 * @param array  $field      Field setting as sent by the GV configuration - has `field`, `input` (input type), and `label` keys
		 */
		$label = apply_filters( 'gravityview_search_field_label', esc_attr( $label ), $form_field, $field );

		return $label;
	}

	/**
	 * Returns the unique type of this field.
	 *
	 * @since 2.42
	 *
	 * @return string The type.
	 */
	public function get_type(): string {
		return static::$type;
	}

	/**
	 * Returns the field type.
	 *
	 * @since 2.42
	 *
	 * @return string
	 */
	protected function get_field_type(): string {
		return static::$field_type;
	}

	/**
	 * Returns the field type.
	 *
	 * @since 2.42
	 *
	 * @return string
	 */
	protected function get_input_type(): string {
		$type        = $this->settings['input_type'] ?? 'input_text';
		$input_types = $this->get_input_types();

		if ( ! $input_types ) {
			return 'input_text';
		}

		return in_array( $type, $input_types, true ) ? $type : reset( $input_types );
	}

	/**
	 * Recursively filters out empty values from an array while preserving '0'.
	 *
	 * @since 2.42
	 *
	 * @param array $input The array to filter.
	 *
	 * @return array The filtered array.
	 */
	protected function filter_empty_values( array $input ): array {
		return array_filter(
			$input,
			function ( $value ) {
				if ( is_array( $value ) ) {
					$filtered = $this->filter_empty_values( $value );

					return ! empty( $filtered );
				}

				return '' !== $value && null !== $value;
			}
		);
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_title( string $label ): string {
		// Translators: %s will contain the label of the field.
		return sprintf( __( 'Search Field: %s', 'gk-gravityview' ), $label );
	}

	/**
	 * Returns the label for this field.
	 *
	 * @since 2.42
	 */
	protected function get_name(): string {
		return $this->title ?? esc_html( 'Unknown Field', 'gk-gravityview' );
	}

	/**
	 * Returns the default label for this field.
	 *
	 * @since 2.42
	 *
	 * @return string The default label.
	 */
	protected function get_default_label(): string {
		return $this->title;
	}

	/**
	 * Returns the description for this field.
	 *
	 * @since 2.42
	 */
	public function get_description(): string {
		return '';
	}

	/**
	 * Returns the icon.
	 *
	 * @since 2.42
	 *
	 * @return string
	 */
	protected function get_icon(): string {
		return (string) ( $this->icon ? $this->icon : $this->item['icon'] );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 * @return array{value: string, label?: string, class?: string, hide_in_picker?: bool}
	 */
	protected function additional_info(): array {
		$description = $this->get_description();
		if ( ! $description ) {
			return [];
		}

		$field_info_items = [
			[ 'value' => $description ],
		];

		return $field_info_items;
	}

	/**
	 * Registers the required hooks.
	 *
	 * @since 2.42
	 */
	protected function init(): void {
		$this->item['icon']        = $this->get_icon();
		$this->item['description'] = $this->get_description();

		$this->form_id = $this->view ? $this->view->form->ID : $this->form_id;
	}

	/**
	 * Returns whether the search field is of the provided type.
	 *
	 * @since 2.42
	 *
	 * @param string $type The type.
	 *
	 * @return bool Whether the search field is of the provided type.
	 */
	public function is_of_type( string $type ): bool {
		return $type === static::$type;
	}

	/**
	 * Returns whether the search field is a searchable field.
	 *
	 * @since 2.42
	 *
	 * @return bool Whether the search field is a searchable field.
	 */
	public function is_searchable_field(): bool {
		return true;
	}

	/**
	 * Returns the name of the input on the form.
	 *
	 * @since 2.42
	 *
	 * @return string
	 */
	protected function get_input_name(): string {
		return sprintf( 'filter_%s', str_replace( '.', '_', $this->get_key() ) );
	}

	/**
	 * Returns the value(s) for the field input.
	 *
	 * @since 2.42
	 *
	 * @return T
	 */
	protected function get_input_value() {
		return $this->get_request_value( $this->get_input_name() ) ?? '';
	}

	/**
	 * Retrieve the field value from the current request.
	 *
	 * @since 2.42
	 *
	 * @param string $name    the param name.
	 * @param mixed  $default The default value if not found.
	 *
	 * @return mixed The value.
	 */
	protected function get_request_value( string $name, $default = null ) {
		$value = \GV\Utils::_REQUEST( $name, $default );

		$value = stripslashes_deep( $value );

		if ( ! is_null( $value ) ) {
			$value = gv_map_deep( $value, 'rawurldecode' );
		}

		$value = gv_map_deep( $value, '_wp_specialchars' );

		return $value;
	}

	/**
	 * Collects the data needed for the template to render.
	 *
	 * @since 2.42
	 *
	 * @return array
	 */
	protected function collect_template_data(): array {
		$params = [
			'key'          => $this->get_key(),
			'name'         => $this->get_input_name(),
			'label'        => $this->get_frontend_label(),
			'value'        => $this->get_input_value(),
			'type'         => $this->get_type(),
			'input'        => $this->get_input_type(),
			'custom_class' => gravityview_sanitize_html_class( $this->settings['custom_class'] ?? '' ),
		];

		foreach ( $this->get_options() as $key => $option ) {
			$params[ $key ] = $this->item[ $key ] ?? $option['value'] ?? null;
		}

		return $params;
	}

	/**
	 * Returns the data needed for the template to render.
	 *
	 * This method is final to ensure consistent filtering behavior across all implementations.
	 * Extending classes should override {@see self::collect_template_data()} to modify the data.
	 *
	 * @since 2.42
	 *
	 * @return array
	 */
	final public function to_template_data(): array {
		$filter         = $this->collect_template_data();
		$field          = $this->to_legacy_format();
		$field['label'] = $this->get_frontend_label();

		/**
		 * Filter the output filter details for the Search widget.
		 *
		 * @since 2.5
		 *
		 * @param array $filter The filter details
		 * @param array $field  The search field configuration
		 * @param \GV\Context|null The context
		 */
		return apply_filters( 'gravityview/search/filter_details', $filter, $field, $this->context );
	}

	/**
	 * Returns the search field in the legacy format.
	 *
	 * @since 2.42
	 *
	 * @return array{field: string, label:string, input_type:string} The search field in the legacy format.
	 */
	public function to_legacy_format(): array {
		return [
			'field' => $this->get_key(),
			'input' => $this->get_input_type(),
			'title' => $this->get_name(),
		];
	}

	/**
	 * Returns the field key.
	 *
	 * @since 2.42
	 */
	protected function get_key(): string {
		return $this->get_type();
	}

	/**
	 * The capability required for this search field.
	 *
	 * @since 2.42
	 *
	 * @return string|null The name of the capability.
	 */
	protected function required_cap(): ?string {
		if ( ! ( $this->settings['only_loggedin'] ?? false ) ) {
			return null;
		}

		return $this->settings['only_loggedin_cap'] ?? 'read';
	}

	/**
	 * Returns whether the search field is visible for the current user.
	 *
	 * @since 2.42
	 *
	 * @return bool Whether the field is visible.
	 */
	final public function is_visible(): bool {
		$cap = $this->required_cap();

		return apply_filters(
			'gk/gravityview/search/field/is_visible',
			( null === $cap || \GVCommon::has_cap( $cap ) ),
			$this,
			$this->view
		);
	}

	/**
	 * Returns whether the field has a request value.
	 *
	 * @since 2.42
	 */
	public function has_request_value(): bool {
		$value = $this->get_input_value();
		if ( is_string( $value ) && '' !== $value ) {
			return true;
		}

		if ( is_array( $value ) ) {
			$filtered = $this->filter_empty_values( $value );

			return [] !== $filtered;
		}

		return false;
	}

	/**
	 * Returns whether the field is allowed to be used once in a search.
	 *
	 * @since 2.42
	 *
	 * @return bool Whether the field is allowed to be used once in a search.
	 */
	protected function is_allowed_once(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function can_duplicate(): bool {
		if ( $this->is_allowed_once() ) {
			return false;
		}

		return parent::can_duplicate();
	}

	/**
	 * Returns the sections where the field is allowed to be used.
	 *
	 * @since 2.42
	 *
	 * @return string[]
	 */
	protected function allowed_sections(): array {
		$sections = [
			'search-general',
			'search-advanced',
		];

		/**
		 * @filter `gk/gravityview/search/field/allowed_sections` Modifies the sections where the field is allowed to be used.
		 *
		 * @since 2.42
		 *
		 * @param string[]     $sections The sections.
		 * @param Search_Field $field    The search field.
		 *
		 * @return string[] The modified sections.
		 */
		$sections = (array) apply_filters( 'gk/gravityview/search/field/allowed_sections', $sections, $this );

		return array_map( 'esc_attr', $sections );
	}

	/**
	 * Returns whether the field is allowed to be used in the provided section.
	 *
	 * @since 2.42
	 *
	 * @param string $section The section.
	 *
	 * @return bool Whether the field is allowed to be used in the provided section.
	 */
	final public function is_allowed_for_section( string $section ): bool {
		return in_array( $section, $this->allowed_sections(), true );
	}

	/**
	 * @inheritDoc
	 *
	 * Adds the required data attributes to the output.
	 *
	 * @since 2.42
	 */
	final public function getOutput(): string {
		$replace = [
			$this->is_allowed_once() ? 'data-allowed-once="true"' : null,
			sprintf( 'data-allowed-sections="%s"', implode( ',', $this->allowed_sections() ) ),
			'data-fieldid=',
		];

		return str_replace( 'data-fieldid=', implode( ' ', array_filter( $replace ) ), parent::getOutput() );
	}
}
