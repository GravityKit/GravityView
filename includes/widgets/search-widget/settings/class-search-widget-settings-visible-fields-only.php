<?php
/**
 * Setting that limits the search results of "Search Everything" to visible fields only.
 *
 * @since     2.42
 *
 * @package   GravityView
 * @license   GPL2+
 * @link      http://www.gravitykit.com
 */

use GV\Field;
use GV\GF_Form;
use GV\Internal_Field;
use GV\View;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Manages the Query to limit Search Everything searches to visible fields only.
 *
 * @since 2.42
 */
final class GravityView_Search_Widget_Settings_Visible_Fields_Only {
	/**
	 * Holds the single instance.
	 *
	 * @since 2.42
	 *
	 * @var self
	 */
	private static self $instance;

	/**
	 * Whether the current query should be skipped.
	 *
	 * @since 2.42
	 *
	 * @var int
	 */
	private int $skipped = 0;

	/**
	 * Holds the visible fields per form on the View.
	 *
	 * @since 2.42
	 *
	 * @var array<int, array<int|string>>
	 */
	private array $fields;

	/**
	 * Holds search fields per View.
	 *
	 * @since 2.42
	 *
	 * @var array
	 */
	private array $searchable_fields = [];

	/**
	 * Returns the singleton.
	 *
	 * @since 2.42
	 */
	public static function get_instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Prevent multiple instances.
	 *
	 * @since 2.42
	 */
	private function __construct() {
		add_action( 'gravityview/view/query', [ $this, 'maybe_update_search_condition' ], 2048, 2 );
		add_action( 'gravityview/search/searchable_fields/allowlist', [ $this, 'record_searchable_fields' ], 2048, 3 );
	}

	/**
	 * Updates the search condition to limit the search to visible fields only.
	 *
	 * @since 2.42
	 *
	 * @param GF_Query $query The Query.
	 * @param View     $view  The View.
	 */
	public function maybe_update_search_condition( GF_Query &$query, View $view ): void {
		$where = $query->_introspect()['where'] ?? null;

		// If the current user can search everything, we don't need to do anything.
		if ( ! $where || ! $this->is_search_limited( $view ) ) {
			return;
		}

		$visible_fields = $this->get_visible_fields( $view );
		$condition      = $this->replace_condition( $query, $where, $visible_fields );

		$query->where( $condition );
	}

	/**
	 * Records the searchable fields for the current View.
	 *
	 * Note: This is somewhat hacky. It could be better if we made the search fields available with a public function on
	 * the Search Widget.
	 *
	 * @todo Refactor to use a public API for accessing search fields from the Search Widget.
	 *
	 * @param array $searchable_fields The searchable fields to record.
	 * @param View  $view              The View.
	 * @param bool  $with_full_field   Whether the searchable fields array includes the full field.
	 *
	 * @return array The unchanged searchable fields.
	 */
	public function record_searchable_fields( array $searchable_fields, $view, $with_full_field ): array {
		if ( $with_full_field && $view instanceof View && $this->is_search_limited( $view ) ) {
			$this->searchable_fields[ $view->ID ] = $searchable_fields;
		}

		return $searchable_fields;
	}

	/**
	 * Returns if the search is limited to visible fields only.
	 *
	 * @since 2.42
	 *
	 * @param View $view The View.
	 *
	 * @return bool Whether the current user can search everything.
	 */
	private function is_search_limited( View $view ): bool {
		if ( $this->skipped > 0 ) {
			return false;
		}

		$is_visible_fields_only = $view->settings->get( 'search_visible_fields', 0 );

		/**
		 * @filter `gk/gravityview/widget/search/visible_fields_only` Modify the search capability of "Search Everything".
		 *
		 * @since 2.42
		 *
		 * @param bool $is_visible_fields_only Whether the search capability of "Search Everything" is limited to visible fields only.
		 * @param View $view                   The View.
		 */
		return (bool) apply_filters(
			'gk/gravityview/widget/search/visible_fields_only',
			$is_visible_fields_only,
			$view
		);
	}

	/**
	 * Returns if the condition is a condition group.
	 *
	 * @since 2.42
	 *
	 * @param GF_Query_Condition $condition The condition to check.
	 *
	 * @return bool Whether the condition is a condition group.
	 */
	private function is_condition_group( GF_Query_Condition $condition ): bool {
		return in_array( $condition->operator, [ GF_Query_Condition::_AND, GF_Query_Condition::_OR ], true );
	}

	/**
	 * Returns if the condition is an excluded field.
	 *
	 * @since 2.42
	 *
	 * @param GF_Query_Condition $condition The condition to check.
	 * @param array              $fields    The visible fields.
	 *
	 * @return bool Whether the condition is an excluded field.
	 */
	private function is_excluded_field( GF_Query_Condition $condition, array $fields ): bool {
		$left  = $condition->left ?? null;
		$right = $condition->right ?? null;

		if (
			null === $right // Might be used for a join.
			|| ! $left instanceof GF_Query_Column // Broken condition.
			|| GF_Query_Column::META === $left->field_id // Search everything.
		) {
			return false;
		}

		// Check for field ID or field wild card in visible fields.
		$ids   = [ $left->field_id, $left->field_id . '.%' ];
		$found = array_intersect( $ids, $fields[ $condition->left->source ] ?? [] );

		// If neither is found, the field is excluded.
		return [] === $found;
	}

	/**
	 * Returns if the condition is a "Search Everything" condition.
	 *
	 * @since 2.42
	 *
	 * @param GF_Query_Condition $condition The condition to check.
	 *
	 * @return bool Whether the condition is a "Search Everything" condition.
	 */
	private function is_search_everything_condition( GF_Query_Condition $condition ): bool {
		return $condition->left instanceof GF_Query_Column && GF_Query_Column::META === $condition->left->field_id;
	}

	/**
	 * Returns if the condition's field ID is in the visible fields.
	 *
	 * @since 2.42
	 *
	 * @param GF_Query_Condition $condition The condition to check.
	 * @param array              $fields    The visible fields.
	 *
	 * @return bool Whether the condition's field ID is in the visible fields.
	 */
	private function is_in_configured_forms( GF_Query_Condition $condition, array $fields ): bool {
		return $condition->left instanceof GF_Query_Column && array_key_exists( $condition->left->source, $fields );
	}

	/**
	 * Recursively replaces the entire query conditions tree.
	 *
	 * @since 2.42
	 *
	 * @param GF_Query                      $query     The Query.
	 * @param GF_Query_Condition            $condition The condition to update.
	 * @param array<int, array<int|string>> $fields    The field configuration.
	 *
	 * @return GF_Query_Condition
	 */
	private function replace_condition(
		GF_Query $query,
		GF_Query_Condition $condition,
		array $fields
	): ?GF_Query_Condition {
		// Traverse down the condition tree when it is a group.
		if ( $this->is_condition_group( $condition ) ) {
			$expressions = array_map(
				fn( GF_Query_Condition $expression ) => $this->replace_condition( $query, $expression, $fields ),
				$condition->expressions ?? []
			);

			return GF_Query_Condition::_AND === $condition->operator
				? GF_Query_Condition::_and( ...$expressions )
				: GF_Query_Condition::_or( ...$expressions );
		}

		// If the form is not configured explicitly, include regular condition.
		if ( ! $this->is_in_configured_forms( $condition, $fields ) ) {
			return $condition;
		}

		// Remove excluded fields from the condition.
		if ( $this->is_excluded_field( $condition, $fields ) ) {
			return null;
		}

		// If the condition is not a "Search Everything" condition, we don't need to do anything.
		if ( ! $this->is_search_everything_condition( $condition ) ) {
			return $condition;
		}

		$source = $condition->left->source;
		// If an array is set, but it is empty; it should exclude ALL values; so we replace it with an invalid statement.
		if ( ! $fields[ $source ] ) {
			return new GF_Query_Condition(
				$condition->left,
				GF_Query_Condition::EQ,
				new GF_Query_Literal( '__GK_NO_MATCH__' ),
			);
		}

		$exact = [];
		$like  = [];

		foreach ( $fields[ $source ] as $field_id ) {
			if ( false !== strpos( $field_id, '%' ) ) {
				$like[] = $field_id;
			} else {
				$exact[] = $field_id;
			}
		}

		$meta_key_column = new GF_Query_Column(
			'meta_key',
			$condition->left->source,
			$query->_alias( GF_Query_Column::META, $source, 'm' )
		);

		$new_conditions = [];

		if ( $exact ) {
			$new_conditions[] = new GF_Query_Condition(
				$meta_key_column,
				GF_Query_Condition::IN,
				new GF_Query_Series(
					array_map(
						static fn( $field_id ) => new GF_Query_Literal( $field_id ),
						$exact
					)
				)
			);
		}

		if ( $like ) {
			foreach ( $like as $field_id ) {
				$new_conditions[] = new GF_Query_Condition(
					$meta_key_column,
					GF_Query_Condition::LIKE,
					new GF_Query_Literal( $field_id )
				);
			}
		}

		// This should not be possible.
		if ( ! $new_conditions ) {
			return $condition;
		}

		$strict_condition = count( $new_conditions ) === 1
			? reset( $new_conditions )
			: GF_Query_Condition::_or( ...$new_conditions );

		return GF_Query_Condition::_and( $condition, $strict_condition );
	}

	/**
	 * Returns if the field is an edit field.
	 *
	 * @since 2.42
	 *
	 * @param Field $field The field object.
	 *
	 * @return bool Whether the field is an edit field.
	 */
	private static function is_edit_field( Field $field ): bool {
		return strpos( $field->position ?? '', 'edit_' ) === 0;
	}
	/**
	 * Returns the visible fields per form on the View.
	 *
	 * @since 2.42
	 *
	 * @param View $view The View.
	 *
	 * @return array<int, array<int|string>> The visible fields per form on the View.
	 */
	private function get_visible_fields( View $view ): array {
		if ( isset( $this->fields[ $view->ID ] ) ) {
			return $this->fields[ $view->ID ];
		}

		++$this->skipped; // Prevent infinite loop.

		$fields = array_reduce(
			$view->fields->by_visible()->all(),
			static function ( array $fields, Field $field ) {
				if (
					( $field instanceof Internal_Field && 'meta' === $field->field->group )
					|| self::is_edit_field( $field )
				) {
					return $fields;
				}

				$configuration = $field->as_configuration();

				$field_id = $configuration['id'];
				if (
					false === strpos( $field_id, '.' )
					&& $field->field instanceof GF_Field
					&& $field->field->get_entry_inputs()
				) {
					$field_id .= '.%';
				}
				$fields[ $configuration['form_id'] ?? 0 ][] = $field_id;

				return $fields;
			},
			[]
		);

		$form_ids = array_column( View::get_joined_forms( $view->ID ), 'ID' );

		// Include fields that are allowed as search fields.
		foreach ( $this->searchable_fields[ $view->ID ] ?? [] as $field_data ) {
			$fields[ $field_data['form_id'] ][] = $field_data['field'];
			// Likely a choice field, so include `field.%` as allowed.
			if (
				'input_text' !== $field_data['input']
				&& false === strpos( $field_data['field'], '.' )
			) {
				$fields[ $field_data['form_id'] ][] = $field_data['field'] . '.%';
			}
		}

		// Make sure all joined forms are in the array.
		foreach ( $form_ids as $form_id ) {
			$fields[ $form_id ] = array_values( array_unique( $fields[ $form_id ] ?? [] ) );
		}

		--$this->skipped;

		// Cache and return.
		$this->fields[ $view->ID ] = $fields;

		return $fields;
	}

	/**
	 * Clears the visible fields cache.
	 *
	 * @since 2.42
	 *
	 * @internal Do not rely on this method. It could change at any time.
	 */
	public static function clear_cache(): void {
		$instance         = self::get_instance();
		$instance->fields = [];
	}
}

GravityView_Search_Widget_Settings_Visible_Fields_Only::get_instance();
