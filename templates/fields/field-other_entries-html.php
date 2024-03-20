<?php
/**
 * The default field output template displaying
 *  entries created by same author.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$created_by = \GV\Utils::get( $gravityview->entry, 'created_by' );

/** There was no logged in user who created this entry. */
if ( empty( $created_by ) ) {
	return;
}

$entries = $gravityview->field->field->get_entries( $gravityview );

/** Don't show if no entries and the setting says so. */
if ( empty( $entries ) ) {
	if ( $gravityview->field->no_entries_hide ) {
		return;
	}

	if ( $gravityview->field->no_entries_text ) {
		echo '<div class="gv-no-results"><p>' . esc_html( $gravityview->field->no_entries_text );
		echo "</p>\n</div>";
		return;
	}
}

/** If there are search results, get the entry list object. */
$list = new GravityView_Entry_List(
	array_map(
		function ( $entry ) {
			return $entry->as_entry(); },
		$entries
	),
	$gravityview->request->is_view( false ) ? $gravityview->view->ID : ( is_object( $GLOBALS['post'] ) ? $GLOBALS['post']->ID : 0 ),
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
	_deprecated_function( 'The "gravityview/field/other_entries/args" filter', 'GravityView 1.11', 'gravityview/field/other_entries/criteria' );
}
