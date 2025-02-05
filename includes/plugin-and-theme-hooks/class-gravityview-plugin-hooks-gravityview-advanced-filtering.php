<?php

/**
 * @inheritDoc
 *
 * @since 2.26
 */
class GravityView_Plugin_Hooks_GravityView_Advanced_Filtering extends GravityView_Plugin_and_Theme_Hooks {
	/**
	 * @inheritDoc
	 *
	 * @since 2.26
	 */
	public $constant_name = 'GRAVITYKIT_ADVANCED_FILTERING_VERSION';

	/**
	 * @inheritDoc
	 * @since 2.26
	 */
	protected function add_inactive_hooks(): void {
		add_action( 'gk/gravityview/metabox/content/after', [ $this, 'render_metabox_placeholder' ] );
	}

	/**
	 * Renders the placeholder in the sort_filter metabox.
	 *
	 * @since 2.26
	 *
	 * @param GravityView_Metabox_Tab $metabox The metabox.
	 */
	public function render_metabox_placeholder( GravityView_Metabox_Tab $metabox ): void {
		$disabled = apply_filters( 'gk/gravityview/feature/upgrade/disabled', false );

		if ( $disabled || 'gravityview_sort_filter' !== $metabox->id ) {
			return;
		}

		$this->get_placeholder()->render();
	}

	/**
	 * Returns the placeholder value object.
	 *
	 * @since 2.26
	 *
	 * @return GravityView_Object_Placeholder The placeholder.
	 */
	private function get_placeholder(): GravityView_Object_Placeholder {
		return
			GravityView_Object_Placeholder::inline(
				__( 'Advanced Filtering', 'gk-gravityview' ),
				__( 'Control what entries are displayed in a View using advanced conditional logic.', 'gk-gravityview' ),
				$this->get_placeholder_icon(),
				'gravityview-advanced-filter',
				'https://www.gravitykit.com/products/advanced-filter'
			);
	}

	/**
	 * Returns the icon for the Advanced Filtering extension.
	 *
	 * @since 2.26
	 *
	 * @return string The SVG icon.
	 */
	private function get_placeholder_icon(): string {
		return <<<ICON
<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
<rect x="1.5" y="1.5" width="77" height="77" rx="6.5" fill="white"/>
<rect x="1.5" y="1.5" width="77" height="77" rx="6.5" stroke="#FF1B67" stroke-width="3"/>
<path d="M61.877 26.923L44.5 47.5V62.5H36.5V47.5L19.123 26.923" stroke="#FF1B67" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M40.5 31.5C52.6503 31.5 62.5 28.8137 62.5 25.5C62.5 22.1863 52.6503 19.5 40.5 19.5C28.3497 19.5 18.5 22.1863 18.5 25.5C18.5 28.8137 28.3497 31.5 40.5 31.5Z" stroke="#FF1B67" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
ICON;
	}
}

new GravityView_Plugin_Hooks_GravityView_Advanced_Filtering();
