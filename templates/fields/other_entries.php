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

$form_id = $gravityview_view->getFormId();

// Get the settings for the View ID
$view_settings = gravityview_get_template_settings( $gravityview_view->getViewId() );

$view_settings['page_size'] = $gravityview_view->getCurrentFieldSetting('page_size');

// Prepare paging criteria
$criteria['paging'] = array(
    'offset' => 0,
    'page_size' => $view_settings['page_size']
);

// Prepare Search Criteria
$criteria['search_criteria'] = array(
    'field_filters' => array(
        array(
            'key' => 'created_by',
            'value' => $created_by,
            'operator' => 'is'
        )
    )
);
$criteria['search_criteria'] = GravityView_frontend::process_search_only_approved( $view_settings, $criteria['search_criteria'] );
$criteria['search_criteria']['status'] = apply_filters( 'gravityview_status', 'active', $view_settings );

/**
 * Modify the search parameters before the entries are fetched
 *
 * @since 1.11
 *
 * @param array $criteria Gravity Forms search criteria array, as used by GVCommon::get_entries()
 * @param array $view_settings Associative array of settings with plugin defaults used if not set by the View
 * @param int $form_id The Gravity Forms ID
 */
$criteria = apply_filters('gravityview/field/other_entries/criteria', $criteria, $view_settings, $form_id );

$entries = GVCommon::get_entries( $form_id, $criteria );

// Don't show if no entries and the setting says so
if( empty( $entries ) && $gravityview_view->getCurrentFieldSetting('no_entries_hide') ) {
	return;
}

// If there are search results, get the entry list object
$list = new GravityView_Entry_List(
	$entries,
	$gravityview_view->getPostId(),
	$field['form'],
	$gravityview_view->getCurrentFieldSetting('link_format'),
	$gravityview_view->getCurrentFieldSetting('after_link'),
	'other_entries' // Context
);

// Generate and echo the output
$list->output();

/**
 * @since 1.7.6
 * @deprecated since 1.11
 */
$deprecated = apply_filters( 'gravityview/field/other_entries/args', array(), $field );
if ( !empty( $deprecated ) ) {
    _deprecated_function(  'The "gravityview/field/other_entries/args" filter', 'GravityView 1.11', 'gravityview/field/other_entries/criteria' );
}
