<?php
/**
 * @file class-gravityview-admin-view-widget.php
 * @since 1.17.3
 */

class GravityView_Admin_View_Widget extends GravityView_Admin_View_Item {

	protected $label_type = 'widget';

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

		return $field_info_items;
	}
}
