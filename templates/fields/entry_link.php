<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

echo apply_filters( 'gravityview_entry_link', __('View Details', 'gravity-view') );
