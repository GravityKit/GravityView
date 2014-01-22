/**
 * Custom js script at Add New / Edit Views screen
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2013, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */


(function( $ ) {
	
	var fieldOrigin = 'sortable';
	var widgetOrigin = 'sortable';

	function init_draggables() {
		
		$("#directory-available-fields").find(".gv-fields").draggable({
			connectToSortable: 'div.active-drop',
			distance: 2,
			helper: 'clone',
			revert: 'invalid',
			zIndex: 100,
			containment: 'document',
			start: function() {
				fieldOrigin = 'draggable';
			}
		});
	}
	
	// Event handler to remove Fields from active areas
	function removeField( event ) {
		event.preventDefault();
		$( event.currentTarget ).parent().parent().parent().remove();
	}
	
	// Event handler to open dialog with Field Settings
	function openFieldSettings( event ) {
		event.preventDefault();
		var parent = $( event.currentTarget ).parent().parent().parent();
		parent.find(".gv-fields-options").dialog({
			dialogClass: 'wp-dialog',
			appendTo: parent,
			closeOnEscape: true,
			buttons: {
				'Close': function() {
					$(this).dialog('close');
				} 
			},
		});
	}
	
	
	

	$(document).ready( function() {
		
		
		// If Form Selection changes update fields
		$("#gravityview_form_id").change( function() {
			
			$("#directory-available-fields, #directory-active-fields").find(".gv-fields").remove();
			
			var data = {
				action: 'gv_available_fields',
				formid: $(this).val(),
				nonce: ajax_object.nonce,
			}
			
			$.post( ajax_object.ajaxurl, data, function( response ) {
				if( response ) {
					$("#directory-available-fields fieldset.area").append( response );
					init_draggables();
				}
			});
			
		});
		
		
		// View Configuration - Tabs
		$("#tabs").tabs();
		
		// Directory View Configuration - Fields Mapping
		 // Using field_origin as flag to avoid 'drop' event being fired twice.
		
		init_draggables();
		
		$('#directory-active-fields').find(".active-drop").sortable({
			placeholder: "fields-placeholder",
			items: '> .gv-fields',
			distance: 2,
			receive: function( event, ui ) {
				$(this).find(".drop-message").hide();
			}
		}).droppable({ 
			drop: function( event, ui ) {
				
				if( 'draggable' === fieldOrigin ) {
					
					var data = {
						action: 'gv_field_options',
						area: $(this).attr('data-areaid'),
						field_id: ui.draggable.attr('data-fieldid'),
						field_label: ui.draggable.find("h5").text(),
						nonce: ajax_object.nonce,
					}
					
					$.post( ajax_object.ajaxurl, data, function( response ) {
						if( response ) {
							ui.draggable.append( response );
						}
					});
					
					fieldOrigin = 'sortable';
					
					// show field buttons: Settings & Remove
					ui.draggable.find("h5 span").show();
					
					ui.draggable.find("h5 span a[href='#remove']").click( removeField );
					
					ui.draggable.find("h5 span a[href='#settings']").click( openFieldSettings );
				}
			}
		});
		
		$("a[href='#remove']").click( removeField );
		
		$("a[href='#settings']").click( openFieldSettings );

		
		
		// Directory View Configuration - Widgets
		
		$("#directory-available-widgets").find(".gv-widgets").draggable({
			connectToSortable: 'div.widget-drop',
			distance: 2,
			helper: 'clone',
			revert: 'invalid',
			zIndex: 100,
			containment: 'document',
			start: function() {
				widgetOrigin = 'draggable';
			}
		});
		
		$('#directory-active-widgets').find(".widget-drop").sortable({
			placeholder: "fields-placeholder",
			items: '> .gv-widgets',
			distance: 2,
			receive: function( event, ui ) {
				$(this).find(".drop-message").hide();
			}
		}).droppable({ 
			drop: function( event, ui ) {
				if( 'draggable' === widgetOrigin ) {
					
					var data = {
						action: 'gv_widget_options',
						area: $(this).attr('data-areaid'),
						widget_id: ui.draggable.attr('data-widgetid'),
						widget_label: ui.draggable.find("h5").text(),
						nonce: ajax_object.nonce,
					}
					
					$.post( ajax_object.ajaxurl, data, function( response ) {
						if( response ) {
							ui.draggable.append( response );
						}
					});
					widgetOrigin = 'sortable';
				}
			}
		});
		
		
	});
 
}(jQuery));
