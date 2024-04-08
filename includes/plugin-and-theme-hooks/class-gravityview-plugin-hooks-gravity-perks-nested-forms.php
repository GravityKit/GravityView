<?php

/**
 * Handles custom hooks for Gravity Perks Nested Forms.
 * @since $ver$
 */
final class GravityView_Plugin_Hooks_Gravity_Perks_Nested_Forms extends GravityView_Plugin_and_Theme_Hooks {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected $class_name = GPNF_Parent_Merge_Tag::class;

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function add_hooks() {
		parent::add_hooks();

		add_filter(
			'gravityview/merge_tags/do_replace_variables',
			Closure::fromCallable( [ $this, 'add_gpnf_merge_tags' ] ),
			5
		);
	}

	/**
	 * Add GP Nested forms merge tags for Gravity View.
	 * @since $ver$
	 * @return bool
	 */
	private function add_gpnf_merge_tags($value) {
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
}

new GravityView_Plugin_Hooks_Gravity_Perks_Nested_Forms();
