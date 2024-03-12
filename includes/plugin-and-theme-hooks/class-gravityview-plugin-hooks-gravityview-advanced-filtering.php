<?php

/**
 * @inheritDoc
 * @since TODO
 */
class GravityView_Plugin_Hooks_GravityView_Advanced_Filtering extends GravityView_Plugin_and_Theme_Hooks {

	use GravityView_Functionality_Placeholder;

	public $constant_name = 'GRAVITYKIT_ADVANCED_FILTERING_VERSION';

	/**
	 * @inheritDoc
	 */
	public function add_placeholder_hooks() {
		add_action( 'gravityview_metabox_sort_filter_after', [ $this, 'render_metabox_placeholder' ] );
	}

	/**
	 * Not used for Advanced Filtering.
	 * @return void
	 */
	protected function register_metabox_placeholder() {
		// Not used for Advanced Filtering.
		echo 'asd';
	}

	protected function get_placeholder_icon() {
		return <<<ICON
<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
<rect x="1.5" y="1.5" width="77" height="77" rx="6.5" fill="white"/>
<rect x="1.5" y="1.5" width="77" height="77" rx="6.5" stroke="#FF1B67" stroke-width="3"/>
<path d="M61.877 26.923L44.5 47.5V62.5H36.5V47.5L19.123 26.923" stroke="#FF1B67" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M40.5 31.5C52.6503 31.5 62.5 28.8137 62.5 25.5C62.5 22.1863 52.6503 19.5 40.5 19.5C28.3497 19.5 18.5 22.1863 18.5 25.5C18.5 28.8137 28.3497 31.5 40.5 31.5Z" stroke="#FF1B67" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
ICON;
		return $icon;
	}

	protected function get_placeholder_title() {
		return __( 'Advanced Filtering', 'gk-gravityview' );
	}

	protected function get_placeholder_description() {
		return __( 'Control what entries are displayed in a View using advanced conditional logic.', 'gk-gravityview' );
	}

	/**
	 * Returns the link to buy the plugin.
	 *
	 * @since TODO
	 *
	 * @return string URL to buy the plugin
	 */
	protected function get_buy_now_link() {
		return 'https://www.gravitykit.com/products/advanced-filter/';
	}

	protected function get_plugin_basename() {
		return defined( 'GRAVITYKIT_ADVANCED_FILTERING_FILE' ) ? plugin_basename( GRAVITYKIT_ADVANCED_FILTERING_FILE ) : 'gravityview-advanced-filter/advanced-filter.php';
	}

	protected function render_placeholder_before() {
		echo '<tr id="gk-placeholder-advanced-filtering"><td colspan="2">';
	}
	protected function render_placeholder_after() {
		echo '</td></tr>';
	}
}

new GravityView_Plugin_Hooks_GravityView_Advanced_Filtering();
