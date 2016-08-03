/* global ajaxurl,gvGlobals,console,alert,form,gfMergeTagsObj,jQuery */
/**
 * Custom js script at Add New / Edit Views screen
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.17.2
 */

jQuery( document ).ready( function( $ ) {

	var $directory_fields = $('#directory-active-fields, #directory-header-widgets, #directory-footer-widgets');
	var $single_fields = $('#single-active-fields');

	function has_active_fields( $container ) {
		return $container.find('.gv-fields').length > 0;
	}

	function has_link_to_single_entry(  ) {
		return $directory_fields.find('input[name*="show_as_link"]').filter(':checked').length > 0;
	}

	function find_widget( widget_id ) {
		return $directory_fields.find('.gv-fields[data-fieldid="' + widget_id + '"]');
	}

	function has_search_bar(  ) {
		return find_widget('search_bar').length > 0;
	}

	function has_paging_links( ) {
		return find_widget('page_links').length > 0;
	}

	function get_form_id(  ) {
		return $('#gravityview_form_id').val();
	}

	$('#submitdiv').on('click', '#publish', function( e ) {

		var message = [];

		if( ! has_active_fields( $directory_fields ) ) {
			message.push( 'You have not configured any fields in the Multiple Entry layout. Click "+Add Field" to get started.');
		}

		if( ! has_active_fields( $single_fields ) ) {
			message.push( 'You have not configured any fields in the Single Entry layout. Click "+Add Field" to get started.');
			// Todo: Switch to Single Entry tab
		}

		// Just getting started
		message.push( has_active_fields( $directory_fields ) ? 'Has directory fields' : 'Does not have directory fields');
		message.push( has_active_fields( $single_fields ) ? 'Has Single Entry fields' : 'Does not have Single Entry fields');
		message.push( has_link_to_single_entry() ? 'Has link to single entry' : 'You have not configured a link to the Single Entry screen. Click a field setting "gear" icon, then check "Link to single" to configure.');
		message.push( has_search_bar() ? 'Has search widget' : 'No search widget');
		message.push( has_paging_links() ? 'Has pagination widget' : 'No pagination widget');

		return confirm( message.join( "\n" ) );
	});

});