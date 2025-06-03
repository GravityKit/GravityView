<?php

namespace GV\Search\Fields;

use GF_Field;
use GF_Query_Column;
use GFAPI;
use GFCommon;
use GFFormsModel;
use GravityView_Widget_Search;

/**
 * Represents a search field based on a Gravity Forms Field.
 *
 * @since $ver$
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Gravity_Forms extends Search_Field_Choices {
	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	protected static string $type = 'gravity_forms';

	/**
	 * The inner field object.
	 *
	 * @since $ver$
	 *
	 * @var array
	 */
	public array $form_field = [];

	/**
	 * @inheritDoc
	 *
	 * Make only allow named constructors for this field, due to the dependencies.
	 *
	 * @since $ver$
	 */
	protected function __construct( ?string $label = null, array $data = [] ) {
		// Do not initialize here, it will be called on the named constructors to parse dependencies.
		parent::__construct( $label, $data, false );
	}

	/**
	 * Creates an instance based on a field object.
	 *
	 * @since $ver$
	 *
	 * @param array $field The field object.
	 *
	 * @return self The instance.
	 */
	public static function from_field( array $field ): ?self {
		if ( empty( $field ) ) {
			return null;
		}

		$instance             = new self( $field['label'] ?? '' );
		$instance->form_field = $field;
		$gf_field             = GFAPI::get_field( $field['form_id'] ?? 0, $field['id'] ?? 0 );

		if ( $gf_field ) {
			// Clone to have a copy per field for immutability.
			$instance->field = clone $gf_field;
			// Set remaining params, like `parent` and `id`.
			foreach ( $field as $param => $value ) {
				$instance->field->{$param} = $value;
			}
		}

		$instance->id = $instance->get_type();

		$instance->init();

		return $instance;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_name(): string {
		return esc_html__( 'Gravity Forms Field', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_description(): string {
		return esc_html__( 'Gravity Forms Field', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function to_configuration(): array {
		return array_merge(
			parent::to_configuration(),
			[
				'form_field' => $this->form_field,
			]
		);
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_type(): string {
		if ( isset( $this->item['id'] ) ) {
			return $this->item['id'];
		}

		return self::generate_field_id(
			(int) ( $this->form_field['form_id'] ?? 0 ),
			(string) ( $this->form_field['id'] ?? '0' )
		);
	}

	/**
	 * Generates a valid Search Field ID.
	 *
	 * @since $ver$
	 *
	 * @param int    $form_id  The form ID.
	 * @param string $field_id The field ID.
	 *
	 * @return string The Search Field ID.
	 */
	public static function generate_field_id( int $form_id, string $field_id ): string {
		return sprintf( '%s::%d::%s', self::$type, $form_id, $field_id );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function init(): void {
		parent::init();

		$this->item['icon'] = $this->get_field_icon();

		$field = $this->get_gf_field();
		if ( ! $field ) {
			return;
		}

		$this->item['parent'] = $field['parent'] ?? null;
	}

	/**
	 * Returns the icon for the Gravity Forms Field.
	 *
	 * @since $ver$
	 *
	 * @return string The icon class name.
	 */
	private function get_field_icon(): string {
		$field = $this->get_gf_field();
		if ( $field ) {
			return $field->get_form_editor_field_type_icon();
		}

		$type = $this->get_field_id();

		switch ( $type ) {
			case 'is_fulfilled':
				return 'dashicons-yes-alt';
			case 'currency':
				return 'dashicons-money-alt';
			case 'payment_amount':
				return 'gform-icon--numbers-alt';
			case 'payment_date':
				return 'dashicons-calendar-alt';
			case 'payment_method':
				return 'dashicons-products';
			case 'payment_status':
				return 'dashicons-visibility';
			case 'geolocation':
				return 'dashicons-admin-site';
			default:
				return 'dashicons-admin-generic';
		}
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function is_of_type( string $type ): bool {
		return $this->get_type() === $type;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_input_name(): string {
		$field_id = $this->get_field_id();

		return sprintf( 'filter_%s', str_replace( '.', '_', $field_id ) );
	}

	/**
	 * Returns the field ID.
	 *
	 * @since $ver$
	 *
	 * @return string The field ID.
	 */
	private function get_field_id(): string {
		$parts = explode( '::', (string) ( $this->id ?? '' ) );
		if ( count( $parts ) !== 3 ) {
			return '0';
		}

		return (string) $parts[2];
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function has_choices(): bool {
		$field = $this->get_gf_field();
		if ( $field ) {
			$choices = $field->choices ?? [];

			$has_choices = is_array( $choices ) && count( $choices ) > 0;
			if ( $has_choices ) {
				return true;
			}
		}

		return in_array(
			$field ? $field->type : $this->get_field_id(),
			[
				'payment_status',
				'post_category',
			],
			true
		);
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function is_sievable(): bool {
		return $this->has_choices() && ! $this->is_child();
	}

	/**
	 * Whether this field has a parent.
	 *
	 * @since $ver$
	 *
	 * @return bool
	 */
	private function is_child(): bool {
		$field = $this->get_gf_field();
		if ( ! $field ) {
			return false;
		}

		return ( $field->parent ?? null ) instanceof GF_Field;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_field_type(): string {
		$field = $this->get_gf_field();

		return GravityView_Widget_Search::get_search_input_types(
			$this->get_field_id(),
			$field ? $field['type'] : null
		);
	}

	/**
	 * @inheritDoc@
	 * @since     $ver$
	 */
	protected function get_choices(): array {
		$field = $this->get_gf_field();
		if ( $field && ! empty( $field->choices ?? [] ) ) {
			return $field->choices;
		}

		$field_type = $field ? $field->type : $this->get_field_id();
		switch ( $field_type ) {
			case 'payment_status':
				return GFCommon::get_entry_payment_statuses_as_choices();
			case 'post_category':
				return gravityview_get_terms_choices();
			default:
				return [];
		}
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_sieved_values(): array {
		global $wpdb;

		$form_id               = $this->view->form->ID;
		$field_id              = $this->get_field_id();
		$entry_table_name      = GFFormsModel::get_entry_table_name();
		$entry_meta_table_name = GFFormsModel::get_entry_meta_table_name();

		$column = new GF_Query_Column( $field_id, $form_id );

		if ( $column->is_entry_column() ) {
			$choices = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT `{$field_id}` FROM `$entry_table_name` WHERE `form_id` = %d",
					$form_id
				)
			);
		} else {
			$key_like = $wpdb->esc_like( $field_id ) . '.%';
			$choices  = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT `meta_value` FROM $entry_meta_table_name WHERE ( `meta_key` LIKE %s OR `meta_key` = %s ) AND `form_id` = %d",
					$key_like,
					$field_id,
					$form_id
				)
			);

			$field = $this->get_gf_field();

			if ( $field && 'json' === ( $field['storageType'] ?? '' ) ) {
				$choices        = array_map( 'json_decode', $choices );
				$_choices_array = [];
				foreach ( $choices as $choice ) {
					if ( ! is_array( $choice ) ) {
						$choice = [ $choice ];
					}

					$_choices_array[] = $choice;
				}

				$choices = array_unique( array_merge( [], ...$_choices_array ) );
			}

			if ( 'post_category' === $field->type ) {
				$choices = array_map(
					static function ( $choice ): string {
						$parts = explode( ':', $choice );

						return reset( $parts );
					},
					$choices
				);
			}
		}

		return $choices;
	}

	/**
	 * Retrieve the Gravity Forms field connected to this search field.
	 *
	 * @since $ver$
	 *
	 * @return GF_Field|null The Gravity Forms field.
	 */
	private function get_gf_field(): ?GF_Field {
		if ( $this->field ) {
			return $this->field;
		}

		$parts = explode( '::', (string) ( $this->id ?? '' ) );
		if ( count( $parts ) !== 3 ) {
			return null;
		}

		[ , $form_id, $field_id ] = $parts;

		$field       = GFAPI::get_field( $form_id, $field_id );
		$this->field = $field ? $field : null;

		return $this->field;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_key(): string {
		$field_id = $this->get_field_id();
		if ( ! $field_id ) {
			return parent::get_key();
		}

		return $field_id;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_input_value() {
		$value = parent::get_input_value();
		if ( empty( $value ) ) {
			if ( 'date_range' === $this->get_input_type() ) {
				$value = [
					'start' => '',
					'end'   => '',
				];
			} elseif ( 'number_range' === $this->get_input_type() ) {
				$value = [
					'min' => '',
					'max' => '',
				];
			}
		}

		return $value;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function to_legacy_format(): array {
		$data = parent::to_legacy_format();

		$field = $this->get_gf_field();
		if ( $field ) {
			$data['form_id'] = $field['formId'] ?? null;
		}

		return $data;
	}
}
