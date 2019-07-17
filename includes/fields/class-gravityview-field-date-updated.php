<?php
/**
 * @file class-gravityview-field-date-updated.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_Date_Updated extends GravityView_Field_Date_Created {

	var $name = 'date_updated';

	var $_custom_merge_tag = 'date_updated';

	/**
	 * GravityView_Field_Date_Updated constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->label = esc_html__( 'Date Updated', 'gravityview' );
		$this->default_search_label = $this->label;
		$this->description = esc_html__( 'The date the entry was last updated.', 'gravityview' );
	}
}

new GravityView_Field_Date_Updated;
