<?php
/**
 * GravityView Ratings & Reviews placeholder metabox.
 *
 * @since 2.26
 *
 * @package GravityView
 */

/**
 * @inheritDoc
 *
 * @since 2.26
 */
class GravityView_Plugin_Hooks_GravityView_Ratings_Reviews extends GravityView_Plugin_and_Theme_Hooks {
	/**
	 * @inheritDoc
	 *
	 * @since 2.26
	 */
	public $class_name = 'GravityView_Ratings_Reviews_Loader';

	/**
	 * @inheritDoc
	 * @since 2.26
	 */
	protected function add_inactive_hooks(): void {
		add_action( 'add_meta_boxes', [ $this, 'register_metabox_placeholder' ] );
	}

	/**
	 * Renders the placeholder in the sort_filter metabox.
	 *
	 * @since 2.26
	 *
	 * @param GravityView_Metabox_Tab $metabox The metabox.
	 */
	public function register_metabox_placeholder(): void {
		$disabled = apply_filters( 'gk/gravityview/feature/upgrade/disabled', false );

		if ( $disabled ) {
			return;
		}

		$metabox = new GravityView_Metabox_Tab(
			'ratings_reviews_entry',
			esc_html__( 'Ratings & Reviews', 'gk-gravityview' ),
			'',
			'dashicons-star-half',
			function () {
				$this->get_placeholder()->render();
			}
		);

		$metabox->extra_nav_class = 'gravityview-upgrade';

		GravityView_Metabox_Tabs::add( $metabox );
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
			GravityView_Object_Placeholder::card(
				esc_html__( 'Ratings & Reviews', 'gk-gravityview' ),
				esc_html__( 'Allow users to rate, review, and comment on entries in a View. Supports star ratings and up/down voting.', 'gk-gravityview' ),
				$this->get_placeholder_icon(),
				'gravityview-ratings-reviews',
				'https://www.gravitykit.com/products/ratings-reviews/'
			);
	}

	/**
	 * Returns the icon for the Ratings & Reviews extension.
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
<path d="M60 57.5H44" stroke="#FF1B67" stroke-width="3" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M60 51.5H44" stroke="#FF1B67" stroke-width="3" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M60 45.5H44" stroke="#FF1B67" stroke-width="3" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M38 51.55L26.64 57.52L29 43.756L19 34.008L32.82 32L39 19.48L45.18 32L59 34.008L53 39.857" stroke="#FF1B67" stroke-width="3" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
ICON;
	}
}

new GravityView_Plugin_Hooks_GravityView_Ratings_Reviews();
