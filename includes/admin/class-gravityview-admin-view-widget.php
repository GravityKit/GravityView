<?php
/**
 * @file class-gravityview-admin-view-widget.php
 * @since 1.17.3
 */

class GravityView_Admin_View_Widget extends GravityView_Admin_View_Item {

	protected $label_type = 'widget';

	/**
	 * Determines whether this widget can be duplicated.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the widget can be duplicated.
	 */
	protected function can_duplicate(): bool {
		// The Search Bar widget has its own configuration dialog and should not be duplicated.
		if ( 'search_bar' === $this->id ) {
			return false;
		}

		return parent::can_duplicate();
	}

	/**
	 * @inheritDoc
	 * @since 2.42
	 */
	protected function get_title( string $label ): string {
		return sprintf( __( 'Widget: %s', 'gk-gravityview' ), $label );
	}

	protected function additional_info() {

		$field_info_items = array();

		if ( ! empty( $this->item['description'] ) ) {

			$field_info_items[] = array(
				'value' => $this->item['description'],
			);

		}

		/**
		 * Allows widgets to add custom summary information displayed in the admin.
		 *
		 * @since TODO
		 *
		 * @param array  $field_info_items Array of info items with 'value' and optional 'class' keys.
		 * @param string $widget_id        The widget ID (e.g., 'search_bar').
		 * @param array  $settings         The widget settings/configuration.
		 * @param array  $item             The widget item data.
		 */
		$field_info_items = apply_filters( 'gk/gravityview/admin/widget-info', $field_info_items, $this->id, $this->settings, $this->item );

		return $field_info_items;
	}
}
