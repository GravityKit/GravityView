/**
 * Custom js script at Add New / Edit Views screen
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */


(function( $ ) {


	var viewConfiguration = {

		// Checks if the execution is on a Start Fresh context
		startFreshStatus: false,

		init: function() {

			// short tag
			var vcfg = viewConfiguration;

			//start fresh button
			vcfg.gvStartFreshButton = $('a[href="#gv_start_fresh"]');

			//select form dropdown
			vcfg.gvSelectForm = $('#gravityview_form_id');

			//current form selection
			vcfg.currentFormId = vcfg.gvSelectForm.val();


			// check if there's a form selected
			if( '' === vcfg.currentFormId ) {
				// if no form is selected, hide all the configs
				vcfg.hideView();

			} else {
				// if both form and template were selected, show View Layout config
				if( $("#gravityview_directory_template").val().length > 0 ){
					$("#gravityview_select_template").slideUp(150);
					vcfg.showViewConfig();
				} else {
					// else show the template picker
					vcfg.templateFilter('custom');
					vcfg.showTemplates();
				}

			}

			// start fresh button
			vcfg.gvStartFreshButton.click( vcfg.startFresh );

			// select form
			vcfg.gvSelectForm.change( vcfg.formChange );

			// switch View (for existing forms)
			$('a[href="#gv_switch_view"]').click( vcfg.switchView );

		// templates

			// select template
			$('a[href="#gv_select_template"]').click( vcfg.selectTemplate );
			$(".gv-view-types-hover").click( vcfg.selectTemplateHover );

			// preview template
			$('a[href="#gv_preview_template"]').click( vcfg.previewTemplate );

			// close all tooltips if user clicks outside the tooltip
			$(document).on('mouseup keyup', function (e) {
				var close = false;

				// Escape key was pressed
				if(e.type === 'keyup' && e.keyCode == 27) { close = true; }

				// The click that was registered wasn't on the tooltip
				if(e.type === 'mouseup' && ((!$(e.target).is('.ui-tooltip') && !$(e.target).parents( '.ui-tooltip' ).length > 0) || $(e.target).parents('.close').length > 0 )) {
					close = true;
				}

				// Close all open tooltips
				if (close) {
					$("a.gv-add-field[data-tooltip='active']").tooltip("close");
				}
			});

		// Fields
			// bind Add Field fields to the addField method
			$('body').on('click', '.ui-tooltip-content .gv-fields', vcfg.addField );

			// show field buttons: Settings & Remove
			$('body').on('click', "span.gv-field-controls a[href='#remove']", vcfg.removeField );
			$('body').on('click', "span.gv-field-controls a[href='#settings']", vcfg.openFieldSettings );

			$('body').on('dblclick', ".gv-fields", function(e) {
				vcfg.openFieldSettings(e);
			});

			// when saving the View, try to create form before proceeding
			$(document).on( 'click', '#publish', vcfg.createPresetForm );


		},

		// hides template picker metabox and view config metabox
		hideView: function() {
			var vcfg = viewConfiguration;

			vcfg.currentFormId = '';
			$("#gravityview_view_config, #gravityview_select_template").hide();

		},

		showTemplates: function() {
			$("#gravityview_select_template").slideDown(150);
		},

		startFresh: function(e){
			e.preventDefault();
			var vcfg = viewConfiguration;

			//todo: what to do if you start fresh and then select another form!?
			//
			vcfg.startFreshStatus = true;

			if( vcfg.currentFormId !== '' ) {
				vcfg.showDialog( 'gravityview_form_id_dialog' );
			} else {
				vcfg.startFreshContinue();
			}
		},

		startFreshContinue: function() {
			var vcfg = viewConfiguration;
			// start fresh on save trigger
			$('#gravityview_form_id_start_fresh').val('1');

			// show templates
			vcfg.templateFilter('preset');
			vcfg.showTemplates();

			// hide config metabox
			$("#gravityview_view_config").slideUp(150);
		},

		formChange: function() {
			var vcfg = viewConfiguration;

			vcfg.startFreshStatus = false;

			if( vcfg.currentFormId !== ''  && vcfg.currentFormId !== $(this).val() ) {
				vcfg.showDialog( 'gravityview_form_id_dialog' );
			} else {
				vcfg.formChangeContinue();
			}
		},

		formChangeContinue: function() {
			var vcfg = viewConfiguration;
			if( '' === vcfg.gvSelectForm.val() ) {
				vcfg.hideView();
			} else {
				vcfg.templateFilter('custom');
				vcfg.showTemplates();
				vcfg.getAvailableFields();
				vcfg.getSortableFields();
			}

			//vcfg.getSortableFields();

		},

		showDialog: function( dialogId ) {

			var vcfg = viewConfiguration;

			var thisDialog = $('#'+ dialogId );

			thisDialog.dialog({
				dialogClass: 'wp-dialog',
				appendTo: thisDialog.parent(),
				closeOnEscape: true,
				buttons: [ {
					text: gvGlobals.label_cancel,
					click: function() {
						if( 'gravityview_form_id_dialog' === dialogId ) {
							vcfg.startFreshStatus = false;
							vcfg.gvSelectForm.val( vcfg.currentFormId );
						} else if( 'gravityview_switch_template_dialog' === dialogId ) {
							$("#gravityview_select_template").slideUp(150);
						}
						thisDialog.dialog('close');
					} }, {
					text: gvGlobals.label_continue,
					click: function() {
						if( 'gravityview_form_id_dialog' === dialogId ) {
							if( vcfg.startFreshStatus ) {
								vcfg.startFreshContinue();
							} else {
								vcfg.formChangeContinue();
							}
						} else if ( 'gravityview_switch_template_dialog' === dialogId ) {
							vcfg.selectTemplateContinue();
						}

						thisDialog.dialog('close');
					}
				} ],
			});

		},

		getSortableFields: function( context, id ) {

			var vcfg = viewConfiguration;

			var data = {
				action: 'gv_sortable_fields_form',
				nonce: gvGlobals.nonce,
			};

			if( context !== undefined && 'preset' === context ) {
				data.template_id = id;
			} else {
				data.form_id = vcfg.gvSelectForm.val();
			}

			$.post( gvGlobals.ajaxurl, data, function( response ) {
				if( response !== 'false' ) {
					$("#gravityview_sort_field").empty();
					$("#gravityview_sort_field").append( response );
				}
			});

		},


		switchView: function(e){
			e.preventDefault();
			var vcfg = viewConfiguration;
			vcfg.templateFilter('custom');
			vcfg.showTemplates();
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
			e.stopImmediatePropagation();

			// get selected template
			vcfg.wantedTemplate = $(this);
			var	currTemplateId = $("#gravityview_directory_template").val(),
				selectedTemplateId = vcfg.wantedTemplate.attr("data-templateid");

			// check if template is being changed
			if( currTemplateId === '' ) {
				vcfg.selectTemplateContinue();
			} else if ( currTemplateId != selectedTemplateId ) {
				vcfg.showDialog( 'gravityview_switch_template_dialog' );
			}
		},


		selectTemplateContinue: function() {

			var vcfg = viewConfiguration,
				selectedTemplateId = vcfg.wantedTemplate.attr("data-templateid");

			// update template name
			$("#gravityview_directory_template").val( selectedTemplateId );

			//add Selected class
			var $parent = vcfg.wantedTemplate.parents(".gv-view-types-module");
			$parent.parents(".gv-grid").find(".gv-view-types-module").removeClass('gv-selected');
			$parent.addClass('gv-selected');

			// check for start fresh context
			if( vcfg.startFreshStatus ) {

				//fetch the available fields of the preset-form
				vcfg.getAvailableFields( 'preset', selectedTemplateId );

				//fetch the fields template config of the preset view
				vcfg.getPresetFields( selectedTemplateId );

				//fetch Sortable fields
				vcfg.getSortableFields( 'preset', selectedTemplateId );

			} else {
				//change view configuration active areas
				vcfg.updateActiveAreas( selectedTemplateId );

				$("#gravityview_select_template").slideUp(150);

			}

		},


		selectTemplateHover: function(e) {
			e.preventDefault();
			e.stopImmediatePropagation();
			$(this).find('a[href="#gv_select_template"]').trigger( 'click' );
		},

		previewTemplate: function(e) {
			e.preventDefault();
			e.stopImmediatePropagation();
			var parent = $( event.currentTarget ).parents(".gv-view-types-module");
			parent.find(".gv-template-preview").dialog({
				dialogClass: 'wp-dialog',
				appendTo: $("#gravityview_select_template"),
				width: 550,
				closeOnEscape: true,
				buttons: [ {
					text: gvGlobals.label_close,
					click: function() {
						$(this).dialog('close');
					}
				}],
				close: function() {
					$(this).dialog( "option", "appendTo", parent);
				}
			});

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
					$('#directory-header-widgets').html( content.header );
					$('#directory-footer-widgets').html( content.footer );
					$('#directory-active-fields').append( content.directory );
					$('#single-active-fields').append( content.single );
					vcfg.showViewConfig();
				}
			});

		},

		getPresetFields: function( template ) {
			var vcfg = viewConfiguration;

			$("#directory-active-fields, #single-active-fields").children().remove();

			var data = {
				action: 'gv_get_preset_fields',
				template_id: template,
				nonce: gvGlobals.nonce,
			};

			$.post( gvGlobals.ajaxurl, data, function( response ) {
				if( response ) {
					var content = $.parseJSON( response );
					$('#directory-header-widgets').html( content.header );
					$('#directory-footer-widgets').html( content.footer );
					$('#directory-active-fields').append( content.directory );
					$('#single-active-fields').append( content.single );
					 vcfg.showViewConfig();
				}
			});


		},

		showViewConfig: function() {
			var vcfg = viewConfiguration;
			$("#gravityview_view_config").slideDown(150);
			vcfg.toggleDropMessage();
			vcfg.init_droppables();
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
				close: function(event, ui) {
					$(this).attr('data-tooltip', '');
				},
				open: function(event, ui) {

					$(this)
						.attr('data-tooltip', 'active')
						.attr('data-tooltip-id', $(this).attr( 'aria-describedby' ) );
				},
				closeOnEscape: true,
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

				$(this).tooltip("open");

				// add title attribute so the tooltip can continue to work (jquery ui bug?)
				$(this).attr("title", "");
			});

		},

		// Fetch the Available Fields for a given Form ID or Preset Template ID
		getAvailableFields: function( context, id ) {

			var vcfg = viewConfiguration;

			$("#directory-available-fields, #single-available-fields").find(".gv-fields").remove();
			$("#directory-active-fields, #single-active-fields").find(".gv-fields").remove();
			vcfg.toggleDropMessage();

			var data = {
				action: 'gv_available_fields',
				nonce: gvGlobals.nonce,
			};

			if( context !== undefined && 'preset' === context ) {
				data.templateid = id;
			} else {
				data.formid = vcfg.gvSelectForm.val();
			}


			$.post( gvGlobals.ajaxurl, data, function( response ) {
				if( response ) {
					$("#directory-available-fields").append( response );
					$("#single-available-fields").append( response );

				}
			});

		},


		// drop selected field in the active area
		addField: function(e) {
			e.preventDefault();

			var vcfg = viewConfiguration;

			var newField = $(this).clone().hide(),
				areaId = $(this).parents('.ui-tooltip').attr('id'),
				templateId = $("#gravityview_directory_template").val(),
				tooltipId = $(this).parents('.ui-tooltip').attr('id'),
				addButton = $('a.gv-add-field[data-tooltip-id="'+tooltipId+'"]');

			var data = {
				action: 'gv_field_options',
				template: templateId,
				area: addButton.attr('data-areaid'),
				context: addButton.attr('data-context'),
				field_id: newField.attr('data-fieldid'),
				field_label: newField.find("h5").text(),
				field_type: addButton.attr('data-objecttype'),
				input_type: addButton.attr('data-objecttype'),
				nonce: gvGlobals.nonce,
			};

			$.post( gvGlobals.ajaxurl, data, function( response ) {
				if( response ) {
					newField.append( response );
				}
			});

			// append the new field to the active drop
			$('a[data-tooltip-id="'+ areaId +'"]')
				.parents('.gv-droppable-area')
					.find('.active-drop')
						.append(newField)
						.end()
				.attr('data-tooltip-id','');

			// Show the new field
			newField.fadeIn();

			// If there's more than one field in the area,
			// we move the tooltip.
			if(newField.siblings('.gv-fields').length > 0) {

				// Get the current position of the tooltip
				tooltipOffset = $('#'+tooltipId).offset();

				// Move the tooltip down by the height of the new field plus 5px margin bottom.
				// TODO: Clean up this so it doesn't use hard-coded margin size.
				$('#'+tooltipId).offset({
					top: (tooltipOffset.top + newField.outerHeight() + 5)
				});
			}

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

					var sender_area = ui.sender.attr('data-areaid'),
						receiver_area = $(this).attr('data-areaid');

					ui.item.find( '[name^="widgets['+ sender_area +']"]').each( function() {
						var name = $(this).attr('name');
						$(this).attr('name', name.replace( sender_area, receiver_area ) );
					});

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

			$('.active-drop').each( function( ) {
				if( $(this).find(".gv-fields").length > 0 ) {
					$(this).find(".drop-message").hide();
				} else {
					$(this).find(".drop-message").fadeIn(100);
				}
			});

		},

		// Event handler to remove Fields from active areas
		removeField: function( e ) {
			var vcfg = viewConfiguration;
			e.preventDefault();
			var area = $( e.currentTarget ).parents(".active-drop");
			$( e.currentTarget ).parents('.gv-fields').fadeOut('normal', function() {
				$(this).remove();
				vcfg.toggleDropMessage();
			});

		},

		// Event handler to open dialog with Field Settings
		openFieldSettings: function( e ) {
			e.preventDefault();

			var parent, vcfg = viewConfiguration;

			if($( e.currentTarget ).is('.gv-fields')) {
				parent = $( e.currentTarget );
			} else {
				parent = $( e.currentTarget ).parents('.gv-fields');
			}

			// Toggle checkbox when changing field visibility
			$('body').on( 'change', 'select[id*="loggedin_cap"]', vcfg.toggleVisibilityCheckbox );

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

		// Check the "only visible to..." checkbox if the capability isn't public
		toggleVisibilityCheckbox: function( e ) {
			var targetCheckbox = $(e.currentTarget).parent().find('input:checkbox[name*=only_loggedin]');

			if($(e.currentTarget).val() !== 'read') {
				targetCheckbox.attr( 'checked', 'checked' );
			} else {
				targetCheckbox.attr( 'checked', null );
			}
		},

		createPresetForm: function() {
			var vcfg = viewConfiguration,
				templateId = $("#gravityview_directory_template").val();

			if( ! vcfg.startFreshStatus || templateId === '' ) {
				return true;
			}


			// try to create preset form in Gravity Forms. On success assign it to post before saving
			var data = {
				action: 'gv_set_preset_form',
				template_id: templateId,
				nonce: gvGlobals.nonce,
			};

			$.post( gvGlobals.ajaxurl, data, function( response ) {

				if( response != 'false' ) {

					vcfg.startFreshStatus = false;
					$('#gravityview_form_id_start_fresh').val('0');

					//set the form id
					vcfg.gvSelectForm.find("option:selected").removeAttr("selected");
					vcfg.gvSelectForm.append( response );

					$("#publish").trigger('click');

				} else {
					$("#post").before('<div id="message" class="error below-h2"><p>'+ gvGlobals.label_publisherror +'</p></div>');
				}
			});

			return false;
		}

	}; // end viewConfiguration object



	$(document).ready( function() {

		// title placeholder
		$('#title-prompt-text').text( gvGlobals.label_viewname );

		// start the View Configuration magic
		viewConfiguration.init();

		//datepicker
		$('.gv-datepicker').datepicker({ dateFormat: "yy-mm-dd" });

		// Save the state on a per-post basis
		var cookie_key = 'gv-active-tab-'+$('#post_ID').val();

		// The default tab is the first (0)
		var activate_tab = $.cookie(cookie_key);
		if(activate_tab === 'undefined') { activate_tab = 0; }

		// View Configuration - Tabs (persisten after refresh)
		$("#tabs").tabs({
			active: activate_tab,
			activate: function( event, ui ) {

				// When the tab is activated, set a new cookie
				$.cookie(cookie_key, ui.newTab.index(), { path: gvGlobals.cookiepath });
			}
		});

		// Make zebra table rows
		$("#gravityview_template_settings .form-table tr:even").addClass('alternate');

	});

}(jQuery));
