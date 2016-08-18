<?php
/**
 * @file class-gravityview-admin-view-field.php
 * @since 1.17.3
 */

class GravityView_Admin_View_Field extends GravityView_Admin_View_Item {

	protected $label_type = 'field';

	protected function additional_info() {

		$field_info = '';

		$field_info_items = array();

		// Fields with IDs, not like Source URL or Entry ID
		if( is_numeric( $this->id ) ) {

			$field_type_title = GFCommon::get_field_type_title( $this->item['input_type'] );

			$field_info_items[] = array(
				'value' => sprintf( __('Type: %s', 'gravityview'), $field_type_title )
			);

			$field_info_items[] = array(
				'value' => sprintf( __('Field ID: %s', 'gravityview'), $this->id ),
			);

		}

		if( !empty( $this->item['desc'] ) ) {
			$field_info_items[] = array(
				'value' => $this->item['desc']
			);
		}

		if( !empty( $this->item['adminLabel'] ) ) {
			$field_info_items[] = array(
				'value' => sprintf( __('Admin Label: %s', 'gravityview' ), $this->item['adminLabel'] ),
				'class'	=> 'gv-sublabel'
			);
		}

		return $field_info_items;
	}

}
