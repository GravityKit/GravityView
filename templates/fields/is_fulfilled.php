<?php

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

if( empty( $value ) ) {
	echo __('Not Fulfilled', 'gravityview');
} else {
	echo __('Fulfilled', 'gravityview');
}
