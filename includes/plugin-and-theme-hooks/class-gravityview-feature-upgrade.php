<?php

/**
 * Handles the upgrade possibilities for a plugin.
 *
 * @since 2.26
 */
final class GravityView_Feature_Upgrade {
	/**
	 * Registers the hooks.
	 *
	 * @since 2.26
	 */
	public function __construct() {
		add_filter( 'gk/gravityview/metaboxes/navigation/title', [ $this, 'maybe_add_upgrade_pill' ], 10, 2 );
	}

	/**
	 * The star SVG used for the Upgrade pill.
	 *
	 * @since 2.26
	 *
	 * @return string The SVG code.
	 */
	private function star_svg(): string {
		return <<<SVG
<svg viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M5 0C5.30497 0 5.56922 0.211305 5.63623 0.50887L5.97084 1.99435C6.19933 3.00861 6.99141 3.8007 8.00565 4.02913L9.49113 4.36374C9.7887 4.43078 10 4.69504 10 5C10 5.30496 9.7887 5.56922 9.49113 5.63626L8.00565 5.97087C6.99141 6.19931 6.19933 6.99139 5.97084 8.00565L5.63623 9.49113C5.56922 9.7887 5.30497 10 5 10C4.69503 10 4.43078 9.7887 4.36377 9.49113L4.02916 8.00565C3.80067 6.99139 3.00859 6.19931 1.9943 5.97087L0.508852 5.63626C0.211348 5.56922 0 5.30496 0 5C0 4.69504 0.211348 4.43078 0.508852 4.36374L1.9943 4.02913C3.00859 3.8007 3.80067 3.00861 4.02916 1.99435L4.36377 0.50887C4.43078 0.211305 4.69503 0 5 0Z" fill="currentColor"/>
</svg>
SVG;
	}

	/**
	 * Displays an upgrade pill on any metabox tab that has the `gravityview-upgrade` class.
	 *
	 * @since 2.26
	 *
	 * @param string                  $title   The metabox navigation title.
	 * @param GravityView_Metabox_Tab $metabox The metabox object.
	 *
	 * @return string The navigation label for the metabox.
	 */
	public function maybe_add_upgrade_pill( $title, GravityView_Metabox_Tab $metabox ): string {
		$disabled = apply_filters( 'gk/gravityview/feature/upgrade/disabled', false );

		if (
			$disabled
			|| false === strpos( $metabox->extra_nav_class, 'gravityview-upgrade' )
		) {
			return $title;
		}

		return sprintf(
			'%s<span class="gravityview-upgrade-pill">%s <span>%s</span></span>',
			$title,
			$this->star_svg(),
			esc_html__( 'Upgrade', 'gk-gravityview' )
		);
	}
}

new GravityView_Feature_Upgrade();
