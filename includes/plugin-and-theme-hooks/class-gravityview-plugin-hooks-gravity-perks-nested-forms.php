<?php

/**
 * Handles custom hooks for Gravity Wiz's Gravity Forms Nested Forms.
 *
 * @since $ver$
 */
final class GravityView_Plugin_Hooks_Gravity_Perks_Nested_Forms extends GravityView_Plugin_and_Theme_Hooks {
	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	protected $class_name = GPNF_Parent_Merge_Tag::class;

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	protected function add_hooks() {
		parent::add_hooks();

		add_filter(
			'gravityview/merge_tags/do_replace_variables',
			Closure::fromCallable( [ $this, 'add_gpnf_merge_tags' ] ),
			5
		);

		add_filter(
			'gform_custom_merge_tags',
			Closure::fromCallable( [ $this, 'add_custom_gpnf_merge_tags' ] ),
			10
		);

		add_filter(
			'gravityview_entry_default_fields',
			Closure::fromCallable( [ $this, 'add_entry_field' ] ),
			10,
			3
		);
	}

	/**
	 * Adds GP Nested Forms merge tags for GravityView.
	 *
	 * @since $ver$
	 *
	 * @return bool
	 */
	private function add_gpnf_merge_tags( $value ) {
		add_filter( 'gform_replace_merge_tags', [
			GPNF_Parent_Merge_Tag::get_instance(),
			'parse_parent_merge_tag',
		], 5, 7 );

		remove_filter( 'gform_replace_merge_tags', [
			GPNF_Parent_Merge_Tag::get_instance(),
			'parse_parent_merge_tag',
		], 6 );

		return $value;
	}

	/**
	 * Adds custom merge tags for parent and child form/entry IDs.
	 *
	 * @since $ver$
	 *
	 * @param array $tags The registered tags.
	 *
	 * @return array
	 */
	private function add_custom_gpnf_merge_tags( array $tags ): array {
		return array_merge( $tags, [
			[
				'label' => esc_html__( 'Parent Entry ID', 'gk-gravityview' ),
				'tag'   => '{Parent:entry_id}',
			],
			[
				'label' => esc_html__( 'Parent Entry Form ID', 'gk-gravityview' ),
				'tag'   => '{Parent:form_id}',
			],
			[
				'label' => esc_html__( 'Child Form Field ID', 'gk-gravityview' ),
				'tag'   => '{Parent:child_id}',
			],
		] );
	}

	/**
	 * Adds fields to GravityView.
	 *
	 * @since $ver$
	 *
	 * @param array        $fields The fields.
	 * @param string|array $form   The form reference.
	 * @param string       $zone   The zone.
	 *
	 * @return array The updated fields.
	 */
	private function add_entry_field( array $fields, $form, string $zone ): array {
		if ( ! in_array( $zone, [ 'directory', 'single' ], true ) ) {
			return $fields;
		}

		$fields[ GPNF_Entry::ENTRY_PARENT_KEY ] = [
			'label' => esc_html__( 'Parent Entry ID', 'gp-nested-forms', 'gk-gravityview' ),
			'desc'  => esc_html__( 'The parent entry ID for nested form entries.', 'gk-gravityview' ),
			'type'  => GPNF_Entry::ENTRY_PARENT_KEY,
			'group' => 'add-on',
			'icon'  => 'dashicons-code-standards',
		];

		$fields[ GPNF_Entry::ENTRY_PARENT_FORM_KEY ] = [
			'label' => esc_html__( 'Parent Entry Form ID', 'gp-nested-forms', 'gk-gravityview' ),
			'desc'  => esc_html__( 'The parent form ID for this nested form entry.', 'gk-gravityview' ),
			'type'  => GPNF_Entry::ENTRY_PARENT_FORM_KEY,
			'group' => 'add-on',
			'icon'  => 'dashicons-code-standards',
		];

		$fields[ GPNF_Entry::ENTRY_NESTED_FORM_FIELD_KEY ] = [
			'label' => esc_html__( 'Child Form Field ID', 'gp-nested-forms', 'gk-gravityview' ),
			'desc'  => esc_html__( 'The field ID on the parent form for this nested form.', 'gk-gravityview' ),
			'type'  => GPNF_Entry::ENTRY_NESTED_FORM_FIELD_KEY,
			'group' => 'add-on',
			'icon'  => 'dashicons-code-standards',
		];

		return $fields;
	}
}

new GravityView_Plugin_Hooks_Gravity_Perks_Nested_Forms();
