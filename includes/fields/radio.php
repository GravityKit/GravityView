<?php

class GravityView_Field_Radio extends GravityView_Field {

	var $name = 'radio';

	var $search_operators = array( 'is', 'in', 'not in', 'isnot', 'contains');

	var $_gf_field_class_name = 'GF_Field_Radio';

}

new GravityView_Field_Radio;
