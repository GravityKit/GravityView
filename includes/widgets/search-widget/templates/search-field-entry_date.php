<?php
/**
 * Display the search by entry date input boxes
 *
 * @file class-search-widget.php See for usage
 *
 * @global array $data
 */

if ( $search_field['input_type'] === 'date_range' ) {
	include 'search-field-date_range.php';
} else {
	include 'search-field-date.php';
}
