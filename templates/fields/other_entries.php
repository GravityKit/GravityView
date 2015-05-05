<?php
/**
 * Display other entries created by the entry creator field type
 *
 * @package GravityView
 * @subpackage GravityView/templates/fields
 */

$gravityview_view = GravityView_View::getInstance();

$field = $gravityview_view->getCurrentField();

$created_by = rgar( $field['entry'], 'created_by' );

// There was no logged in user who created this entry.
if( empty( $created_by ) ) {
	return;
}

// Generate the search parameters
$args = array(
	'id'              => $gravityview_view->getViewId(),
	'page_size'       => $gravityview_view->getCurrentFieldSetting('page_size'),
	'search_field'    => 'created_by',
	'search_value'    => $created_by,
	'search_operator' => 'is',
);

/**
 * @since 1.7.6
 */
$args = apply_filters( 'gravityview/field/other_entries/args', $args, $field );

// Get the entries for the search
$entries = GravityView_frontend::get_view_entries( $args, $field['form']['id'] );

// Don't show if no entries and the setting says so
if( empty( $entries['entries'] ) && $gravityview_view->getCurrentFieldSetting('no_entries_hide') ) {
	return;
}

// If there are search results, get the entry list object
$list = new GravityView_Entry_List(
	$entries['entries'],
	$gravityview_view->getPostId(),
	$field['form'],
	$gravityview_view->getCurrentFieldSetting('link_format'),
	$gravityview_view->getCurrentFieldSetting('after_link'),
	'other_entries' // Context
);

// Generate and echo the output
$list->output();