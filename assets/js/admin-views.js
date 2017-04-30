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
 * @since 1.0.0
 *
 * @typedef {{
 *   passed_form_id: bool,
 *   label_cancel: string
 *   label_continue: string,
 *   loading_text: string,
 *   nonce: string,
 *   label_close: string,
 *   field_loaderror: string,
 *   cookiepath: string,
 *   label_viewname: string,
 *   label_publisherror: string,
 * }} gvGlobals
 *
 * @typedef {{
 *  target: element,
 *  relatedTarget: element,
 *  which: number,
 *  pageX: number,
 *  pageY: number,
 *  metaKey: string,
 *  altKey: boolean,
 *  cancelable: bool,
 *  char: string,
 *  charCode: number,
 *  clientX: number,
 *  clientY: number,
 *  ctrlKey: bool,
 *  currentTarget: element,
 *  data: object,
 *  keyCode: number,
 *  namespace: string,
 *  result: object,
 *  type: string,
 *  preventDefault: function,
 *  stopImmediatePropagation: function
 * }} jQueryEvent
 */

(function( $ ) {

	var viewConfiguration, viewGeneralSettings;

	viewConfiguration = {

		// Checks if the execution is on a Start Fresh context
		startFreshStatus: false,

		/**
		 * @since 1.17.3
		 * @type {bool} Whether the alt (modifier) key is currently being clicked
		 */
		altKey: false,

		/**
		 * @since 1.14
		 * @type {int} The width of the modal dialogs to use for field and widget settings
		 */
		dialogWidth: 650,

		init: function () {

			// short tag
			var vcfg = viewConfiguration;

			//select form dropdown
			vcfg.gvSelectForm = $( '#gravityview_form_id' );

			vcfg.gvSwitchView = $('a[href="#gv_switch_view"]');

			//current form selection
			vcfg.currentFormId = vcfg.gvSelectForm.val();

			// Start by showing/hiding on load
			vcfg.toggleInitialVisibility( vcfg );

			// Start bind to $('body')
			$( 'body' )

				// Track modifier keys being clicked
				.on( 'keydown keyup', vcfg.altKeyListener )

				// select form
				.on( 'change', '#gravityview_form_id', vcfg.formChange )

				// start fresh button
				.on( 'click', 'a[href="#gv_start_fresh"]', vcfg.startFresh )

				// when saving the View, try to create form before proceeding
				.on( 'click', '#publish, #save-post', vcfg.processFormSubmit )

				// when saving the View, try to create form before proceeding
				.on( 'submit', '#post', vcfg.processFormSubmit )

				// Hover overlay show/hide
				.on( 'click', ".gv-view-types-hover", vcfg.selectTemplateHover )

				// Convert rel="external" to target="_blank" for accessibility
				.on( 'click', 'a[rel*=external]', vcfg.openExternalLinks )

				// close all tooltips if user clicks outside the tooltip
				.on( 'click mouseup keyup', vcfg.closeTooltips )

				// switch View (for existing forms)
				.on( 'click', 'a[href="#gv_switch_view"]', vcfg.switchView )

				// select template
				.on( 'click', 'a[href="#gv_select_template"]', vcfg.selectTemplate )

				// bind Add Field fields to the addField method
				.on( 'click', '.ui-tooltip-content .gv-fields', vcfg.startAddField )

				// When user clicks into the shortcode example field, select the example.
				.on( 'click', ".gv-shortcode input", vcfg.selectText )

				// When changing forms, update the form info helper links
				.on( 'gravityview_form_change', vcfg.updateFormLinks )

				// Show fields that are being used as links to single entry
				.on( 'change', ".gv-dialog-options input[name*=show_as_link]", vcfg.toggleShowAsEntry )

				// show field buttons: Settings & Remove
				.on( 'click', ".gv-field-controls a[href='#remove']", vcfg.removeField )

				// Clicking a settings link opens settings
				.on( 'click', ".gv-field-controls a[href='#settings']", vcfg.openFieldSettings )

				// Double-clicking a field/widget label opens settings
				.on( 'dblclick', ".gv-fields", vcfg.openFieldSettings )

				// Update checkbox visibility when having dependency checkboxes
				.on( 'change', ".gv-setting-list, #gravityview_settings", vcfg.toggleCheckboxes )

				.on( 'change', "#gravityview_settings", vcfg.zebraStripeSettings );

			// End bind to $('body')

			if( gvGlobals.passed_form_id ) {
				$( '#gravityview_form_id' ).trigger( 'change' );
			}
		},

		/**
		 * Listen for whether the altKey is being held down. If so, we modify some behavior.
		 *
		 * This is necessary here because clicking on <select> doesn't register the altKey properly
		 *
		 * @since 1.17.3
		 *
		 * @param {jQuery} e
		 */
		altKeyListener: function( e ) {
			viewConfiguration.altKey = e.altKey;
		},

		/**
		 * Update zebra striping when settings are changed
		 * This prevents two gray rows next to each other.
		 * @since 1.19
		 */
		zebraStripeSettings: function() {
			$( '#gravityview_settings table').find('tr').removeClass('alternate').filter(':visible:even' ).addClass( 'alternate' );
		},

		/**
		 * Show/hide checkboxes that have visibility conditionals
		 * @see GravityView_FieldType_checkboxes
		 * @param  {jQuery} e
		 */
		toggleCheckboxes: function (  e ) {

			var $parent = $( e.currentTarget );
			$conditionals = $parent.find( '[data-requires]' );

			$conditionals.each( function ()  {
				var requires = $( this ).data( 'requires' );
				var $checkbox = $parent.find(':checkbox[name$="['+requires+']"]');
				$( this ).toggle( $checkbox.is(':checked') );
			});
		},

		/**
		 * Close all tooltips if user clicks outside the tooltip or presses escape key
		 * @param  {jQueryEvent} e [description]
		 * @return {bool}   [description]
		 */
		closeTooltips: function ( e ) {

			var activeTooltips = $( "[data-tooltip='active']" );

			var close = false;
			var return_false = false;

			switch ( e.type ) {

				case 'keyup':

					// Escape key was pressed
					if ( e.keyCode === 27 ) {
						close = true;
					}

					break;

				case 'mouseup':

					if ( // If clicking inside the dialog or tooltip
					$( e.target ).parents( '.ui-dialog,.ui-tooltip' ).length ||

					// Or on the dialog or tooltip itself
					$( e.target ).is( '.ui-dialog,.ui-tooltip' ) ) {
						close = false;
					}

					// For tooltips, clicking on anything outside of the tooltip
					// should close it. Not for dialogs.
					else if ( activeTooltips.length > 0 ) {
						close = true;
					}

					// The click was on the close link
					if ( $( e.target ).parents( '.close' ).length ) {
						close = true;
					}

					break; // End mouseup switch

				// Run on click instead of mouseup so that when selecting a form using the
				// select, it doesn't close the dialog right away
				case 'click':

					// They clicked the overlay
					if ( $( e.target ).is( '.gv-overlay' ) ) {
						close = true;
						return_false = true;

						// Always remove the overlay
						$( e.target ).remove();
					}

					break;

			}


			if ( close ) {

				// Close all open tooltips
				activeTooltips.tooltip( "close" );

				// Close all open dialogs
				$( ".ui-dialog:visible" ).find( '.ui-dialog-content' ).dialog( "close" );

				// Prevent scrolling window on click close
				if ( return_false ) {
					return false;
				}
			}
		},

		/**
		 * Toggle the dashicon link representing whether the field is being used as a link to the single entry
		 * @param  {jQueryEvent} e jQuery event object
		 * @return {void}
		 */
		toggleShowAsEntry: function ( e ) {

			var parent = $( e.target ).parents( '.gv-fields' );

			var icon = parent.find( '.gv-field-controls .dashicons-admin-links' );

			icon.toggleClass( 'hide-if-js', $( e.target ).not( ':checked' ) );

		},

		/**
		 * Select the text of an input field on click
		 * @param  {jQueryEvent}    e     [description]
		 * @return {[type]}          [description]
		 */
		selectText: function ( e ) {
			e.preventDefault();

			$( this ).focus().select();

			return false;
		},

		toggleInitialVisibility: function ( vcfg ) {

			// There are no Gravity Forms forms
			if ( vcfg.gvSelectForm.length === 0 ) {
				return;
			}

			// check if there's a form selected
			if ( '' === vcfg.currentFormId ) {
				// if no form is selected, hide all the configs
				vcfg.hideView();

			} else {
				// if both form and template were selected, show View Layout config
				if ( $( "#gravityview_directory_template" ).val().length > 0 ) {
					$( "#gravityview_select_template" ).slideUp( 150 );
					vcfg.showViewConfig();
				} else {
					// else show the template picker
					vcfg.templateFilter( 'custom' );
					vcfg.showViewTypeMetabox();
				}
			}

			vcfg.togglePreviewButton();

			vcfg.zebraStripeSettings();

		},

		/**
		 * Only show the Preview button if a form is selected.
		 * Otherwise, gravityview_get_entries() doesn't work.
		 */
		togglePreviewButton: function() {

			var preview_button = $('#preview-action').find('.button');

			if( '' === viewConfiguration.gvSelectForm.val() ) {
				preview_button.hide();
			} else {
				preview_button.show();
			}

		},

		// hides template picker metabox and view config metabox
		hideView: function () {
			var vcfg = viewConfiguration;

			vcfg.currentFormId = '';
			vcfg.togglePreviewButton();
			$( "#gravityview_view_config, #gravityview_select_template, #gravityview_sort_filter, .gv-form-links" ).hide();
			viewGeneralSettings.metaboxObj.hide();

		},

		/**
		 * Update the Data Source links to the selected form
		 * @return {void}
		 */
		updateFormLinks: function () {
			var vcfg = viewConfiguration;

			$( '.gv-form-links a' ).each( function () {

				var new_url = $( this ).attr( 'href' ).replace( /id=([0-9]+)/gm, 'id=' + vcfg.gvSelectForm.val() );

				$( this ).attr( 'href', new_url );

			} );
		},

		/**
		 * Show/Hide
		 * @return {[type]} [description]
		 */
		toggleViewTypeMetabox: function () {
			var $templates = $( "#gravityview_select_template" );

			if ( $templates.is( ':visible' ) ) {

				viewConfiguration.gvSwitchView.text( function () {
					return $( this ).attr( 'data-text-backup' );
				} );

				$templates.slideUp( 150 );

			} else {

				viewConfiguration.gvSwitchView.attr( 'data-text-backup', function () {
					return $( this ).text();
				} ).text( gvGlobals.label_cancel );

				$templates.slideDown( 150 );
			}
		},

		showViewTypeMetabox: function () {
			$( "#gravityview_select_template" ).slideDown( 150 );
		},

		/**
		 * Triggered when the Start Fresh button has been clicked
		 * @param {jQueryEvent} e
		 */
		startFresh: function ( e ) {
			e.preventDefault();
			var vcfg = viewConfiguration;

			//todo: what to do if you start fresh and then select another form!?
			//
			vcfg.startFreshStatus = true;

			// If the form has been chosen and there are GF forms to choose from
			if ( vcfg.currentFormId !== '' && vcfg.gvSelectForm.length > 0 ) {
				vcfg.showDialog( '#gravityview_form_id_dialog' );
			} else {
				vcfg.startFreshContinue();
			}
		},

		startFreshContinue: function () {
			var vcfg = viewConfiguration;

			// start fresh on save trigger
			$( '#gravityview_form_id_start_fresh' ).val( '1' );

			// Reset the selected form value
			$( '#gravityview_form_id' ).val( '' );

			vcfg.gvSwitchView.hide();

			// show templates
			vcfg.templateFilter( 'preset' );
			vcfg.showViewTypeMetabox();

			// hide config metabox
			vcfg.hideViewConfig();

			vcfg.togglePreviewButton();
		},

		/**
		 * The Data Source dropdown has been changed. Show alert dialog or process.
		 * @param {jQueryEvent} e
		 * @return void
		 */
		formChange: function ( e ) {
			e.preventDefault();
			var vcfg = viewConfiguration;

			// Holding down on the alt key while switching forms allows you to change forms without resetting configurations
			if( vcfg.altKey ) {
				return;
			}

			vcfg.startFreshStatus = false;

			if ( vcfg.currentFormId !== '' && vcfg.currentFormId !== $( this ).val() ) {
				vcfg.showDialog( '#gravityview_form_id_dialog' );
			} else {
				vcfg.formChangeContinue();
			}

			vcfg.togglePreviewButton();
		},

		formChangeContinue: function () {
			var vcfg = viewConfiguration;

			if ( '' === vcfg.gvSelectForm.val() ) {
				vcfg.hideView();
			} else {

				// Let merge tags know not to initialize
				$( 'body' ).trigger( 'gravityview_form_change' ).addClass( 'gv-form-changed' );

				vcfg.templateFilter( 'custom' );
				vcfg.showViewTypeMetabox();
				vcfg.getAvailableFields();
				vcfg.getSortableFields();
				vcfg.gvSwitchView.fadeOut( 150 );
			}
		},

		showDialog: function ( dialogSelector, buttons ) {

			var vcfg = viewConfiguration;

			var thisDialog = $( dialogSelector );

			var cancel_button = {
				text: gvGlobals.label_cancel,
				click: function () {
					if ( thisDialog.is( '#gravityview_form_id_dialog' ) ) {
						vcfg.startFreshStatus = false;
						vcfg.gvSelectForm.val( vcfg.currentFormId );
					}
					// "Changing the View Type will reset your field configuration. Changes will be permanent once you save the View."
					else if ( thisDialog.is( '#gravityview_switch_template_dialog' ) ) {
						vcfg.toggleViewTypeMetabox();
						vcfg.showViewConfig();
					}
					thisDialog.dialog( 'close' );
				}
			};

			var continue_button = {
				text: gvGlobals.label_continue,
				click: function () {
					if ( thisDialog.is( '#gravityview_form_id_dialog' ) ) {
						if ( vcfg.startFreshStatus ) {
							vcfg.startFreshContinue();
						} else {
							vcfg.formChangeContinue();
						}
					}
					// "Changing the View Type will reset your field configuration. Changes will be permanent once you save the View."
					else if ( thisDialog.is( '#gravityview_switch_template_dialog' ) ) {
						vcfg.selectTemplateContinue();
						vcfg.toggleViewTypeMetabox();
					}

					thisDialog.dialog( 'close' );
				}
			};

			var default_buttons = [ cancel_button, continue_button ];

			// If the buttons var isn't passed, use the defaults instead.
			buttons = buttons || default_buttons;

			thisDialog.dialog( {
				dialogClass: 'wp-dialog gv-dialog',
				appendTo: thisDialog.parent(),
				draggable: false,
				resizable: false,
				width: function () {

					// If the window is wider than {vcfg.dialogWidth}px, use vcfg.dialogWidth
					if ( $( window ).width() > vcfg.dialogWidth ) {
						return vcfg.dialogWidth;
					}

					// Otherwise, return the window width, less 10px
					return $( window ).width() - 10;
				},
				open: function () {
					$( '<div class="gv-overlay" />' ).prependTo( '#wpwrap' );
					return true;
				},
				close: function ( e ) {
					e.preventDefault();

					vcfg.setCustomLabel( thisDialog );

					$( '#wpwrap').find('> .gv-overlay' ).fadeOut( 'fast', function () {
						$( this ).remove();
					} );
				},
				closeOnEscape: true,
				buttons: buttons
			} );

		},

		/**
		 * Update the field display to show the custom label while editing
		 * @param {jQuery} dialog The dialog object
		 */
		setCustomLabel: function ( dialog ) {

			// Does the field have a custom label?
			var $custom_label = $( '[name*=custom_label]', dialog );

			var $label = dialog.parents( '.gv-fields' ).find( '.gv-field-label' );

			// If there's a custom title, use it for the label.
			if ( $custom_label.length ) {

				var custom_label_text = $custom_label.val().trim();

				// Make sure the custom label isn't empty
				if( custom_label_text.length > 0 ) {
					$label.html( custom_label_text );
				} else {
					// If there's no custom title, then use the original
					// @see GravityView_Admin_View_Item::getOutput()
					$label.html( $label.attr( 'data-original-title' ) );
				}

			}

		},

		/**
		 * @todo Combine with the embed shortcode dropdown
		 * @param  {string} context Context (multiple, single, edit)
		 * @param  {string} id      Template ID
		 * @return {void}
		 */
		getSortableFields: function ( context, id ) {

			var vcfg = viewConfiguration;

			// While it's loading, disable the field, remove previous options, and add loading message.
			$( "#gravityview_sort_field" ).prop( 'disabled', 'disabled' ).empty().append( '<option>' + gvGlobals.loading_text + '</option>' );

			var data = {
				action: 'gv_sortable_fields_form',
				nonce: gvGlobals.nonce
			};

			if ( context !== undefined && 'preset' === context ) {
				data.template_id = id;
			} else {
				data.form_id = vcfg.gvSelectForm.val();
			}

			$.post(ajaxurl, data, function (response) {
				if (response !== 'false' && response !== '0') {
					$("#gravityview_sort_field").empty().append(response).prop('disabled', null);
				}
			});

		},

		/**
		 * Hide metaboxes related to view configuration.
		 * @return {void}
		 */
		hideViewConfig: function () {
			$( "#gravityview_view_config" ).slideUp( 150 );

			$( document ).trigger( 'gv_admin_views_hideViewConfig' );
		},

		showViewConfig: function () {

			$( '#gravityview_view_config' ).slideDown( 150 );

			viewGeneralSettings.metaboxObj.show();
			viewConfiguration.toggleDropMessage();
			viewConfiguration.init_droppables();
			viewConfiguration.init_tooltips();

			$( document ).trigger( 'gv_admin_views_showViewConfig' );
		},

		/**
		 * @param {jQueryEvent} e
		 */
		switchView: function ( e ) {
			e.preventDefault();
			e.stopImmediatePropagation();

			var vcfg = viewConfiguration;

			vcfg.templateFilter( 'custom' );
			vcfg.toggleViewTypeMetabox();
		},

		/**
		 * Change which filters to show, depending on whether the form is Start Fresh or pre-existing forms
		 * @param  {string} templateType Checks against the `data-filter` attribute of the HTML
		 * @return {[type]}              [description]
		 */
		templateFilter: function ( templateType ) {
			$( ".gv-view-types-module" ).each( function () {
				if ( $( this ).attr( 'data-filter' ) === templateType ) {
					$( this ).parent().show();
				} else {
					$( this ).parent().hide();
				}
			} );
		},

		/**
		 * @param {jQueryEvent} e
		 */
		selectTemplate: function ( e ) {
			var vcfg = viewConfiguration;

			e.preventDefault();
			e.stopImmediatePropagation();

			// get selected template
			vcfg.wantedTemplate = $( this );
			var currTemplateId = $( "#gravityview_directory_template" ).val(),
			selectedTemplateId = vcfg.wantedTemplate.attr( "data-templateid" ),
			regexMatch = /(.*?)_(.*?)$/i,
			currTemplateIdSlug = currTemplateId.replace( regexMatch, '$2' ),
			selectedTemplateIdSlug = selectedTemplateId.replace( regexMatch, '$2' ),
			slugmatch = ( selectedTemplateIdSlug === currTemplateIdSlug ),
			has_fields = $( '#directory-fields, #single-fields' ).find('.gv-droppable-area .gv-fields').length;

			// check if template is being changed
			if ( currTemplateId === '' || slugmatch || 0 === has_fields ) {
				$( "#gravityview_select_template" ).slideUp( 150 );
				vcfg.selectTemplateContinue( slugmatch );
			} else if ( currTemplateId !== selectedTemplateId ) {
				vcfg.showDialog( '#gravityview_switch_template_dialog' );
			} else {
				// show the same situation as before clicking in Start Fresh.
				vcfg.toggleViewTypeMetabox();
				vcfg.showViewConfig();
			}
		},

		selectTemplateContinue: function ( slugmatch ) {

			var vcfg = viewConfiguration, selectedTemplateId = vcfg.wantedTemplate.attr( "data-templateid" );

			// update template name
			$( "#gravityview_directory_template" ).val( selectedTemplateId ).change();

			//add Selected class
			var $parent = vcfg.wantedTemplate.parents( ".gv-view-types-module" );
			$parent.parents( ".gv-grid" ).find( ".gv-view-types-module" ).removeClass( 'gv-selected' );
			$parent.addClass( 'gv-selected' );

			vcfg.waiting('start');

			// check for start fresh context
			if ( vcfg.startFreshStatus ) {

				//fetch the available fields of the preset-form
				vcfg.getAvailableFields( 'preset', selectedTemplateId );

				//fetch the fields template config of the preset view
				vcfg.getPresetFields( selectedTemplateId );

				//fetch Sortable fields
				vcfg.getSortableFields( 'preset', selectedTemplateId );

			} else {

				if( ! slugmatch ) {
					//change view configuration active areas
					vcfg.updateActiveAreas( selectedTemplateId );
				} else {
					vcfg.waiting('stop');
				}

				vcfg.gvSwitchView.fadeIn( 150 );
				vcfg.toggleViewTypeMetabox();

			}

		},

		/**
		 * When clicking the hover overlay, select the template by clicking the #gv_select_template button
		 * @param  {jQueryEvent}    e     jQuery event object
		 * @return void
		 */
		selectTemplateHover: function ( e ) {
			e.preventDefault();
			e.stopImmediatePropagation();
			$( this ).find( 'a[href="#gv_select_template"]' ).trigger( 'click' );
		},

		openExternalLinks: function () {
			window.open( this.href );
			return false;
		},

		/**
		 * Display a screenshot of the current template. Not currently in use.
		 *
		 * @todo REMOVE ?
		 * @param  {jQueryEvent}    e     jQuery event object
		 * @return void
		 */
		previewTemplate: function ( e ) {
			e.preventDefault();
			e.stopImmediatePropagation();
			var parent = $( event.currentTarget ).parents( ".gv-view-types-module" );
			parent.find( ".gv-template-preview" ).dialog( {
				dialogClass: 'wp-dialog gv-dialog',
				appendTo: $( "#gravityview_select_template" ),
				width: viewConfiguration.dialogWidth,
				open: function () {
					$( '<div class="gv-overlay" />' ).prependTo( '#wpwrap' );
				},
				close: function () {
					$( this ).dialog( "option", "appendTo", parent );
					$( '#wpwrap' ).find('> .gv-overlay' ).fadeOut( 'fast', function () {
						$( this ).remove();
					} );
				},
				closeOnEscape: true,
				buttons: [
					{
						text: gvGlobals.label_close,
						click: function () {
							$( this ).dialog( 'close' );
						}
					}
				]
			} );

		},

		/**
		 * @param {string} template The template ID
		 */
		updateActiveAreas: function ( template ) {
			var vcfg = viewConfiguration;

			$( "#directory-active-fields, #single-active-fields" ).children().remove();

			var data = {
				action: 'gv_get_active_areas',
				template_id: template,
				nonce: gvGlobals.nonce
			};

			vcfg.updateViewConfig( data );
		},

		/**
		 * @param {string} template The template ID
		 */
		getPresetFields: function ( template ) {
			var vcfg = viewConfiguration;

			$( "#directory-active-fields, #single-active-fields" ).children().remove();

			var data = {
				action: 'gv_get_preset_fields',
				template_id: template,
				nonce: gvGlobals.nonce
			};

			vcfg.updateViewConfig( data );
		},

		/**
		 * POST to AJAX and insert the returned field HTML into zone DOM
		 *
		 * @since 1.17.2
		 * @param {object} data `action`, `template_id` and `nonce` keys
		 */
		updateViewConfig: function ( data ) {
			var vcfg = viewConfiguration;
			
			$.post( ajaxurl, data, function ( response ) {
				if ( response ) {
					var content = $.parseJSON( response );
					$( '#directory-header-widgets' ).html( content.header );
					$( '#directory-footer-widgets' ).html( content.footer );
					$( '#directory-active-fields' ).append( content.directory );
					$( '#single-active-fields' ).append( content.single );
					vcfg.showViewConfig();
					vcfg.waiting('stop');
				}
			} );
		},

		/**
		 * Toggle the "loading" indicator
		 * @since 1.16.5
		 * @param {string} action "start" or "stop"
		 */
		waiting: function( action ) {

			$containers = $( '#wpwrap,.gv-fields' );

			if( 'start' === action ) {
				$containers.addClass('gv-wait');
			} else {
				$containers.removeClass('gv-wait');
			}
		},


		// tooltips

		init_tooltips: function () {

			$( ".gv-add-field" ).tooltip( {
				content: function () {

					// Is the field picker in single or directory mode?
					//	var context = ( $(this).parents('#single-view').length ) ? 'single' : 'directory';
					var context = $( this ).attr( 'data-context' );

					switch ( $( this ).attr( 'data-objecttype' ) ) {
						case 'field':
							// If in Single context, show fields available in single
							// If it Directory, same for directory
							return $( "#" + context + "-available-fields" ).html();
						case 'widget':
							return $( "#directory-available-widgets" ).html();
					}
				},
				close: function () {
					$( this ).attr( 'data-tooltip', null );
				},
				open: function () {

					$( this ).attr( 'data-tooltip', 'active' ).attr( 'data-tooltip-id', $( this ).attr( 'aria-describedby' ) );

				},
				closeOnEscape: true,
				disabled: true, // Don't open on hover
				position: {
					my: "center bottom",
					at: "center top-12"
				},
				tooltipClass: 'top'
			} )// add title attribute so the tooltip can continue to work (jquery ui bug?)
				.attr( "title", "" ).on( 'mouseout focusout', function ( e ) {
					e.stopImmediatePropagation();
				} ).click( function ( e ) {

					// add title attribute so the tooltip can continue to work (jquery ui bug?)
					$( this ).attr( "title", "" );

					e.preventDefault();
					//e.stopImmediatePropagation();

					$( this ).tooltip( "open" );

				} );

		},

		/**
		 * Refresh Gravity Forms tooltips (the real help tooltips)
		 */
		refreshGFtooltips: function () {
			$( ".gf_tooltip" ).tooltip( {
				show: 500,
				hide: 1000,
				content: function () {
					return $( this ).prop( 'title' );
				}
			} );
		},


		/**
		 * Fetch the Available Fields for a given Form ID or Preset Template ID
		 * @param  {null|string}    preset
		 * @param  {string}    templateid      The "slug" of the View template
		 * @return void
		 */
		getAvailableFields: function ( preset, templateid ) {

			var vcfg = viewConfiguration;

			$( "#directory-available-fields, #single-available-fields, #edit-available-fields" ).find( ".gv-fields" ).remove();
			$( "#directory-active-fields, #single-active-fields, #edit-active-fields" ).find( ".gv-fields" ).remove();

			vcfg.toggleDropMessage();

			var data = {
				action: 'gv_available_fields',
				nonce: gvGlobals.nonce,
				context: 'directory'
			};

			if ( preset !== undefined && 'preset' === preset ) {
				data.template_id = templateid;
			} else {
				data.form_id = vcfg.gvSelectForm.val();
			}


			// Get the fields for the directory context
			$.post( ajaxurl, data, function ( response ) {
				if ( response ) {
					$( "#directory-available-fields" ).append( response );
				}
			} );


			// Now get the fields for the single context
			data.context = 'single';

			$.post( ajaxurl, data, function ( response ) {
				if ( response ) {
					$( "#single-available-fields" ).append( response );
				}
			} );

			// Now get the fields for the edit context
			data.context = 'edit';

			$.post( ajaxurl, data, function ( response ) {
				if ( response ) {
					$( "#edit-available-fields" ).append( response );
				}
			} );

		},

		/**
		 * When a field is clicked in the field picker, add the field or add all fields
		 * @param  {jQueryEvent} e [description]
		 * @return {void}
		 */
		startAddField: function ( e ) {

			// If the clicked field div contains the all fields label,
			// we're dealing with an all fields click!
			if ( $( this ).has( '.field-id-all-fields' ).length ) {
				viewConfiguration.addAllFields( $( this ) );
			} else {
				// Add a single field.
				viewConfiguration.addField( $( this ), e );
			}
		},

		/**
		 * Add all the fields available at once. Bam!
		 * @param  {object}    clicked jQuery object of the clicked "+ Add All Fields" link
		 */
		addAllFields: function ( clicked ) {

			clicked.siblings( '.gv-fields' ).each( function () {
				$( this ).trigger( 'click' );
			} );

			// We just added all the fields. No reason to show the tooltip.
			$( "a.gv-add-field[data-tooltip='active']" ).tooltip( "close" );

		},

		/**
		 * Drop selected field in the active area
		 * @param  {object} clicked jQuery DOM object of the clicked Add Field button
		 * @param  {jQueryEvent}    e     jQuery Event object
		 */
		addField: function ( clicked, e ) {
			e.preventDefault();

			var vcfg = viewConfiguration;

			var newField = clicked.clone().hide();
			var areaId = clicked.parents( '.ui-tooltip' ).attr( 'id' );
			var templateId = $( "#gravityview_directory_template" ).val();
			var tooltipId = clicked.parents( '.ui-tooltip' ).attr( 'id' );
			var addButton = $( 'a.gv-add-field[data-tooltip-id="' + tooltipId + '"]' );

			var data = {
				action: 'gv_field_options',
				template: templateId,
				area: addButton.attr( 'data-areaid' ),
				context: addButton.attr( 'data-context' ),
				field_id: newField.attr( 'data-fieldid' ),
				field_label: newField.find( '.gv-field-label' ).attr( 'data-original-title' ),
				field_type: addButton.attr( 'data-objecttype' ),
				input_type: newField.attr( 'data-inputtype' ),
				form_id: vcfg.currentFormId,
				nonce: gvGlobals.nonce
			};

			// Get the HTML for the Options <div>
			// - If there are no options, response will NULL
			// - If response is false, it means the request was invalid.
			$.ajax( {
				type: "POST",
				url: ajaxurl,
				data: data,
				async: true,
				beforeSend: function () {
					// Don't allow saving until this is done.
					vcfg.disable_publish();
				},
				complete: function () {
					// Enable saving after it's done
					vcfg.enable_publish();
				}
			} ).done( function ( response ) {

				// Add in the Options <div>
				newField.append( response );

				// If there are field options, show the settings gear.
				if ( $( '.gv-dialog-options', newField ).length > 0 ) {
					$( '.dashicons-admin-generic', newField ).removeClass( 'hide-if-js' );
				}

				// append the new field to the active drop
				$( 'a[data-tooltip-id="' + areaId + '"]' ).parents( '.gv-droppable-area' ).find( '.active-drop' ).append( newField ).end().attr( 'data-tooltip-id', '' );

				// Show the new field
				newField.fadeIn( 100, function () {

					// Remove existing merge tags, since otherwise GF will add another
					$( '.all-merge-tags' ).remove();

					// Only init merge tags if the View has been saved and the form hasn't been changed.
					if ( typeof(
							form
						) !== 'undefined' && $( 'body' ).not( '.gv-form-changed' ) ) {

						// Re-init merge tag dropdowns
						window.gfMergeTags = new gfMergeTagsObj( form );

					}

				} );

				// refresh the little help tooltips
				vcfg.refreshGFtooltips();

			} ).fail( function ( jqXHR ) {

				// Enable publish on error
				vcfg.enable_publish();

				// Something went wrong
				alert( gvGlobals.field_loaderror );

				console.log( jqXHR );

			} ).always( function () {

				vcfg.toggleDropMessage();

			} );

		},

		/**
		 * Enable the publish input; enable saving a View
		 * @return {void}
		 */
		enable_publish: function () {

			/**
			 * Added in ~ WP 3.8
			 * @see https://github.com/WordPress/WordPress/blob/master/wp-admin/js/post.js#L365-L367
			 */
			$( document ).trigger( 'autosave-enable-buttons.edit-post' );

			// Restore saving after settings are generated
			$( '#publishing-action').find('#publish' ).prop( 'disabled', null ).removeClass( 'button-primary-disabled' );
		},

		/**
		 * Disable the publish input; prevent saving a View
		 * @return {void}
		 */
		disable_publish: function () {

			/**
			 * Added in ~ WP 3.8
			 * @see https://github.com/WordPress/WordPress/blob/master/wp-admin/js/post.js#L363-L364
			 */
			$( document ).trigger( 'autosave-disable-buttons.edit-post' );

			$( '#publishing-action').find('#publish' ).prop( 'disabled', 'disabled' ).addClass( 'button-primary-disabled' );
		},

		// Sortables and droppables
		init_droppables: function () {

			var vcfg = viewConfiguration;

			// widgets
			$( '#directory-fields, #single-fields' ).find( ".active-drop-widget" ).sortable( {
				placeholder: "fields-placeholder",
				items: '> .gv-fields',
				distance: 2,
				revert: 75,
				connectWith: ".active-drop-widget",
				receive: function ( event, ui ) {
					// Check if field comes from another active area and if so, update name attributes.

					var sender_area = ui.sender.attr( 'data-areaid' ), receiver_area = $( this ).attr( 'data-areaid' );

					ui.item.find( '[name^="widgets[' + sender_area + ']"]' ).each( function () {
						var name = $( this ).attr( 'name' );
						$( this ).attr( 'name', name.replace( sender_area, receiver_area ) );
					} );

					vcfg.toggleDropMessage();

				}
			} );

			//fields
			$( '#directory-fields, #single-fields, #edit-fields' ).find( ".active-drop-field" ).sortable( {
				placeholder: "fields-placeholder",
				items: '> .gv-fields',
				distance: 2,
				revert: 75,
				connectWith: ".active-drop-field",
				receive: function ( event, ui ) {
					// Check if field comes from another active area and if so, update name attributes.
					if ( ui.item.find( ".gv-dialog-options" ).length > 0 ) {

						var sender_area = ui.sender.attr( 'data-areaid' ), receiver_area = $( this ).attr( 'data-areaid' );

						ui.item.find( '[name^="fields[' + sender_area + ']"]' ).each( function () {
							var name = $( this ).attr( 'name' );
							$( this ).attr( 'name', name.replace( sender_area, receiver_area ) );
						} );

					}

					vcfg.toggleDropMessage();

				}
			} );
		},

		toggleDropMessage: function () {

			$( '.active-drop' ).each( function () {
				if ( $( this ).find( ".gv-fields" ).length > 0 ) {
					$( this ).find( ".drop-message" ).hide();
				} else {
					$( this ).find( ".drop-message" ).fadeIn( 100 );
				}
			} );

		},

		/**
		 * Event handler to remove Fields from active areas
		 * @param {jQueryEvent} e
		 */
		removeField: function ( e ) {

			e.preventDefault();

			var vcfg = viewConfiguration;
			var area = $( e.currentTarget ).parents( ".active-drop" );

			// Nice little easter egg: when holding down control, get rid of all fields in the zone at once.
			if ( e.altKey && $( area ).find( '.gv-fields' ).length > 1 ) {

				// Show a confirm dialog
				var remove_all = window.confirm( gvGlobals.remove_all_fields );

				// If yes, remove all, otherwise don't do anything
				if ( remove_all ) {
					$( area ).find( '.gv-fields' ).remove();
					vcfg.toggleDropMessage();
				}

				return;
			}

			$( e.currentTarget ).parents( '.gv-fields' ).fadeOut( 'normal', function () {
				$( this ).remove();
				vcfg.toggleDropMessage();
			} );

		},

		/**
		 * Event handler to open dialog with Field Settings
		 * @param {jQueryEvent} e
		 */
		openFieldSettings: function ( e ) {
			e.preventDefault();

			var parent, vcfg = viewConfiguration;

			if ( $( e.currentTarget ).is( '.gv-fields' ) ) {
				parent = $( e.currentTarget );
			} else {
				parent = $( e.currentTarget ).parents( '.gv-fields' );
			}

			vcfg.updateVisibilitySettings( e, true );

			// Toggle checkbox when changing field visibility
			$( 'body' ).on( 'change', '.gv-fields input:checkbox', vcfg.updateVisibilitySettings );

			var buttons = [
				{
					text: gvGlobals.label_close,
					click: function () {
						$( this ).dialog( 'close' );
					}
				}
			];

			vcfg.showDialog( parent.find( ".gv-dialog-options" ), buttons );

		},

		/**
		 * @param {jQueryEvent} e Check the "only visible to..." checkbox if the capability isn't public
		 * @param {bool} first_run Is this the first run (on load)?
		 */
		updateVisibilitySettings: function ( e, first_run ) {

			var vcfg = viewConfiguration;

			// Is this coming from the window opening?
			first_run = first_run || false;

			// If coming from the openFieldSettings method, we need a different parent
			var $parent = $( e.currentTarget ).is( '.gv-fields' ) ? $( e.currentTarget ) : $( e.currentTarget ).parents( '.gv-fields' );

			// Custom Label should show only when "Show Label" checkbox is checked
			vcfg.toggleVisibility( $( 'input:checkbox[name*=show_label]', $parent ), $( '[name*=custom_label]', $parent ), first_run );

			// Toggle Email fields
			vcfg.toggleVisibility( $( 'input:checkbox[name*=emailmailto]', $parent ), $( '[name*=emailsubject],[name*=emailbody]', $parent ), first_run );

			// Toggle Source URL fields
			vcfg.toggleVisibility( $( 'input:checkbox[name*=link_to_source]', $parent ), $( '[name*=source_link_text]', $parent ), first_run );

			$( ".gv-setting-list", $parent ).trigger( 'change' );

			$( 'input:checkbox', $parent ).attr( 'disabled', null );

			// Link to Post should be disabled when Single Entry is checked
			if ( $( 'input:checkbox[name*=show_as_link]', $parent ).is( ':checked' ) ) {
				$( 'input:checkbox[name*=link_to_]', $parent ).attr( 'disabled', true );
			}

			// Link to Post should hide when Single Entry is checked
			if ( $( 'input:checkbox[name*=link_to_]:checked', $parent ).length > 0 ) {
				$( 'input:checkbox[name*=show_as_link]', $parent ).attr( 'disabled', true );
			}

			// Logged in capability selector should only show when Logged In checkbox is checked
			vcfg.toggleVisibility( $( 'input:checkbox[name*=only_loggedin]', $parent ), $( '[name*=only_loggedin_cap]', $parent ), first_run );

		},

		/**
		 * Show/Hide Visibility of an input's container list item based on the value of a checkbox
		 *
		 * @param  {jQuery} $checkbox The checkbox to use when determining show/hide. Checked: show; unchecked: hide
		 * @param  {jQuery} $toggled  The field whose container to show/hide
		 * @param  {boolean} first_run Is this the first run (on load)? If so, show/hide immediately
		 * @return {void}
		 */
		toggleVisibility: function ( $checkbox, $toggled, first_run ) {

			var speed = first_run ? 0 : 'fast';

			if ( $checkbox.is( ':checked' ) ) {
				$toggled.parents( '.gv-setting-container' ).fadeIn( speed );
			} else {
				$toggled.parents( '.gv-setting-container' ).fadeOut( speed );
			}

		},

		/**
		 * When the Publish/Update form is submitted
		 *
		 * - Make sure there is a GF Form selected. If doing Start Fresh, calls `createPresetForm()` to create the GF form for the template ID.
		 * - Serializes the field data so that the request isn't too large
		 *
		 * @param  {jQueryEvent} e [description]
		 * @return {boolean}   True: success; False: stuff didn't work out.
		 */
		processFormSubmit: function ( e ) {
			var vcfg = viewConfiguration;
			var templateId = $( "#gravityview_directory_template" ).val();

			// Create the form if we're starting fresh.
			// On success, this also sets the vcfg.startFreshStatus to false.
			if ( vcfg.startFreshStatus ) {
				vcfg.createPresetForm( e, templateId );
				return false;
			}

			// If the View isn't a Start Fresh view, we just return true
			// so that the click on the Publish button can process.
			if ( !vcfg.startFreshStatus || templateId === '' ) {

				// Serialize the inputs so that `max_input_vars`
				return vcfg.serializeForm( e );

			}

			return false;

		},

		/**
		 * Serialize all GV field data and submit it all as one field value
		 *
		 * To fix issues where there are too many array items, causing PHP max_input_vars threshold to be met
		 *
		 * @param  {jQueryEvent} e jQuery event object
		 *
		 * @return {false}
		 */
		serializeForm: function ( e ) {

			var $post = $('#post');

			if ( $post.data( 'gv-valid' ) ) {
				return true;
			}

			e.stopImmediatePropagation();

			$post.data( 'gv-valid', false );

			/**
			 * Add slashes to date fields so stripslashes doesn't strip all of them
			 * {@link http://phpjs.org/functions/addslashes/}
			 */
			$post.find( 'input[name*=date_display]' ).val(function() {
				return $( this ).val().replace( /[\\"']/g, '\\$&' ).replace( /\u0000/g, '\\0' );
			});

			// Get all the fields where the `name` attribute start with `fields`
			var $fields = $post.find( ':input[name^=fields]' );

			// Serialize the data
			var serialized_data = $fields.serialize();

			// Remove the fields from the $_POSTed data
			$fields.remove();

			// Add a field to the form that contains all the data.
			$post.append( $( '<input/>', {
				'name': 'gv_fields',
				'value': serialized_data,
				'type': 'hidden'
			} ) );

			// make sure the "slow" browsers did append all the serialized data to the form
			setTimeout( function () {

				$post.data( 'gv-valid', true );

				if ( 'click' === e.type ) {
					$( e.target ).click();
				} else {
					$post.submit();
				}

			}, 101 );

			return false;

		},

		/**
		 * Create a Gravity Forms form using a preset defined by the View Template selected during Start Fresh
		 *
		 * This is done just before the Publish click is registered.
		 *
		 * @see GravityView_Admin_Views::create_preset_form()
		 *
		 * @param {jQueryEvent} e
		 * @param {string} templateId Template ID
		 *
		 * @return boolean|void
		 */
		createPresetForm: function ( e, templateId ) {
			var vcfg = viewConfiguration;
			var $target = $( e.target );

			e.stopPropagation();

			// Try to create preset form in Gravity Forms. On success assign it to post before saving
			var data = {
				action: 'gv_set_preset_form',
				template_id: templateId,
				nonce: gvGlobals.nonce
			};


			$.ajax( {
				type: "POST",
				url: ajaxurl,
				data: data,
				async: false, // Allows returning the value. Important!

				success: function ( response ) {

					if ( response !== 'false' && response !== '0' ) {

						vcfg.startFreshStatus = false;

						//set the form id
						vcfg.gvSelectForm.find( "option:selected" ).removeAttr( "selected" ).end().append( response );

						// Continue submitting the form, since we preventDefault() above
						if ( 'click' === e.type ) {
							$target.click();
						} else {
							$('#post').submit();
						}

					} else {

						$target.before( '<div id="message" class="error below-h2"><p>' + gvGlobals.label_publisherror + '</p></div>' );

					}

				}
			} );

			return false;
		}

	}; // end viewConfiguration object


	/**
	 * Manages the General View Settings
	 *
	 * @since 1.7
	 *
	 * @type {{templateId: null, init: Function, updateSettingsDisplay: Function, toggleSetting: Function}}
	 */
	viewGeneralSettings = {

		/**
		 * Holds the current view type id (template)
		 */
		templateId: null,

		/**
		 * Holds the tabbed Settings metabox container
		 */
		metaboxObj: null,

		/**
		 * Init method
		 */
		init: function() {

			viewGeneralSettings.metaboxObj = $( '#gravityview_settings' );

			// Init general settings tabs
			viewGeneralSettings.initTabs();

			// Conditional display general settings & trigger display settings if template changes
			$('#gravityview_directory_template')
				.change( viewGeneralSettings.updateSettingsDisplay )
				.trigger('change');

			$('body')
				// Enable a setting tab (since 1.8)
				.on('gravityview/settings/tab/enable', viewGeneralSettings.enableSettingTab )

				// Disable a setting tab (since 1.8)
				.on('gravityview/settings/tab/disable', viewGeneralSettings.disableSettingTab );

		},

		/**
		 * Callback method to show/hide settings if template changes and settings have a specific template attribute
		 */
		updateSettingsDisplay: function () {

			viewGeneralSettings.templateId = $( this ).val();

			$( 'tr[data-show-if]' ).each( viewGeneralSettings.toggleSetting );

		},

		/**
		 * Show/Hides setting based on the template
		 */
		toggleSetting: function () {
			var row = $( this ), templates = row.attr( 'data-show-if' );

			// if setting field attribute is empty, leave..
			if ( templates.length < 1 ) {
				return;
			}


			if ( viewGeneralSettings.templateId.length > 0 && templates.indexOf( viewGeneralSettings.templateId ) > -1 ) {
				row.show();
			} else {
				row.find( 'select, input' ).val( '' ).prop( 'checked', false );
				row.hide();
			}

		},

		/**
		 * Set up the settings metabox vertical tabs
		 *
		 * @since 1.8
		 * @return {void}
		 */
		initTabs: function() {

			viewGeneralSettings.metaboxObj
				// What happens after tabs are generated
				.on( 'tabscreate', viewGeneralSettings.tabsCreate )

				// Force the sort metabox to be directly under the view configuration. Damn 3rd party metaboxes!
				.insertAfter( $('#gravityview_view_config') )

				// Make vertical tabs
				.tabs()
				.addClass( "ui-tabs-vertical ui-helper-clearfix" )
				.find('li')
				.removeClass( "ui-corner-top" );

		},

		/**
		 * After creating the Tabs we need to do a few tweaks to make it look good
		 *
		 * @since 1.8
		 *
		 * @param {jQueryEvent} event jQuery Event
		 * @param {Object} ui jQuery UI Tab element, with: `ui.tab` and `ui.panel`
		 *
		 * @return {void}
		 */
		tabsCreate: function( event, ui ){
			var $container = $( this ),
				$panels = $container.find( '.ui-tabs-panel' ),
				max = [];

			$panels.each( function(){
				max.push( $( this ).outerHeight( true ) );
			} ).css( { 'min-height': _.max( max ) } );
		},

		/**
		 * Enable a tab in the settings metabox
		 *
		 * Useful for when switching View types that support a type of setting (DataTables)
		 *
		 * @since 1.8
		 *
		 * @param {jQueryEvent} e jQuery Event
		 * @param {jQuery} tab DOM of tab to enable
		 *
		 * @return {void}
		 */
		enableSettingTab: function( e, tab ) {

			viewGeneralSettings.metaboxObj
				.tabs('enable', $( tab ).attr('id') );

		},

		/**
		 * Disable a tab in the settings metabox
		 *
		 * Useful for when switching View types that may not support a type of setting (DataTables)
		 *
		 * @since 1.8
		 *
		 * @param {jQueryEvent} e jQuery Event
		 * @param {jQuery} tab DOM of tab to enable
		 *
		 * @return {void}
		 */
		disableSettingTab: function( e, tab ) {

			viewGeneralSettings.metaboxObj
				.tabs('disable', $( tab ).attr('id') );

		}

	};  // end viewGeneralSettings object

	jQuery( document ).ready( function ( $ ) {

		// title placeholder
		$( '#title-prompt-text' ).text( gvGlobals.label_viewname );

		// start the general view settings magic
		viewGeneralSettings.init();

		// start the View Configuration magic
		viewConfiguration.init();

		//datepicker
		$( '.gv-datepicker' ).datepicker( {
			dateFormat: "yy-mm-dd",
			constrainInput: false // Allow strtotime() configurations
		} );

		// Save the state on a per-post basis
		var cookie_key = 'gv-active-tab-' + $( '#post_ID' ).val();

		// The default tab is the first (0)
		var activate_tab = $.cookie( cookie_key );
		if ( activate_tab === 'undefined' ) {
			activate_tab = 0;
		}

		if ( location.hash && $( location.hash ).length ) {
			activate_tab = $( location.hash ).index() - 1;
		}

		// View Configuration - Tabs (persisten after refresh)
		$( "#gv-view-configuration-tabs" ).tabs( {
			active: activate_tab,
			activate: function ( event, ui ) {

				// When the tab is activated, set a new cookie
				$.cookie( cookie_key, ui.newTab.index(), { path: gvGlobals.cookiepath } );
			}
		} );

	} );

}(jQuery));
