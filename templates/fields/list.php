<?php

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

echo $display_value;
