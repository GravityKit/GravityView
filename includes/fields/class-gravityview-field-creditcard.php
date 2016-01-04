<?php
/**
 * @file class-gravityview-field-creditcard.php
 * @package GravityView
 * @subpackage includes\fields
 */

class GravityView_Field_CreditCard extends GravityView_Field {

	var $name = 'creditcard';

	var $is_searchable = false;

	var $_gf_field_class_name = 'GF_Field_CreditCard';

	var $group = 'pricing';

}

new GravityView_Field_CreditCard;
