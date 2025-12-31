<?php

namespace GV\Search\Fields;

use GFFormsModel;
use GVCommon;

/**
 * Represents a search field that searches on the Entry Creator.
 *
 * @since 2.42
 *
 * @extends Search_Field<string>
 */
final class Search_Field_Created_By extends Search_Field_Choices {
	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected string $icon = 'dashicons-admin-users';

	/**
	 * @inheritdoc
	 * @since 2.42
	 */
	protected static string $type = 'created_by';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected static string $field_type = 'created_by';

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_name(): string {
		return esc_html__( 'Entry Creator', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	public function get_description(): string {
		return esc_html__( 'Search on entry creator', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_default_label(): string {
		return esc_html__( 'Submitted by:', 'gk-gravityview' );
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_input_name(): string {
		return 'gv_by';
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function has_choices(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function is_sievable(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_choices(): array {
		$users = GVCommon::get_users( 'search_widget', [ 'fields' => [ 'ID', 'display_name' ] ] );

		$choices = [];
		foreach ( $users as $user ) {
			/**
			 * Filter the display text in created by search choices.
			 *
			 * @since 2.3
			 *
			 * @param string The text. Default: $user->display_name
			 * @param \WP_User      $user The user.
			 * @param \GV\View|null $view The view.
			 */
			$text = apply_filters(
				'gravityview/search/created_by/text',
				$user->display_name,
				$user,
				$this->view
			);

			$choices[] = [
				'value' => $user->ID,
				'text'  => $text,
			];
		}

		return $choices;
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_sieved_values(): array {
		global $wpdb;

		$entry_table_name = GFFormsModel::get_entry_table_name();
		$form_id          = $this->view->form->ID;

		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT `created_by` FROM $entry_table_name WHERE `form_id` = %d",
				$form_id
			)
		);
	}
}
