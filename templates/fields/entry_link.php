<?php

global $gravityview_view;

extract( $gravityview_view->field_data );

$output = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $field_settings['entry_link_text'], $form, $entry ) );

$href = GravityView_API::entry_link( $entry, $field );
$output = '<a href="'. $href .'">'. $output . '</a>';

echo $output;
