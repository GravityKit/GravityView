<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

$output = GFCommon::format_date( $value, false, apply_filters( 'gravityview_date_format', rgar($field, "dateFormat") ) );

echo $output;
