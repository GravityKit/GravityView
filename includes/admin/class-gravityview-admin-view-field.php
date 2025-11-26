<?php
/**
 * @file class-gravityview-admin-view-field.php
 * @since 1.17.3
 */

class GravityView_Admin_View_Field extends GravityView_Admin_View_Item {

	protected $label_type = 'field';

	protected function get_title( string $label ): string {
		return sprintf( __( 'Field: %s', 'gk-gravityview' ), $label );
	}

	protected function additional_info() {

		$field_info_items = array();

		if ( ! empty( $this->item['adminLabel'] ) ) {
			$field_info_items[] = array(
				'value' => sprintf( __( 'Admin Label: %s', 'gk-gravityview' ), $this->item['adminLabel'] ),
				'class' => 'gv-sublabel',
			);
		}

		// Fields with IDs, not like Source URL or Entry ID
		if ( is_numeric( $this->id ) || preg_match( '/^\d(\.\d+)*$/', $this->id ) ) {
			$field_type_title = GFCommon::get_field_type_title( $this->item['input_type'] );

			if ( ! empty( $this->item['parent'] ) ) {
				$field_info_items[] = array(
					'value' => sprintf( esc_html__( 'Parent: %s', 'gk-gravityview' ), esc_attr( $this->item['parent']['label'] ) ),
				);
			}

			$field_info_items[] = array(
				'value'          => sprintf( __( 'Type: %s', 'gk-gravityview' ), $field_type_title ),
				'hide_in_picker' => ! empty( $this->item['parent'] ),
			);

			$field_info_items[] = array(
				'value' => sprintf( __( 'Field ID: %s', 'gk-gravityview' ), $this->id ),
			);
		}

		if ( ! empty( $this->item['desc'] ) ) {
			$field_info_items[] = array(
				'value' => $this->item['desc'],
			);
		}

		$field_info_items[] = array(
			'value'          => sprintf( __( 'Form ID: %s', 'gk-gravityview' ), $this->form_id ),
			'hide_in_picker' => true,
		);

		return $field_info_items;
	}
}
