<?php
/**
 * @file class-gravityview-field-total.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.20
 */

class GravityView_Field_Total extends GravityView_Field {

	var $name = 'total';

	var $is_searchable = true;

	var $is_numeric = true;

	var $search_operators = array( 'is', 'isnot', 'greater_than', 'less_than', 'contains' );

	var $group = 'product';

	/** @see GF_Field_Total */
	var $_gf_field_class_name = 'GF_Field_Total';

	public function __construct() {
		$this->label = esc_html__( 'Total', 'gravityview' );

		add_filter( 'gravityview/edit_entry/after_update', array( $this, 'edit_entry_recalculate_totals' ), 10, 3 );

		parent::__construct();
	}


	/**
	 * If entry has totals fields, recalculate them
	 *
	 * @since 1.20
	 *
	 * @param array $form Gravity Forms form array
	 * @param int $entry_id Gravity Forms Entry ID
	 * @param GravityView_Edit_Entry_Render $Edit_Entry_Render
	 *
	 * @return void
	 */
	function edit_entry_recalculate_totals( $form = array(), $entry_id = 0, $Edit_Entry_Render = null ) {

		//saving total field as the last field of the form.
		if ( ! empty( $Edit_Entry_Render->total_fields ) ) {

			$entry = $Edit_Entry_Render->entry;

			/** @var GF_Field_Total $total_field */
			foreach ( $Edit_Entry_Render->total_fields as $total_field ) {
				$entry["{$total_field->id}"] = GFCommon::get_order_total( $form, $Edit_Entry_Render->entry );
			}

			$return_entry = GFAPI::update_entry( $entry );

			if( is_wp_error( $return_entry ) ) {
				do_action( 'gravityview_log_error', __METHOD__ . ': Updating the entry total fields failed', $return_entry );
			} else {
				do_action( 'gravityview_log_debug', __METHOD__ . ': Updating the entry total fields succeeded' );
			}
		}
	}
}

new GravityView_Field_Total;
