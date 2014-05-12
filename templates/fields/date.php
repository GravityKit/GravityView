<?php

global $gravityview_view;

extract( $gravityview_view->__get('field_data') );

$output = GFCommon::date_display( $value, apply_filters( 'gravityview_date_format', $field['dateFormat'] ) );

echo $output;