<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

if( empty( $value ) ) {
	echo __('Not Fulfilled', 'gravity-view');
} else {
	echo __('Fulfilled', 'gravity-view');
}
