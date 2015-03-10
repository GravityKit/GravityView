<?php
/**
 * Display the entry_link field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

extract( $gravityview_view->getCurrentField() );

// Don't show on single entry
if( $gravityview_view->getContext() === 'single' ) { return; }

$link_text = empty( $field_settings['entry_link_text'] ) ? __('View Details', 'gravityview') : $field_settings['entry_link_text'];

$output = apply_filters( 'gravityview_entry_link', GravityView_API::replace_variables( $link_text, $form, $entry ) );

echo GravityView_API::entry_link_html( $entry, $output, array(), $field_settings );