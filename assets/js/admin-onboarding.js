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

	var $tabs = $( "#gv-view-configuration-tabs" );
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

	function switch_to_tab( index ) {
		$tabs.tabs( 'option', 'active', index );
	}

	$('#submitdiv').on('click', '#publish', function( e ) {

		var active_tab_id = $tabs.tabs( "option", "active" );

		var tour = {
			id: "gv-configuration-errors"
		};

		var steps = [];

		var calloutMgr = hopscotch.getCalloutManager();

		if( ! has_active_fields( $directory_fields ) ) {
			steps.push( {
				id: 'configure_multiple_entry',
				target: 'ui-id-7',
				title: 'You have not configured any fields in the Multiple Entry layout.',
				content: '',
				// When clicking Next, switch to Multiple Entries tab
				onNext: function() {
					switch_to_tab( 1 );
				}
			});

			steps.push({
				id: 'add_field_directory',
				target: $('#directory-active-fields').find('.gv-add-field')[0],
				title: 'Click "+Add Field" to get started.',
				content: ''
			});
		}

		if( ! has_active_fields( $single_fields ) ) {

			if ( 1 !== active_tab_id ) {

				steps.push( {
					id: 'configure_single_entry',
					target: 'ui-id-8',
					placement: 'bottom',
					title: 'You have not configured any fields in the Single Entry layout.',
					content: '',
					// When clicking Next, switch to Single Entry tab
					onNext: function() {
						switch_to_tab( 1 );
					}
				} );
			}

			steps.push({
				id: 'add_field_single',
				placement: 'bottom',
				target: $('#single-active-fields').find('.gv-add-field')[0],
				title: 'Click "+Add Field" to get started.',
				content: ''
			});
		}

		// Just getting started
		/*tour.steps.push( has_active_fields( $directory_fields ) ? 'Has directory fields' : 'Does not have directory fields');
		tour.steps.push( has_active_fields( $single_fields ) ? 'Has Single Entry fields' : 'Does not have Single Entry fields');
		tour.steps.push( has_link_to_single_entry() ? 'Has link to single entry' : 'You have not configured a link to the Single Entry screen. Click a field setting "gear" icon, then check "Link to single" to configure.');
		tour.steps.push( has_search_bar() ? 'Has search widget' : 'No search widget');
		tour.steps.push( has_paging_links() ? 'Has pagination widget' : 'No pagination widget');*/


		if( steps.length === 1 ) {
			calloutMgr.createCallout( steps[0] );
		} else {
			tour.steps = steps;
			hopscotch.startTour( tour );
		}

		return false; // confirm( message.join( "\n" ) );
	});

});