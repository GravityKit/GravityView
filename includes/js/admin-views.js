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

	function init_draggables() {
		
		$("#directory-available-fields, #single-available-fields").find(".gv-fields").draggable({
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
		
		// Define droppable zone to remove active fields
		$("#directory-available-fields, #single-available-fields").droppable({
			drop: function( event, ui ) {
				if( ui.draggable.find(".gv-dialog-options").length > 0 ) {
					ui.draggable.remove();
					toggleDropMessage();
				}
			}/*
,
			over: function( event, ui ) {
				if( ui.draggable.find(".gv-dialog-options").length > 0 ) {
					console.log('in');
				}
			},
			out: function( event, ui ) {
				console.log('out');
			}
*/
		});
	}
	
	
	function init_droppables() {
	
		$('#directory-active-fields, #single-active-fields').find(".active-drop").sortable({
			placeholder: "fields-placeholder",
			items: '> .gv-fields',
			distance: 2,
			connectWith: ".active-drop",
			receive: function( event, ui ) {
				
				// Check if field comes from another active area and if so, update name attributes.
				if( ui.item.find(".gv-dialog-options").length > 0 ) {
				
					var sender_area = ui.sender.attr('data-areaid'),
						receiver_area = $(this).attr('data-areaid');
						
					ui.item.find( '[name^="fields['+ sender_area +']"]').each( function() {
						var name = $(this).attr('name');
						$(this).attr('name', name.replace( sender_area, receiver_area ) );
					});
				
				}
				
				toggleDropMessage();
				
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
					ui.draggable.find("span.gv-field-controls").show();
					
					ui.draggable.find("span.gv-field-controls a[href='#remove']").click( removeField );
					
					ui.draggable.find("span.gv-field-controls a[href='#settings']").click( openFieldSettings );
				}
			}
		});
	
	}
	
	
	// Event handler to remove Fields from active areas
	function removeField( event ) {
		event.preventDefault();
		var area = $( event.currentTarget ).parents(".active-drop");
		$( event.currentTarget ).parent().parent().remove();
		if( area.find(".gv-fields").length == 0 ) {
			 area.find(".drop-message").show();
		}
	}
	
	// Event handler to open dialog with Field Settings
	function openFieldSettings( event ) {
		event.preventDefault();
		var parent = $( event.currentTarget ).parent().parent();
		parent.find(".gv-dialog-options").dialog({
			dialogClass: 'wp-dialog',
			appendTo: parent,
			width: 550,
			closeOnEscape: true,
			buttons: {
				'Close': function() {
					$(this).dialog('close');
				} 
			},
		});
	}
	
	// Event handler to open dialog with Widget Settings
	function openWidgetSettings( event ) {
		event.preventDefault();
		var parent = $( event.currentTarget ).parent();
		parent.find(".gv-dialog-options").dialog({
			dialogClass: 'wp-dialog',
			appendTo: parent,
			width: 350,
			closeOnEscape: true,
			buttons: {
				'Close': function() {
					$(this).dialog('close');
				} 
			},
		});
	}
	
	function toggleDropMessage() {
		$(".active-drop").each( function() {
			if( $(this).find(".gv-fields").length != 0 ) {
				$(this).find(".drop-message").hide();
			} else {
				$(this).find(".drop-message").show();
			}
		});
	}
	
	

	$(document).ready( function() {
		
		
		// If Form Selection changes update fields, show/hide View configuration metabox
		$('#gravityview_form_id').change( function() {
			
			// check if form is selected, if not hide the entire View Configuration metabox
			if( $(this).val() === '') {
				$("#gravityview_directory_view").slideUp(150);
				$("#directory-available-fields, #directory-active-fields, #single-available-fields, #single-active-fields").find(".gv-fields").remove();
				// And stop processing
				return false;
			} 
			
			$("#gravityview_directory_view").slideDown(150);
			
			// toggle view of "drop message" when active areas are empty or not.
			toggleDropMessage();

			var data = {
				action: 'gv_available_fields',
				formid: $(this).val(),
				nonce: ajax_object.nonce,
			}

			$.post( ajax_object.ajaxurl, data, function( response ) {
				if( response ) {
					$("#directory-available-fields fieldset.area").append( response );
					$("#single-available-fields fieldset.area").append( response );
					init_draggables();
				}
			});

		}).trigger('change');
		
		
		// If Directory Template Selection changes update areas/fields
		$("#gravityview_directory_template").change( function() {
			
			$("#directory-active-fields").find("fieldset.area").remove();
			
			var data = {
				action: 'gv_get_active_areas',
				template_id: $(this).val(),
				nonce: ajax_object.nonce,
			}
			
			$.post( ajax_object.ajaxurl, data, function( response ) {
				if( response ) {
					$("#directory-active-fields").append( response );
					init_droppables();
				}
			});
			
		});
		
		// If Single Template Selection changes update areas/fields
		$("#gravityview_single_template").change( function() {
			
			$("#single-active-fields").find("fieldset.area").remove();
			
			var data = {
				action: 'gv_get_active_areas',
				template_id: $(this).val(),
				nonce: ajax_object.nonce,
			}
			
			$.post( ajax_object.ajaxurl, data, function( response ) {
				if( response ) {
					$("#single-active-fields").append( response );
					init_droppables();
				}
			});
			
		});
		
		
		// View Configuration - Tabs (persisten after refresh)
		$("#tabs").tabs({
			active: $("#gv-active-tab").val(),
			activate: function( event, ui ) {
				$("#gv-active-tab").val( ui.newTab.parent().children().index( ui.newTab ) );
			}
		});
		
		
		// Directory View Configuration - Fields Mapping
		 // Using field_origin as flag to avoid 'drop' event being fired twice.
		
		init_draggables();
		
		init_droppables();
		
		$("a[href='#remove']").click( removeField );
		
		$("a[href='#settings']").click( openFieldSettings );
		
		// toggle view of "drop message" when active areas are empty or not.
		toggleDropMessage();
		
		
		
		// Directory View Configuration - Widgets
		$("a[href='#widget-settings']").click( openWidgetSettings );
		
		$("table.form-table tr:even").addClass('alternate');
		
		
	});
 
}(jQuery));
