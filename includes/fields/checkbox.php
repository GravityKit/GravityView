<?php

class GravityView_Field_Checkbox extends GravityView_Field {

	var $name = 'checkbox';

	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains');

	var $_gf_field_class_name = 'GF_Field_Checkbox';

}

new GravityView_Field_Checkbox;
