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

			// Start by showing/hiding on load
			vcfg.toggleInitialVisibility( vcfg );

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

			// Close open Dialog boxes when clicking on the overlay
			$('body').on('click', '.gv-overlay', function( e ) {
				$(".ui-dialog:visible .ui-dialog-titlebar .ui-button").click();
				return;
			});

			// close all tooltips if user clicks outside the tooltip
			$('body').on('mouseup keyup', function (e) {

				var close = false;

				// Escape key was pressed
				if(e.type === 'keyup' && e.keyCode == 27) { close = true; }

				// The click that was registered wasn't on the tooltip
				if(e.type === 'mouseup' && ((!$(e.target).is('.ui-tooltip') && !$(e.target).parents( '.ui-tooltip' ).length > 0) || $(e.target).parents('.close').length > 0 )) {
					close = true;
				}

				if (close) {

					// Close all open tooltips
					$("a.gv-add-field[data-tooltip='active']").tooltip("close");

				}
			});

		// Fields
			// bind Add Field fields to the addField method
			$('body').on('click', '.ui-tooltip-content .gv-fields', function(e) {

				// If the clicked field div contains the all fields label,
				// we're dealing with an all fields click!
				if( $(this).has('.field-id-all-fields').length ) {
					vcfg.addAllFields( $(this) );
				} else {
					// Add a single field.
					vcfg.addField( $(this), e );
				}
			});

			// When user clicks into the shortcode example field, select the example.
			$('body').on('click', ".gv-shortcode input", vcfg.selectText );

			// show field buttons: Settings & Remove
			$('body').on('click', "span.gv-field-controls a[href='#remove']", vcfg.removeField );
			$('body').on('click', "span.gv-field-controls a[href='#settings']", vcfg.openFieldSettings );

			// Don't allow collapsing the main Configuration metabox.
			$('#gravityview_view_config').find('.hndle,.handlediv').off('click').on('click', function(e) {
				e.preventDefault();
				return false;
			});

			$('body').on('dblclick', ".gv-fields", function(e) {
				vcfg.openFieldSettings(e);
			});

			// when saving the View, try to create form before proceeding
			$(document).on( 'click', '#publish', vcfg.createPresetForm );


		},

		/**
		 * Select the text of an input field on click
		 * @filter default text
		 * @action default text
		 * @param  {[type]}    e     [description]
		 * @return {[type]}          [description]
		 */
		selectText: function( e ) {
			e.preventDefault();

			$(this).focus().select();

			return false;
		},

		toggleInitialVisibility: function( vcfg ) {

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
				vcfg.showDialog( '#gravityview_form_id_dialog' );
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

		/**
		 * The Data Source dropdown has been changed. Show alert dialog or process.
		 * @return void
		 */
		formChange: function( e ) {
			e.preventDefault();
			var vcfg = viewConfiguration;

			vcfg.startFreshStatus = false;

			if( vcfg.currentFormId !== ''  && vcfg.currentFormId !== $(this).val() ) {
				vcfg.showDialog( '#gravityview_form_id_dialog' );
			} else {
				vcfg.formChangeContinue();
			}
		},

		formChangeContinue: function() {
			var vcfg = viewConfiguration;
			if( '' === vcfg.gvSelectForm.val() ) {
				vcfg.hideView();
			} else {

				// Let merge tags know not to initialize
				$('body').addClass('gv-form-changed');

				vcfg.templateFilter('custom');
				vcfg.showTemplates();
				vcfg.getAvailableFields();
				vcfg.getSortableFields();
			}

			//vcfg.getSortableFields();

		},

		showDialog: function( dialogSelector, buttons ) {

			var vcfg = viewConfiguration;

			var thisDialog = $( dialogSelector );

			var default_buttons = [
				{
					text: gvGlobals.label_cancel,
					click: function() {
						if( thisDialog.is('#gravityview_form_id_dialog') ) {
							vcfg.startFreshStatus = false;
							vcfg.gvSelectForm.val( vcfg.currentFormId );
						} else if ( thisDialog.is('#gravityview_switch_template_dialog') ) {
							$("#gravityview_select_template").slideUp(150);
						}
						thisDialog.dialog('close');
					}
				},
				{
					text: gvGlobals.label_continue,
					click: function() {
						if( thisDialog.is('#gravityview_form_id_dialog') ) {
							if( vcfg.startFreshStatus ) {
								vcfg.startFreshContinue();
							} else {
								vcfg.formChangeContinue();
							}
						} else if ( thisDialog.is('#gravityview_switch_template_dialog') ) {
							vcfg.selectTemplateContinue();
						}

						thisDialog.dialog('close');
					}
				}
			];

			// If the buttons var isn't passed, use the defaults instead.
			buttons = buttons || default_buttons;

			thisDialog.dialog({
				dialogClass: 'wp-dialog',
				appendTo: thisDialog.parent(),
				draggable: false,
				resizable: false,
				width: function() {

					// If the window is wider than 550px, use 550
					if( $(window).width() > 550 ) { return 550; }

					// Otherwise, return the window width, less 10px
					return $(window).width() - 10;
				},
				open: function ( event, ui ) {
					$('<div class="gv-overlay" />').prependTo('#wpwrap');
					return true;
				},
				close: function () {
					$('#wpwrap > .gv-overlay').fadeOut( 'fast', function() { $(this).remove(); });
				},
				closeOnEscape: true,
				buttons: buttons
			});

		},

		/**
		 * @todo Combine with the embed shortcode dropdown
		 * @param  {[type]} context [description]
		 * @param  {[type]} id      [description]
		 * @return {[type]}         [description]
		 */
		getSortableFields: function( context, id ) {

			var vcfg = viewConfiguration;

			// While it's loading, disable the field, remove previous options, and add loading message.
			$("#gravityview_sort_field").prop('disabled', 'disabled').empty().append('<option>'+ gvGlobals.loading_text + '</option>');

			var data = {
				action: 'gv_sortable_fields_form',
				nonce: gvGlobals.nonce,
			};

			if( context !== undefined && 'preset' === context ) {
				data.template_id = id;
			} else {
				data.form_id = vcfg.gvSelectForm.val();
			}

			$.post( ajaxurl, data, function( response ) {
				if( response !== 'false' ) {
					$("#gravityview_sort_field").empty().append( response ).prop('disabled', null );
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
				vcfg.showDialog( '#gravityview_switch_template_dialog' );
			}
		},


		selectTemplateContinue: function() {

			var vcfg = viewConfiguration,
				selectedTemplateId = vcfg.wantedTemplate.attr("data-templateid");

			// update template name
			$("#gravityview_directory_template").val( selectedTemplateId ).change();

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

		/**
		 * When clicking the hover overlay, select the template by clicking the #gv_select_template button
		 * @param  object    e     jQuery event object
		 * @return void
		 */
		selectTemplateHover: function(e) {
			e.preventDefault();
			e.stopImmediatePropagation();
			$(this).find('a[href="#gv_select_template"]').trigger( 'click' );
		},

		/**
		 * Display a screenshot of the current template. Not currently in use.
		 * @param  object    e     jQuery event object
		 * @return void
		 */
		previewTemplate: function(e) {
			e.preventDefault();
			e.stopImmediatePropagation();
			var parent = $( event.currentTarget ).parents(".gv-view-types-module");
			parent.find(".gv-template-preview").dialog({
				dialogClass: 'wp-dialog',
				appendTo: $("#gravityview_select_template"),
				width: 550,
				open: function () {
					$('<div class="gv-overlay" />').prependTo('#wpwrap');
				},
				close: function () {
					$('#wpwrap > .gv-overlay').fadeOut( 'fast', function() { $(this).remove(); });
				},
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

			$.post( ajaxurl, data, function( response ) {
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

			$.post( ajaxurl, data, function( response ) {
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

		/**
		 * Fetch the Available Fields for a given Form ID or Preset Template ID
		 * @param  string    context Current context (seen as tabs): for example, "directory" or "single"
		 * @param  string    templateid      The "slug" of the View template
		 * @return void
		 */
		getAvailableFields: function( context, templateid ) {

			var vcfg = viewConfiguration;

			$("#directory-available-fields, #single-available-fields").find(".gv-fields").remove();
			$("#directory-active-fields, #single-active-fields").find(".gv-fields").remove();
			vcfg.toggleDropMessage();

			var data = {
				action: 'gv_available_fields',
				nonce: gvGlobals.nonce,
			};

			if( context !== undefined && 'preset' === context ) {
				data.template_id = templateid;
			} else {
				data.form_id = vcfg.gvSelectForm.val();
			}


			$.post( ajaxurl, data, function( response ) {
				if( response ) {

					$("#directory-available-fields").append( response );
					$("#single-available-fields").append( response );

				}
			});

		},

		/**
		 * Add all the fields available at once. Bam!
		 * @param  object    clicked jQuery object of the clicked "+ Add All Fields" link
		 */
		addAllFields: function( clicked ) {

			clicked.siblings('.gv-fields').each( function() {
				$(this).trigger('click');
			});

			// We just added all the fields. No reason
			$("a.gv-add-field[data-tooltip='active']").tooltip("close");
		},

		/**
		 * Drop selected field in the active area
		 * @param  object    e     jQuery Event object
		 */
		addField: function( clicked, e ) {
			e.preventDefault();

			var vcfg = viewConfiguration;

			var newField	= 	clicked.clone().hide();
			var	areaId 		= 	clicked.parents('.ui-tooltip').attr('id');
			var	templateId 	= 	$("#gravityview_directory_template").val();
			var	tooltipId 	= 	clicked.parents('.ui-tooltip').attr('id');
			var addButton 	= 	$('a.gv-add-field[data-tooltip-id="'+tooltipId+'"]');

			var data = {
				action: 'gv_field_options',
				template: templateId,
				area: addButton.attr('data-areaid'),
				context: addButton.attr('data-context'),
				field_id: newField.attr('data-fieldid'),
				field_label: newField.find("h5").text(),
				field_type: addButton.attr('data-objecttype'),
				input_type: newField.attr('data-inputtype'),
				nonce: gvGlobals.nonce,
			};

			// Get the HTML for the Options <div>
			// - If there are no options, response will NULL
			// - If response is false, it means the request was invalid.
			$.post( ajaxurl, data, function( response ) {

				// A response means we have the HTML for the field options
				if( response ) {

					// Add in the Options <div>
					newField.append( response );

					// Remove existing merge tags
					$('.all-merge-tags').remove();

					// Only init if the View has been saved and the form hasn't been changed.
					if( typeof(form) !== 'undefined' && $('body').not('.gv-form-changed') ) {

						// Re-init merge tag dropdowns
						window.gfMergeTags = new gfMergeTagsObj(form);
					}

					if( $('.gv-dialog-options', newField ).length > 0 ) {
						// There are options. Show the settings gear.
						$('.dashicons-admin-generic', newField).hide().removeClass('hide-if-js').fadeIn(100);
					}
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
			newField.fadeIn('fast');

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
				revert: 75,
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
				revert: 75,
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

			vcfg.updateVisibilitySettings( e, true );

			// Toggle checkbox when changing field visibility
			$('body').on( 'change', '.gv-fields input:checkbox', vcfg.updateVisibilitySettings );

			var buttons = [ {
				text: gvGlobals.label_close,
				click: function() {
					$(this).dialog('close');
				}
			}];

			vcfg.showDialog(parent.find(".gv-dialog-options"), buttons);

		},

		// Check the "only visible to..." checkbox if the capability isn't public
		updateVisibilitySettings: function( e, first_run ) {

			var vcfg = viewConfiguration;

			// Is this coming from the window opening?
			first_run = first_run || false;

			// If coming from the openFieldSettings method, we need a different parent
			$parent = $(e.currentTarget).is('.gv-fields') ? $(e.currentTarget) : $(e.currentTarget).parents('.gv-fields');

			// Custom Label should show only when "Show Label" checkbox is checked
			vcfg.toggleVisibility( $('input:checkbox[name*=show_label]', $parent) , $('[name*=custom_label]', $parent), first_run );


			$('input:checkbox', $parent).attr( 'disabled', null );

			// Link to Post should be disabled when Single Entry is checked
			if( $('input:checkbox[name*=show_as_link]', $parent).is(':checked') ) {
				$('input:checkbox[name*=link_to_]', $parent).attr('disabled', true);
			}

			// Link to Post should hide when Single Entry is checked
			if( $('input:checkbox[name*=link_to_]:checked', $parent).length > 0 ) {
				$('input:checkbox[name*=show_as_link]', $parent).attr('disabled', true);
			}

			// Logged in capability selector should only show when Logged In checkbox is checked
			vcfg.toggleVisibility( $('input:checkbox[name*=only_loggedin]', $parent) , $('[name*=only_loggedin_cap]', $parent), first_run );

		},

		toggleVisibility: function( $checkbox, $toggled, first_run ) {

			var speed = first_run ? 0 : 'fast';

			if( $checkbox.is(':checked') ) {
				$toggled.parents('li').fadeIn( speed );
			} else {
				$toggled.parents('li').fadeOut( speed );
			}

		},

		/**
		 * Create a Gravity Forms form using a preset defined by the View Template selected during Start Fresh
		 *
		 * This is done just before the Publish click is registered.
		 *
		 * @see GravityView_Admin_Views::create_preset_form()
		 * @return boolean|void
		 */
		createPresetForm: function() {
			var vcfg = viewConfiguration,
				templateId = $("#gravityview_directory_template").val();

			// If the View isn't a Start Fresh view, we just return true
			// so that the click on the Publish button can process.
			if( ! vcfg.startFreshStatus || templateId === '' ) {
				return true;
			}


			// Try to create preset form in Gravity Forms. On success assign it to post before saving
			var data = {
				action: 'gv_set_preset_form',
				template_id: templateId,
				nonce: gvGlobals.nonce,
			};

			$.post( ajaxurl, data, function( response ) {


				if( response != 'false' ) {

					vcfg.startFreshStatus = false;

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
		$('.gv-datepicker').datepicker({
			dateFormat: "yy-mm-dd",
			constrainInput: false // Allow strtotime() configurations
		});

		// Save the state on a per-post basis
		var cookie_key = 'gv-active-tab-'+$('#post_ID').val();

		// The default tab is the first (0)
		var activate_tab = $.cookie(cookie_key);
		if(activate_tab === 'undefined') { activate_tab = 0; }

		// View Configuration - Tabs (persisten after refresh)
		$("#gv-view-configuration-tabs").tabs({
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
