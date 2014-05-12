<?php

global $gravityview_view;

extract( $gravityview_view->__get('field_data') );

$output = GFCommon::format_date( $value, true, apply_filters( 'gravityview_date_format', '' ) );

echo $output;