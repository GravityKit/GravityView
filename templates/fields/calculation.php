<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

echo gravityview_get_field_value( $entry, $field_id, $display_value );
