<?php
/**
 * The default field output template displaying
 *  entries created by same author.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

$created_by = \GV\Utils::get( $gravityview->entry, 'created_by' );

/** There was no logged in user who created this entry. */
if ( empty( $created_by ) ) {
	return;
}

/** Filter entries by approved and created_by. */
$search_criteria = GravityView_frontend::process_search_only_approved( $gravityview->view->settings->as_atts(),
	array(
		'field_filters' => array(
			array(
				'key' => 'created_by',
				'value' => $created_by,
				'operator' => 'is'
			)
		),
		'status' => apply_filters( 'gravityview_status', 'active', $gravityview->view->settings->as_atts() ),
	)
);

/**
 * @filter `gravityview/field/other_entries/criteria` Modify the search parameters before the entries are fetched.
 *
 * @since 1.11
 *
 * @param array $criteria Gravity Forms search criteria array, as used by GVCommon::get_entries()
 * @param array $view_settings Associative array of settings with plugin defaults used if not set by the View
 * @param int $form_id The Gravity Forms ID
 * @since 2.0
 * @param \GV\Template_Context $gravityview The context
 */
$criteria = apply_filters( 'gravityview/field/other_entries/criteria', $search_criteria, $gravityview->view->settings->as_atts(), $gravityview->view->form->ID, $gravityview );

/** Force mode all and filter out our own entry. */
$search_criteria['field_filters']['mode'] = 'all';
$search_criteria['field_filters'][] = array(
	'key' => 'id',
	'value' => $gravityview->entry->ID,
	'operator' => 'isnot'
);
$filter = \GV\GF_Entry_Filter::from_search_criteria( $search_criteria );

$entries = $gravityview->view->form->entries->filter( $filter )->limit( $gravityview->field->page_size ? : 10 )->all();

/** Don't show if no entries and the setting says so. */
if ( empty( $entries ) && $gravityview->field->no_entries_hide ) {
	return;
}

/** If there are search results, get the entry list object. */
$list = new GravityView_Entry_List(
	array_map( function( $entry ) { return $entry->as_entry(); }, $entries ),
	$gravityview->request->is_view() ? $gravityview->view->ID : is_object( $GLOBALS['post'] ) ? $GLOBALS['post']->ID : 0,
	$gravityview->view->form->form,
	$gravityview->field->link_format,
	$gravityview->field->after_link,
	'other_entries', // Context
	$gravityview
);

/** Generate and echo the output. */
$list->output();

/**
 * @since 1.7.6
 * @deprecated since 1.11
 */
$deprecated = apply_filters( 'gravityview/field/other_entries/args', array(), null );
if ( ! empty( $deprecated ) ) {
    _deprecated_function(  'The "gravityview/field/other_entries/args" filter', 'GravityView 1.11', 'gravityview/field/other_entries/criteria' );
}
