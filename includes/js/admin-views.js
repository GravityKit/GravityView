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

		// $("#directory-available-fields, #single-available-fields").find(".gv-fields").draggable({
		// 	connectToSortable: 'div.active-drop',
		// 	distance: 2,
		// 	helper: 'clone',
		// 	revert: 'invalid',
		// 	zIndex: 100,
		// 	containment: 'document',
		// 	start: function() {
		// 		fieldOrigin = 'draggable';
		// 	}
		// });

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

		$('#directory-fields, #single-fields').find(".active-drop").sortable({
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

					//find active tab object to assign the template selector
					var templateId = '';
					if( 'single-view' === $("#tabs ul li.ui-tabs-active").attr('aria-controls') ) {
						templateId = $("input[name='gravityview_single_template']:checked").val();
					} else {
						templateId = $("input[name='gravityview_directory_template']:checked").val();
					}

					var data = {
						action: 'gv_field_options',
						template: templateId,
						area: $(this).attr('data-areaid'),
						field_id: ui.draggable.attr('data-fieldid'),
						field_label: ui.draggable.find("h5").text(),
						nonce: gvGlobals.nonce,
					};

					$.post( gvGlobals.ajaxurl, data, function( response ) {
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
		if( area.find(".gv-fields").length === 0 ) {
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
			buttons: [ {
				text: gvGlobals.label_close,
				click: function() {
					$(this).dialog('close');
				}
			}],
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
			buttons: [ {
				text: gvGlobals.label_close,
				click: function() {
					$(this).dialog('close');
				}
			} ],
		});
	}








	/* Select form and template */

	var viewConfiguration = {

		init: function() {

			// short tag
			var vcfg = viewConfiguration;

			//start fresh button
			vcfg.gvStartFreshButton = $('a[href="#gv_start_fresh"]');

			//select form dropdown
			vcfg.gvSelectForm = $('#gravityview_form_id');

			//current form selection
			vcfg.currentFormId = vcfg.gvSelectForm.val();


			if( '' === vcfg.currentFormId ) {
				vcfg.hideView();
			} else {
				vcfg.templateFilter('custom');
				vcfg.showTemplates();
				if( $("#gravityview_directory_template").val().length > 0 ){
					vcfg.showViewConfig();
				}
			}

			// start fresh button
			vcfg.gvStartFreshButton.click( function(e) {
				e.preventDefault();
				vcfg.startFresh();
			});

			// select form
			vcfg.gvSelectForm.change( vcfg.formChange );

			// templates

			// select template
			$('a[href="#gv_select_template"]').click( vcfg.selectTemplate );

			// close all tooltips if user clicks outside the tooltip
	        $(document).mouseup( function (e) {
			    var activeTooltip = $("a.gv-add-field[data-tooltip='active']");
			    if( !activeTooltip.is( e.target ) && activeTooltip.has( e.target ).length === 0 ) {
			        activeTooltip.tooltip("close");
			        activeTooltip.attr('data-tooltip', '');
			    }
			});

	        // sortables & droppables
	        vcfg.init_droppables();

	        // toggle view of "drop message" when active areas are empty or not.
	        vcfg.toggleDropMessage();

	        // field controls
	        $("a[href='#remove']").click( vcfg.removeField );
			$("a[href='#settings']").click( vcfg.openFieldSettings );

		},

		hideView: function() {
			var vcfg = viewConfiguration;

			vcfg.currentFormId = '';
			$("#gravityview_view_config, #gravityview_select_template").slideUp(150);
			//$("#directory-available-fields, #directory-active-fields, #single-available-fields, #single-active-fields").find(".gv-fields").remove();
		},

		showTemplates: function() {
			$("#gravityview_select_template").slideDown(150);
		},

		startFresh: function(){
			var vcfg = viewConfiguration;

			if( vcfg.currentFormId !== '' ) {
				vcfg.showDialog();
			} else {
				vcfg.templateFilter('preset');
				vcfg.showTemplates();
			}

		},

		formChange: function() {
			var vcfg = viewConfiguration;

			if( vcfg.currentFormId !== ''  && vcfg.currentFormId !== $(this).val() ) {
				vcfg.showDialog();
			} else {
				vcfg.templateFilter('custom');
				vcfg.showTemplates();
				vcfg.getNewFields();
			}
		},

		showDialog: function() {

			var vcfg = viewConfiguration;

			var thisDialog = $('#gravityview_form_id_dialog');

			thisDialog.dialog({
				dialogClass: 'wp-dialog',
				appendTo: thisDialog.parent(),
				closeOnEscape: true,
				buttons: [ {
					text: gvGlobals.label_cancel,
					click: function() {
						vcfg.gvSelectForm.val( vcfg.currentFormId );
						thisDialog.dialog('close');
					} }, {
					text: gvGlobals.label_continue,
					click: function() {
						if( '' === vcfg.gvSelectForm.val() ) {
							vcfg.hideView();
						} else {
							vcfg.getNewFields();
						}
						thisDialog.dialog('close');
					}
				} ],
			});

		},

		templateFilter: function( templateType ) {
			$(".gv-view-types-module").each( function() {
				if( $(this).attr('data-filter') === templateType ) {
					$(this).parent().show();
				} else {
					$(this).parent().hide();
				}
			});
		},

		selectTemplate: function(e) {
			var vcfg = viewConfiguration;

			e.preventDefault();
			// update template name
			var templateId = $(this).attr("data-templateid");
			$("#gravityview_directory_template").val( templateId );

			//add Selected class
			var $parent = $(this).parents(".gv-view-types-module");
			$parent.parents(".gv-grid").find(".gv-view-types-module").removeClass('gv-selected');
			$parent.addClass('gv-selected');

			//change view configuration active areas
			vcfg.updateActiveAreas( templateId );
			vcfg.showViewConfig();

		},

		updateActiveAreas: function( template ) {
			var vcfg = viewConfiguration;

			$("#directory-active-fields, #single-active-fields").children().remove();

			var data = {
				action: 'gv_get_active_areas',
				template_id: template,
				nonce: gvGlobals.nonce,
			};

			$.post( gvGlobals.ajaxurl, data, function( response ) {
				if( response ) {
					var content = $.parseJSON( response );
					$('#directory-active-fields').append( content.directory );
					//$('#single-active-fields').append( content.single );
					vcfg.init_droppables();
					vcfg.init_tooltips();
				}
			});

		},

		showViewConfig: function() {
			var vcfg = viewConfiguration;
			$("#gravityview_view_config").slideDown(150);
			vcfg.init_tooltips();
		},


		// tooltips

		init_tooltips: function() {

			var vcfg = viewConfiguration;

			$(".gv-add-field").tooltip({
				content: function() {
					var objType = $(this).attr('data-objecttype');
					if( objType === 'field' ) {
						return $("#directory-available-fields").html();
					} else if( objType === 'widget' ) {
						return $("#directory-available-widgets").html();
					}

				},
				disabled: true,
				position: {
					my: "center bottom",
					at: "center top-12",
				},
				tooltipClass: 'top',
				})
			.on('mouseout focusout', function(e) {
	                  e.stopImmediatePropagation();
	             })
			.click( function(e) {
					e.preventDefault();
					e.stopImmediatePropagation();
					if( $(this).attr('data-tooltip') !== undefined && $(this).attr('data-tooltip') == 'active' ) {
						$(this).tooltip("close");
						$(this).attr('data-tooltip', '');

					} else {
						$(this).tooltip("open");
						$(this).attr('data-tooltip', 'active');
						$(this).attr('data-tooltip-id', $(this).attr( 'aria-describedby' ) );

						// bind fields
						$('.ui-tooltip-content .gv-fields').click( vcfg.addField );
					}
					// add title attribute so the tooltip can continue to work (jquery ui bug?)
					$(this).attr("title", "");

			});

		},

		getNewFields: function() {
			var vcfg = viewConfiguration;

			vcfg.currentFormId = vcfg.gvSelectForm.val();

			$("#directory-available-fields, #single-available-fields").find(".gv-fields").remove();

			var data = {
				action: 'gv_available_fields',
				formid: vcfg.currentFormId,
				nonce: gvGlobals.nonce,
			};

			$.post( gvGlobals.ajaxurl, data, function( response ) {
				if( response ) {
					$("#directory-available-fields").append( response );
					$("#single-available-fields").append( response );

				}
			});

			vcfg.toggleDropMessage();
			vcfg.showTemplates();
		},


		// drop selected field in the active area
		addField: function(e) {
			e.preventDefault();

			var vcfg = viewConfiguration;

			var newField = $(this).clone(),
				areaId = $(this).parents('.ui-tooltip').attr('id'),
				templateId = $("#gravityview_directory_template").val(),
				tooltipId = $(this).parents('.ui-tooltip').attr('id'),
				addButton = $('a.gv-add-field[data-tooltip-id="'+tooltipId+'"]');



			var data = {
				action: 'gv_field_options',
				template: templateId,
				area: addButton.attr('data-areaid'),
				field_id: newField.attr('data-fieldid'),
				field_label: newField.find("h5").text(),
				field_type: addButton.attr('data-objecttype'),
				nonce: gvGlobals.nonce,
			};

			$.post( gvGlobals.ajaxurl, data, function( response ) {
				if( response ) {
					newField.append( response );
				}
			});

			// show field buttons: Settings & Remove
			newField.find("span.gv-field-controls a[href='#remove']").click( vcfg.removeField );
			newField.find("span.gv-field-controls a[href='#settings']").click( vcfg.openFieldSettings );

			// append the new field to the active drop
			$('a[data-tooltip-id="'+ areaId +'"]').parents('.gv-droppable-area').find('.active-drop').append(newField).end().attr('data-tooltip-id','');

			vcfg.toggleDropMessage();
		},

		// Sortables and droppables
		init_droppables: function() {

			var vcfg = viewConfiguration;

			// widgets
			$('#directory-fields, #single-fields').find(".active-drop-widget").sortable({
				placeholder: "fields-placeholder",
				items: '> .gv-fields',
				distance: 2,
				connectWith: ".active-drop-widget",
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

					vcfg.toggleDropMessage();

				}
			});

			//fields
			$('#directory-fields, #single-fields').find(".active-drop-field").sortable({
				placeholder: "fields-placeholder",
				items: '> .gv-fields',
				distance: 2,
				connectWith: ".active-drop-field",
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

					vcfg.toggleDropMessage();

				}
			});
		},

		toggleDropMessage: function() {

			$('.active-drop').each( function() {
				if( $(this).find(".gv-fields").length !== 0 ) {
					$(this).find(".drop-message").hide();
				} else {
					$(this).find(".drop-message").show();
				}
			});

		},

			// Event handler to remove Fields from active areas
		removeField: function( e ) {
			e.preventDefault();
			var area = $( event.currentTarget ).parents(".active-drop");
			$( event.currentTarget ).parent().parent().remove();
			if( area.find(".gv-fields").length === 0 ) {
				 area.find(".drop-message").show();
			}
		},

		// Event handler to open dialog with Field Settings
		openFieldSettings: function( e ) {
			e.preventDefault();
			var parent = $( event.currentTarget ).parent().parent();
			parent.find(".gv-dialog-options").dialog({
				dialogClass: 'wp-dialog',
				appendTo: parent,
				width: 550,
				closeOnEscape: true,
				buttons: [ {
					text: gvGlobals.label_close,
					click: function() {
						$(this).dialog('close');
					}
				}],
			});
		},

	}; // end viewConfiguration object








	function viewTemplatePicker( type ) {

		var thisType = type;

		this.init = function() {

			if( thisType != 'single' && thisType != 'directory' ) {
				return;
			}

			// assign selected class
			$('input[name="gravityview_'+ thisType +'_template"]:checked').parents(".gv-template").addClass('gv-selected');

			//
			$('#gravityview_'+ thisType +'_template_change').click( this.showDialog );

			// action when template changes
			$('input[name="gravityview_'+ thisType +'_template"]').change( this.changed );


		};

		this.showDialog = function( e ) {
			e.preventDefault();

			var $thisDialog = $('#gravityview_'+ thisType +'_template_dialog');

			$thisDialog.dialog({
				dialogClass: 'wp-dialog',
				width: 600,
				appendTo: $thisDialog.parent(),
				closeOnEscape: true,
				buttons: [ {
					text: gvGlobals.label_ok,
					click: function() {
						$thisDialog.dialog('close');
					} },
				],
			});
		};

		this.changed = function() {

			$('#'+ thisType +'-active-fields').find("fieldset.area").remove();

			var data = {
				action: 'gv_get_active_areas',
				template_id: $(this).val(),
				nonce: gvGlobals.nonce,
			};

			$.post( gvGlobals.ajaxurl, data, function( response ) {
				if( response ) {
					$('#'+ thisType +'-active-fields').append( response );
					init_droppables();
				}
			});

			//change class to highlight the selection
			var $parent = $(this).parents(".gv-template");
			$parent.siblings().removeClass('gv-selected');
			$parent.addClass('gv-selected');

			//update the template name when dialog is closed
			$('#gravityview_'+ thisType +'_template_name').text( $(this).next("img").attr('alt') );

		};
	}


	$(document).ready( function() {

		// start the View Configuration magic
		viewConfiguration.init();

		// View Configuration - Tabs (persisten after refresh)
		$("#tabs").tabs({
			active: $("#gv-active-tab").val(),
			activate: function( event, ui ) {
				$("#gv-active-tab").val( ui.newTab.parent().children().index( ui.newTab ) );
			}
		});

		// Directory View Configuration - Widgets
		//$("a[href='#widget-settings']").click( openWidgetSettings );

		// Make zebra table rows
		$("table.form-table tr:even").addClass('alternate');

	});

}(jQuery));
