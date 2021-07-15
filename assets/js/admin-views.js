/* global ajaxurl,gvGlobals,console,alert,form,gfMergeTagsObj,jQuery */
/**
 * Custom js script at Add New / Edit Views screen
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
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
		 * @since 2.10
		 * @type {bool} Whether to show "Are you sure you want to leave this page?" warning
		 */
		hasUnsavedChanges: false,

		/**
		 * @since 1.17.3
		 * @type {bool} Whether the alt (modifier) key is currently being clicked
		 */
		altKey: false,

		/**
		 * @since 1.14
		 * @type {int} The width of the modal dialogs to use for field and widget settings
		 */
		dialogWidth: 750,

		/**
		 * @since 2.10
		 * @type {bool} Whether an AJAX action is being performed
		 */
		performingAjaxAction: false,

		init: function () {

			// short tag
			var vcfg = viewConfiguration;

			//select form dropdown
			vcfg.gvSelectForm = $( '#gravityview_form_id' );

			vcfg.gvSwitchView = $('#gv_switch_view_button');

			//current form/template selection
			vcfg.currentFormId = vcfg.gvSelectForm.val();
			vcfg.currentTemplateId = $("#gravityview_directory_template").val();

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

				// close all tooltips if user clicks outside the tooltip
				.on( 'click', '.gv-field-filter-form span[role="button"]', vcfg.switchTooltipLayout )

				// switch View (for existing forms)
				.on( 'click', '#gv_switch_view_button', vcfg.switchView )

				.on( 'click', '.clear-all-fields', vcfg.removeAllFields )

				// select template
				.on( 'click', '.gv_select_template', vcfg.selectTemplate )

				// bind Add Field fields to the addField method
				.on( 'click', '.ui-tooltip-content .gv-fields', vcfg.startAddField )

				// When user clicks into the shortcode example field, select the example.
				.on( 'click', ".gv-shortcode input", vcfg.selectText )

				// When changing forms, update the form info helper links
				.on( 'gravityview_form_change', vcfg.updateFormLinks )

				// When changing forms, update the widget form_ids
				.on( 'gravityview_form_change', vcfg.updateWidgetFormIds )

				// Show fields that are being used as links to single entry
				.on( 'change', ".gv-dialog-options input[name*=show_as_link]", vcfg.toggleShowAsEntry )

				.on( 'change', '.gv-dialog-options input[name*=only_loggedin]', vcfg.toggleCustomVisibility )

				.on( 'change', '.gv-dialog-options [name*=allow_edit_cap]', vcfg.toggleCustomVisibility )

				// show field buttons: Settings & Remove
				.on( 'click', ".gv-field-controls .gv-remove-field", vcfg.removeField )

				// Clicking a settings link opens settings
				.on( 'click', ".gv-field-controls .gv-field-settings", vcfg.openFieldSettings )

				// Double-clicking a field/widget label opens settings
				.on( 'dblclick', ".gv-fields:not(.gv-nonexistent-form-field)", vcfg.openFieldSettings )

				// Update checkbox visibility when having dependency checkboxes
				.on( 'change', ".gv-setting-list, #gravityview_settings, .gv-dialog-options", vcfg.toggleCheckboxes )

				.on( 'change', "#gravityview_settings", vcfg.zebraStripeSettings )

				.on( 'click', '.gv-field-details--toggle', function( e ) {

					var $dialog = $( this ).parents('.ui-dialog');

					var was_closed = $( '.gv-field-details', $dialog ).hasClass('gv-field-details--closed');

					viewConfiguration.toggleFieldDetails( $dialog, was_closed );

					// When toggled, set a new cookie
					$.cookie( 'gv-field-details-expanded', was_closed, { path: gvGlobals.admin_cookiepath } );

					return false;
				})

				.on( 'search keydown keyup', '.gv-field-filter-form input:visible', vcfg.setupFieldFilters )

				/**
				 * When dismissing tab configuration warnings, don't show to the user again
				 */
				.on( 'click', '.gv-section .is-dismissible .notice-dismiss', function( e ) {

					var warning_name = $( this ).parents( '.gv-section' ).attr( 'id' ) + '-' + $( '#post_ID' ).val();

					$.cookie( 'warning-dismissed-' + warning_name, 1, { path: gvGlobals.admin_cookiepath } );

					$( 'body' ).trigger( 'gravityview/tabs-ready' );
				})

				.on( 'gravityview/loaded gravityview/tabs-ready gravityview/field-added gravityview/field-removed gravityview/all-fields-removed gravityview/show-as-entry', vcfg.toggleTabConfigurationWarnings )

				.on( 'gravityview/loaded gravityview/tabs-ready gravityview/field-added gravityview/field-removed gravityview/all-fields-removed gravityview/show-as-entry gravityview/view-config-updated', vcfg.toggleRemoveAllFields )

				.on( 'search keydown keyup', '.gv-field-filter-form input:visible', vcfg.setupFieldFilters )

				.on( 'focus', '.gv-add-field', function( e ) {
					$( this ).parent('.gv-fields').addClass( 'trigger--hover' );
				})

				.on( 'blur', '.gv-add-field', function( e ) {
					$( this ).parent('.gv-fields').removeClass( 'trigger--hover' );
				})

				.on( 'keydown', '.gv-add-field', function( e ) {
					if ( 13 !== e.keyCode && 32 !== e.keyCode ) {
						return true;
					}
					$( this ).parent( '.gv-fields' ).addClass( 'trigger--active' );
				})

				.on( 'keyup', '.gv-add-field', function( e ) {
					if ( 13 !== e.keyCode && 32 !== e.keyCode ) {
						return true;
					}
					$( this ).parent( '.gv-fields' ).removeClass( 'trigger--active' );
				});
			// End bind to $('body')

			$( window ).resize( function() {

				var $open_dialog = $( ".ui-dialog:visible" ).find( '.ui-dialog-content' );

				$open_dialog.dialog( 'option', 'position', {
					my: 'center',
					at: 'center',
					of: window
				} );

				// If dialog width is greater than 95% of window width, set to 95% window width
				var window_width = vcfg.dialogWidth;
				var ninety_five_per = $( window ).width() * 0.95;

				if ( vcfg.dialogWidth > ninety_five_per ) {
					window_width = ninety_five_per;
				}

				$open_dialog.dialog( 'option', 'width', window_width );
			});

			window.onbeforeunload = function() {
				return vcfg.hasUnsavedChanges ? true : null;
			};

			if( gvGlobals.passed_form_id ) {
				vcfg.gvSelectForm.trigger( 'change' );
			}
		},

		getCookieVal: function ( cookie ) {
			if ( ! cookie || cookie === 'undefined' || 'false' === cookie ) {
				return false;
			}

			return cookie;
		},

		/**
		 * Show or hide tab warning icons
		 *
		 * @since 2.10
		 * @param e
		 */
		toggleTabConfigurationWarnings: function ( e ) {

			var tabs = {
				single: {
					configured: ( $( '.gv-dialog-options input[name*=show_as_link]:checked', '#directory-active-fields' ).length || $( '[data-fieldid="entry_link"]', '#directory-active-fields' ).length ),
					icon: 'dashicons-media-default',
				},
				edit: {
					configured: $( '.gv-fields .field-key[value="edit_link"]' ).length,
					icon: 'dashicons-welcome-write-blog',
				}
			};

			$.each( tabs,  function ( index, value ) {

				var warning_name = index + '-fields' + '-' + $( '#post_ID' ).val();
				var dismissed_warning = viewConfiguration.getCookieVal( $.cookie( 'warning-dismissed-' + warning_name ) );

				var show_warning = ! dismissed_warning && value.configured === 0;

				$( '#' + index + '-fields' ).find( '.notice-warning' ).toggle( show_warning );
				$( 'li[aria-controls="' + index + '-view"]' )
					.toggleClass( 'tab-not-configured', show_warning )
					.find( '.tab-icon' )
					.toggleClass( 'dashicons-warning', show_warning )
					.toggleClass( value.icon, ! show_warning );
			});
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
			jQuery( '#gravityview_settings').find('table').each( function ( ) {
				$trs = $( this ).find('tr').not('[style="display: none;"]');

				$trs.removeClass('alternate');

				$trs.filter( ':even' ).addClass( 'alternate' );
			});
		},

		/**
		 * Show/hide checkboxes that have visibility conditionals
		 * @see GravityView_FieldType_checkboxes
		 * @param  {jQuery} e
		 */
		toggleCheckboxes: function (  e ) {

			var target = e.currentTarget ? e.currentTarget : e;

			viewConfiguration.toggleRequired( target, 'requires', false );
			viewConfiguration.toggleRequired( target, 'requires-not', true );

			var $parent = $( target ).is( '.gv-fields' ) ? $( target ) : $( target ).parents( '.gv-fields' );

			// "Link to Post" should hide when "Link to single entry" is checked
			viewConfiguration.toggleDisabled( $( 'input:checkbox[name*=link_to_]', $parent ), $( 'input:checkbox[name*=show_as_link]', $parent ) );

			// "Make Phone Number Clickable" should hide when "Link to single entry" is checked
			viewConfiguration.toggleDisabled( $( 'input:checkbox[name*=link_phone]', $parent ), $( 'input:checkbox[name*=show_as_link]', $parent ) );
		},

		/**
		 * If one setting is enabled, disable the other. Requires the input support `:checked` attribute.
		 *
		 * @since 2.10
		 *
		 * @param {jQuery} $one
		 * @param {jQuery} $two
		 */
		toggleDisabled: function ( $one, $two ) {

			if ( $one.is( ':checked' ) ) {
				$two.attr( 'disabled', true );
			}

			if ( $two.filter(':checked').length > 0 ) {
				$one.attr( 'disabled', true );
			}
		},

		/**
		 * Process conditional show/hide logic
		 *
		 * @since 2.3
		 *
		 * @param {jQueryEvent} currentTarget
		 * @param {string} data_attr The attribute to find in the target, like `requires` or `requires-not`
		 * @param {boolean} reverse_logic If true, find items that do not match the attribute value. True = `requires-not`; false = `requires`
		 */
		toggleRequired: function( currentTarget, data_attr, reverse_logic ) {

			var $parent = $( currentTarget );

			$parent
				.find( '[data-' + data_attr + ']' )
				.each( function ()  {
					var requires = $( this ).data( data_attr ),
						requires_array = requires.split('='),
						requires_name = requires_array[0],
						requires_value = requires_array[1];

					var $input = $parent.find(':input[name$="[' + requires_name + ']"]');

					if ( $input.is(':checkbox') ) {
						if ( reverse_logic ) {
							$(this).toggle( $input.not(':checked') );
						} else {
							$(this).toggle( $input.is(':checked') );
						}
					} else if ( requires_value !== undefined ) {
						if ( reverse_logic ) {
							$(this).toggle( $input.val() !== requires_value );
						} else {
							$(this).toggle( $input.val() === requires_value );
						}
					}
				});

		},

		/**
		 * When clicking the field picker layout, change the tooltip class
		 *
		 * @param  {jQueryEvent} e [description]
		 * @return {bool}   [description]
		 */
		switchTooltipLayout: function ( e ) {

			var layout = $( this ).data( 'value' );

			viewConfiguration.setTooltipLayout( layout );
		},

		setTooltipLayout: function ( layout ) {

			$( '.gv-items-picker--' + layout ).addClass( 'active' );

			$( '.gv-items-picker' ).not( '.gv-items-picker--' + layout ).removeClass( 'active' );

			$( '.gv-items-picker-container' ).attr( 'data-layout', layout );

			// When choice is made, set a new cookie
			$.cookie( 'gv-items-picker-layout', layout, { path: gvGlobals.admin_cookiepath } );
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
						if ( $( '.ui-autocomplete' ).is( ':visible' ) ) {
							return;
						}

						close = $( '.gv-field-filter-form input[data-has-search]:focus' ).length === 0;
						return_false = close;

						// The Beacon escape key behavior is flaky. Make it work better.
						if ( window.Beacon  ) {
							window.Beacon('close');
						}
					}

					// The click was on the close link
					if ( ( 13 === e.keyCode || 32 === e.keyCode ) && $( e.target ).is( '.close' ) || $( e.target ).is('.dashicons-dismiss') ) {
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

			parent.toggleClass( 'has-single-entry-link', $( e.target ).is( ':checked' ) );

			parent.find( '.gv-field-controls .dashicons-media-default' ).toggleClass( 'hide-if-js', $( e.target ).not( ':checked' ) );

			$( 'body' ).trigger( 'gravityview/show-as-entry', $( e.target ).is( ':checked' ) );
		},

		/**
		 * Toggle the dashicon link representing whether the field has custom visibility settings
		 * @param  {jQueryEvent} e jQuery event object
		 * @return {void}
		 */
		toggleCustomVisibility: function ( e ) {

			var custom_visibility;

			if ( $( e.target ).is('select') ) {
				custom_visibility = 'read' !== $( e.target ).val();
			} else {
				custom_visibility = $( e.target ).is( ':checked' );
			}

			var parent = $( e.target ).parents( '.gv-fields' );

			parent.toggleClass( 'has-custom-visibility', custom_visibility );

			parent.find( '.gv-field-controls .icon-custom-visibility' ).toggleClass( 'hide-if-js', ! custom_visibility );
		},

		/**
		 * Select the text of an input field on click
		 * @param  {jQueryEvent}    e     [description]
		 * @return {[type]}          [description]
		 */
		selectText: function ( e ) {
			e.preventDefault();

			$( this ).trigger('focus').trigger('select');

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
				if ( $( "#gravityview_directory_template" ).length && $( "#gravityview_directory_template" ).val().length > 0 ) {
					$( "#gravityview_select_template" ).slideUp( 150 );
					vcfg.showViewConfig();
				} else {
					// else show the template picker
					vcfg.templateFilter( 'custom' );
					vcfg.showViewTypeMetabox();
				}
			}

			vcfg.togglePreviewButton();

			vcfg.zebraStripeSettings( true );

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
		 * Update Widget form IDs to the selected form
		 * @return {void}
		 */
		updateWidgetFormIds: function() {
			var vcfg = viewConfiguration;

			$( '.field-form-id' ).each( function() {
				$( this ).val( vcfg.gvSelectForm.val() );
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

			vcfg.startFreshStatus = true;

			// If fields are configured (either via a form or preset selection), warn against making changes
			if ( vcfg.getConfiguredFields().length ) {
				vcfg.showDialog( '#gravityview_select_preset_dialog' );
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

			vcfg.currentFormId = '';
			vcfg.currentTemplateId = '';
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

			if ( vcfg.getConfiguredFields().length ) {
				vcfg.showDialog( '#gravityview_change_form_dialog' );
			} else {
				vcfg.formChangeContinue();
			}

			vcfg.togglePreviewButton();
		},

		formChangeContinue: function () {
			var vcfg = viewConfiguration;

			if ( ! vcfg.gvSelectForm.val() ) {
				vcfg.getConfiguredFields().remove();
				vcfg.hideView();
				vcfg.gvSwitchView.fadeOut( 150 );
			} else {
				vcfg.templateFilter( 'custom' );

				vcfg.getAvailableFields();
				vcfg.getSortableFields();

				if ( ! vcfg.currentFormId && ! vcfg.currentTemplateId ) {
					vcfg.showViewTypeMetabox();
					vcfg.gvSwitchView.fadeOut( 150 );
				} else {
					vcfg.gvSwitchView.show();
				}
			}

			vcfg.currentTemplateId = '';
			vcfg.currentFormId = vcfg.gvSelectForm.val();
			vcfg.hasUnsavedChanges = true;
			$( 'body' ).trigger( 'gravityview_form_change' ).addClass( 'gv-form-changed' );
		},

		showDialog: function ( dialogSelector, buttons ) {

			var vcfg = viewConfiguration;

			var thisDialog = $( dialogSelector );

			var cancel_button = {
				text: gvGlobals.label_cancel,
				click: function () {
					if ( thisDialog.is( '#gravityview_change_form_dialog' ) ) {
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
				click: function() {
					if ( thisDialog.is( '#gravityview_change_form_dialog' ) || thisDialog.is( '#gravityview_select_preset_dialog' ) ) {
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
				},
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

					vcfg.toggleCheckboxes( thisDialog );
					vcfg.setupFieldDetails( thisDialog );
					vcfg.setupCodeMirror( thisDialog );

					$( '#directory-fields, #single-fields' ).find( ".active-drop-widget" ).sortable( 'disable' );
					$( '#directory-fields, #single-fields, #edit-fields' ).find( ".active-drop-field" ).sortable('disable');

					return true;
				},
				close: function ( e ) {
					e.preventDefault();

					$( 'textarea.code', thisDialog ).each( function () {

						$CodeMirror = $( this ).next( '.CodeMirror' );

						if ( 0 === $CodeMirror.length || ! $CodeMirror[0].hasOwnProperty('CodeMirror') ) {
							return;
						}

						$CodeMirror[0].CodeMirror.toTextArea();
					} );

					$( '.gv-field-settings.active', '#gravityview_view_config' ).removeClass( 'active' );

					vcfg.setCustomLabel( thisDialog );

					$( '#wpwrap').find('> .gv-overlay' ).fadeOut( 'fast', function () {
						$( this ).remove();
					} );

					$( '#directory-fields, #single-fields' ).find( ".active-drop-widget" ).sortable( 'enable' );
					$( '#directory-fields, #single-fields, #edit-fields' ).find( ".active-drop-field" ).sortable('enable');

					$( 'body' ).trigger( 'gravityview/dialog-closed', thisDialog );
				},
				closeOnEscape: true,
				buttons: buttons
			} );

		},

		/**
		 * When opening a dialog, convert textareas with CSS class ".code" to codeMirror instances
		 *
		 * @since 2.10
		 * @param {jQuery} dialog
		 */
		setupCodeMirror: function ( dialog ) {

			$( 'textarea.code', dialog ).each( function () {
				var editor = wp.codeEditor.initialize( $( this ), {
					undoDepth: 1000
				} );

				// Leave room for
				editor.codemirror.setSize( '95%' );
				var $textarea = $( this );
				var editorId = $textarea.attr( 'id' );
				var mergeTags = window.gfMergeTags.getAutoCompleteMergeTags( $textarea );
				var mergeTag = '';
				var initialEditorCursorPos = editor.codemirror.getCursor();

				// Move merge tag before before CodeMirror in DOM to fix floating issue
				$textarea.parent().find( '.all-merge-tags' ).insertBefore( $textarea );

				// Set up Merge Tag autocomplete
				$textarea.autocomplete( {
					appendTo: $textarea.parent(),
					minLength: 1,
					position: {
						my: 'center top',
						at: 'center bottom',
						collision: 'none'
					},
					source: mergeTags,
					select: function ( event, ui ) {
						// insert the merge tag value without curly braces
						var val = ui.item.value.replace( /^{|}$/gm, '' );
						var currentEditorCursorPos = editor.codemirror.getCursor();

						editor.codemirror.replaceRange( val, initialEditorCursorPos, window.wp.CodeMirror.Pos( currentEditorCursorPos.line, currentEditorCursorPos.ch ) );
						editor.codemirror.focus();
						editor.codemirror.setCursor( window.wp.CodeMirror.Pos( currentEditorCursorPos.line, currentEditorCursorPos.ch + val.length + 1 ) );
					},
				} );

				var $autocompleteEl = $textarea.parent().find( 'ul.ui-autocomplete' );

				var closeAutocompletion = function () {
					$( '#' + editorId ).autocomplete( 'close' );
				};

				$( 'body' ).on( 'keyup', function ( e ) {
					if ( $autocompleteEl.is( ':visible' ) && 27 === e.which ) {
						e.preventDefault();
						closeAutocompletion();
						$textarea.focus();
					}
				} );

				editor.codemirror.on( 'mousedown', function () {
					closeAutocompletion();
				} );

				editor.codemirror.on( 'keydown', function ( el, e ) {
					if ( !$autocompleteEl.is( ':visible' ) ) {
						return;
					}

					if ( 38 === e.which || 40 === e.which || 13 === e.which ) {
						if ( $autocompleteEl.not( ':focus' ) ) {
							$autocompleteEl.focus();
						}

						e.preventDefault();
					}
				} );

				editor.codemirror.on( 'change', function ( e, obj ) {
					// detect curly braces and update the cursor position
					if ( obj.text[ 0 ] === '{}' ) {
						initialEditorCursorPos = editor.codemirror.getCursor();
					}

					// select everything between the initial and current cursor positions
					var currentEditorCursorPos = editor.codemirror.getCursor();
					mergeTag = editor.codemirror.getRange( {
						ch: initialEditorCursorPos.ch - 1,
						line: initialEditorCursorPos.line
					}, currentEditorCursorPos );

					// if the value starts with a curly braces, initiate autocompletion
					if ( mergeTag[ 0 ] === '{' ) {
						$( '#' + editorId ).autocomplete( 'search', mergeTag );

						return;
					}

					closeAutocompletion();
				} );
			} );
		},

		/**
		 * When opening a dialog, restore the Field Details visibility based on cookie
		 * @since 2.10
		 * @param {jQuery} dialog
		 */
		setupFieldDetails: function ( dialog ) {

			// Add the details to the title bar
			$( '.gv-field-details--container', dialog ).insertAfter( '.ui-dialog-title:visible' );

			// When the dialog opens, read the cookie
			// Otherwise, check for cookies
			var show_details_cookie = $.cookie( 'gv-field-details-expanded' );

			var show_details = viewConfiguration.getCookieVal( show_details_cookie );

			viewConfiguration.toggleFieldDetails( dialog, show_details );

			viewConfiguration.migrateSurveyScore( dialog );
		},

		/**
		 * Migrate Likert fields with [score] to [choice_display]
		 * @since 2.11
		 * @param {jQuery} $dialog
		 */
		migrateSurveyScore: function ( $dialog ) {

			// Only process on Survey fields
			if ( 0 === $dialog.parents('[data-inputtype="survey"]').length ) {
				return;
			}

			var $score = $dialog.find( '.gv-setting-container-score input' );

			if ( ! $score ) {
				return;
			}

			if ( 0 === $score.val() * 1 ) {
				return;
			}

			$dialog
				.find( '.gv-setting-container-choice_display input[value="score"]' )
				.trigger('click') // Update the choice
				.trigger('focus') // Highlight the selected choice
			;
		},

		/**
		 * Toggle visibility for field details
		 * @since 2.10
		 * @param {jQuery}  $dialog The open dialog
		 * @param {boolean} show_details Whether to show the field details or not
		 */
		toggleFieldDetails: function ( $dialog, show_details ) {
			$dialog
				.find( '.gv-field-details' ).toggleClass( 'gv-field-details--closed', ! show_details ).end()
				.find( '.gv-field-details--toggle .dashicons' )
				.toggleClass( 'dashicons-arrow-down', !! show_details )
				.toggleClass( 'dashicons-arrow-right', ! show_details ).end();
		},

		/**
		 * Update the field display to show the custom label while editing
		 * @param {jQuery} dialog The dialog object
		 */
		setCustomLabel: function ( dialog ) {

			// Does the field have a custom label?
			var $admin_label = $( '[name*=admin_label]', dialog );
			var $custom_label;

			if ( ! $admin_label.length || ! $admin_label.val() ) {
				$custom_label = $( '[name*=custom_label]', dialog );
			} else {
				$custom_label = $admin_label; // We have an administrative label for this field
			}

			var $label = dialog.parents( '.gv-fields' ).find( '.gv-field-label-text-container' );

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
			$( ".gravityview_sort_field" ).prop( 'disabled', 'disabled' ).empty().append( '<option>' + gvGlobals.loading_text + '</option>' );

			var data = {
				action: 'gv_sortable_fields_form',
				nonce: gvGlobals.nonce
			};

			if ( context !== undefined && 'preset' === context ) {
				data.template_id = id;
			} else {
				data.form_id = vcfg.gvSelectForm.val(); // TODO: Update for Joins
			}

			$.post(ajaxurl, data, function (response) {
				if (response !== 'false' && response !== '0') {
					$(".gravityview_sort_field").empty().append(response).prop('disabled', null);
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

		/**
		 * Show metaboxes related to view configuration.
		 * @return {void}
		 */
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
		selectTemplate: function( e ) {
			var vcfg = viewConfiguration;

			e.preventDefault();
			e.stopImmediatePropagation();

			// get selected template
			vcfg.wantedTemplate = $( this );
			var selectedTemplateId = vcfg.wantedTemplate.attr( 'data-templateid' );
			var regexMatch = /(.*?)_(.*?)$/i;
			var currTemplateIdSlug = vcfg.currentTemplateId.replace( regexMatch, '$2' );
			var selectedTemplateIdSlug = selectedTemplateId.replace( regexMatch, '$2' );
			var slugmatch = ( selectedTemplateIdSlug === currTemplateIdSlug );

			// check if template is being changed
			if ( ! vcfg.currentTemplateId || slugmatch || ! vcfg.getConfiguredFields().length ) {
				$( '#gravityview_select_template' ).slideUp( 150 );
				vcfg.selectTemplateContinue( slugmatch );
			} else if ( vcfg.currentTemplateId !== selectedTemplateId ) {
				// warn if fields are configured
				if ( vcfg.getConfiguredFields().length ) {
					vcfg.showDialog( '#gravityview_switch_template_dialog' );
				} else {
					vcfg.toggleViewTypeMetabox();
					vcfg.selectTemplateContinue( slugmatch );
				}
			} else {
				// revert back to how things before before clicking "use a form preset"
				vcfg.toggleViewTypeMetabox();
				vcfg.showViewConfig();
			}
		},

		selectTemplateContinue: function ( slugmatch ) {

			var vcfg = viewConfiguration, selectedTemplateId = vcfg.wantedTemplate.attr( "data-templateid" );

			// update template name
			$( "#gravityview_directory_template" ).val( selectedTemplateId ).trigger('change');

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

			vcfg.currentTemplateId = selectedTemplateId;
			vcfg.hasUnsavedChanges = true;
		},

		/**
		 * When clicking the hover overlay, select the template by clicking the #gv_select_template button
		 * @param  {jQueryEvent}    e     jQuery event object
		 */
		selectTemplateHover: function ( e ) {
			var vcfg = viewConfiguration;
			var $link = $(e.target);
			var $parent = $link.parents('.gv-view-types-module');

			// If we're internally linking
			if ($link.is('[rel=internal]') && !$link.hasClass('gv-layout-activate')) {
				return true;
			}

			e.preventDefault();
			e.stopImmediatePropagation();

			// Activate layout if it's already installed
			if ($link.hasClass('gv-layout-activate')) {
				if (vcfg.performingAjaxAction) {
					return;
				}

				var activate = function () {
					var defer = $.Deferred();

					$link.addClass('disabled');
					vcfg.performingAjaxAction = true;
					$('.gv-view-template-notice').hide();

					$.post(ajaxurl, {
						'action': 'gravityview_admin_installer_activate',
						'data': {path: $link.attr('data-template-path')}
					}, function (response) {
						if (!response.success) {
							return defer.reject(response.data.error);
						}

						$parent.find('.gv-view-types-hover > div:eq(0)').hide();
						$parent.find('.gv-view-types-hover > div:eq(1)').removeClass('hidden');
						$parent.removeClass('gv-view-template-placeholder');
						$parent.find('.gv-view-types-hover > div:eq(1) .gv_select_template').trigger('click');

						defer.resolve();
					}).fail(function () {
						defer.reject(gvAdminInstaller.activateErrorLabel);
					});

					return defer.promise();
				};

				$.when(activate())
					.always(function () {
						vcfg.performingAjaxAction = false;
						$link.removeClass('disabled');
					})
					.fail(function (error) {
						$('.gv-view-template-notice').show().find('p').text(error);

						document.querySelector('.gv-view-template-notice').scrollIntoView({
							behavior: 'smooth'
						});
					});

				return;
			}

			$(this).find('.gv_select_template').trigger('click');
		},

		openExternalLinks: function () {

			if ( !! window.Beacon && ( $( this ).is( '[data-beacon-article]' ) || $( this ).is( '[data-beacon-article-modal]' ) || $( this ).is( '[data-beacon-article-sidebar]' ) || $( this ).is( '[data-beacon-article-inline]' ) ) ) {
				return false;
			}

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
			var parent = $( e.currentTarget ).parents( ".gv-view-types-module" );
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
					var content = JSON.parse( response );
					$( '#directory-header-widgets' ).html( content.header );
					$( '#directory-footer-widgets' ).html( content.footer );
					$( '#directory-active-fields' ).append( content.directory );
					$( '#single-active-fields' ).append( content.single );
					vcfg.showViewConfig();
					vcfg.waiting('stop');

					/**
					 * Triggers after the AJAX is loaded for the zone
					 * @since 2.10
					 * @param {object} JSON response with `header` `footer` (widgets) `directory` and `single` (contexts) properties
					 */
					$('body').trigger( 'gravityview/view-config-updated', content );
				}
			} );

			vcfg.hasUnsavedChanges = true;
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
		remove_tooltips: function ( el ) {
			if ( $( el || '.gv-add-field' ).is( ':ui-tooltip' ) ) {
				$( '.gv-add-field' ).tooltip( 'destroy' ).off( 'click' );
			}
		},

		init_tooltips: function (el) {

			$( el || ".gv-add-field" ).tooltip( {
				show:    150,
				hide:    200,
				content: function () {
					// Is the field picker in single or directory mode?
					//	var context = ( $(this).parents('#single-view').length ) ? 'single' : 'directory';
					var context = $( this ).attr( 'data-context' );
					var formId = $( this ).attr( 'data-formid' ) || $( '#gravityview_form_id' ).val();
					var templateId = $( '#gravityview_directory_template' ).val();

					switch ( $( this ).attr( 'data-objecttype' ) ) {
						case 'field':
							// If in Single context, show fields available in single
							// If it Directory, same for directory
							return $( '#' + context + '-available-fields-' + ( formId || templateId ) ).html();
						case 'widget':
							return $( "#directory-available-widgets" ).html();
					}
				},
				close: function () {
					$( this ).attr( 'data-tooltip', null );
				},
				open: function( event, tooltip ) {

					$( this )
						.attr( 'data-tooltip', 'active' )
						.attr( 'data-tooltip-id', $( this ).attr( 'aria-describedby' ) );

					$focus_item = $( 'input[type=search]', tooltip.tooltip );

					// Widgets don't have a search field; select the first "Add Widget" button instead
					if ( ! $focus_item.length) {
						$focus_item = $( tooltip.tooltip ).find( '.close' ).first();
					}

					var activate_layout = 'list';

					// If layout is coded in HTML, use it.
					if ( $( tooltip ).find('.gv-items-picker-container[data-layout]').length ) {
						activate_layout = $( tooltip ).find( '.gv-items-picker-container[data-layout]' ).attr( 'data-layout' );
					} else {

						// Otherwise, check for cookies
						layout_cookie = $.cookie( 'gv-items-picker-layout' );

						if ( viewConfiguration.getCookieVal( layout_cookie ) ) {
							activate_layout = layout_cookie;
						}
					}

					viewConfiguration.setTooltipLayout( activate_layout );

					$focus_item.trigger('focus');
				},
				closeOnEscape: true,
				disabled: true, // Don't open on hover
				position: {
					my: "center bottom",
					at: "center top-12"
				},
				tooltipClass: 'gravityview-item-picker-tooltip top'
			} )
				// add title attribute so the tooltip can continue to work (jquery ui bug?)
				.attr( "title", "" ).on( 'mouseout focusout', function ( e ) {
				e.stopImmediatePropagation();
			} )
				.on( 'click', function ( e ) {
					// add title attribute so the tooltip can continue to work (jquery ui bug?)
					$( this ).attr( "title", "" );

					e.preventDefault();
					//e.stopImmediatePropagation();

					$( this ).tooltip( "open" );

				} );

		},

		/**
		 * Filters visible fields in the field picker tooltip when the value of the field filter search input changes (or is cleared)
		 *
		 * {@since 2.0.11}
		 *
		 * {@returns void}
		 */
		setupFieldFilters: function( e ) {

			var input = $( this ).val().trim(),
				$tooltip = $( this ).parents( '.ui-tooltip-content' ),
				$resultsNotFound = $tooltip.find( '.gv-no-results' );

			// Allow closeTooltips to know whether to close the tooltip on escape
			if( 'keydown' === e.type ) {
				$( this ).attr( 'data-has-search', ( input.length > 0 ) ? input.length : null );
				return; // Only process the filtering on keyup
			}

			$tooltip.find( '.gv-fields' ).show().filter( function () {

				var match_title = $( this ).find( '.gv-field-label' ).attr( 'data-original-title' ).match( new RegExp( input, 'i' ) );
				var match_id    = $( this ).attr( 'data-fieldid' ).match( new RegExp( input, 'i' ) );
				var match_parent = $( this ).attr( 'data-parent-label' ) ? $( this ).attr( 'data-parent-label' ).match( new RegExp( input, 'i' ) ) : false;

				return ! match_title && ! match_id && ! match_parent;
			} ).hide();

			if ( ! $tooltip.find( '.gv-fields:visible' ).length ) {
				$resultsNotFound.show();
			} else {
				$resultsNotFound.hide();
			}
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
		 * Get fields configured in each context
		 *
		 * @return array
		 */
		getConfiguredFields: function () {
			return $( '#directory-active-fields, #single-active-fields, #edit-active-fields' ).find( '.gv-fields' );
		},

		/**
		 * Fetch the Available Fields for a given Form ID or Preset Template ID
		 * @param  {null|string}    preset
		 * @param  {string}    templateid      The "slug" of the View template
		 * @return void
		 */
		getAvailableFields: function( preset, templateid ) {

			var vcfg = viewConfiguration;

			vcfg.toggleDropMessage();

			vcfg.getConfiguredFields().remove();

			var data = {
				action: 'gv_available_fields',
				nonce: gvGlobals.nonce,
			};

			if ( preset !== undefined && 'preset' === preset ) {
				data.form_preset_ids = [ templateid ];
			} else {
				/**
				 * TODO: Update to support multiple fields in Joins
				 * @see GravityView_Ajax::gv_available_fields()
				 * */
				data.form_preset_ids = [ vcfg.gvSelectForm.val() ];
			}

			// Do not fetch fields if we already have them for the given form or template
			if ( $( '#directory-available-fields-' + data.form_preset_ids[ 0 ] ).length ) {
				return;
			}

			$.ajax( {
				type: 'post',
				url: ajaxurl,
				data: data,
				success: function( response ) {
					if ( ! response.success && ! response.data ) {
						return;
					}

					$.each( response.data, function( context,markup ) {
						$( '#' + context + '-fields' ).append( markup );
					} );
				},
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

			clicked.siblings( '.gv-fields' ).filter( function () {

				var field_id = $( this ).data( 'fieldid' );

				// Is the (number +)Field ID the same as the integer (not an input)?
				// If so, form field. If not, entry meta or custom field type.
				return ( +field_id === parseInt( field_id, 10 ) );

			} ).each( function () {
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
			var addButton = $( '.gv-add-field[data-tooltip-id="' + tooltipId + '"]' );

			var data = {
				action: 'gv_field_options',
				template: templateId,
				area: addButton.attr( 'data-areaid' ),
				context: addButton.attr( 'data-context' ),
				field_id: newField.attr( 'data-fieldid' ),
				field_label: newField.find( '.gv-field-label' ).attr( 'data-original-title' ),
				field_type: addButton.attr( 'data-objecttype' ),
				input_type: newField.attr( 'data-inputtype' ),
				form_id: parseInt($(clicked).attr( 'data-formid' ), 10) || vcfg.currentFormId,
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
					$( '.gv-field-settings', newField ).removeClass( 'hide-if-js' );
				}

				// append the new field to the active drop
				$( '[data-tooltip-id="' + areaId + '"]' ).parents( '.gv-droppable-area' ).find( '.active-drop' ).append( newField ).end().attr( 'data-tooltip-id', '' );

				$('body').trigger( 'gravityview/field-added', newField );

				// Show the new field
				newField.fadeIn( 100, function () {
					vcfg.refresh_merge_tags();
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
				vcfg.hasUnsavedChanges = true;

			} );

		},

		/**
		 * Re-initialize Merge Tags
		 *
		 * @since 1.22.1
		 */
		refresh_merge_tags: function() {

			// Remove existing merge tags, since otherwise GF will add another
			$( '.all-merge-tags' ).remove();

			$merge_tag_supported = $('.merge-tag-support');

			// Only init merge tags if the View has been saved and the form hasn't been changed.
			if ( 'undefined' !== typeof( form ) && $( 'body' ).not( '.gv-form-changed' ) && $merge_tag_supported.length >= 0 ) {

				if ( window.gfMergeTags ) {

					if ( gfMergeTags.hasOwnProperty('destroy') ) {

						// 2.3 re-init
						$merge_tag_supported.each( function () {
							new gfMergeTagsObj( form, $( this ) );
						});

					} else {

						// Re-init merge tag dropdowns, pre-2.3
						window.gfMergeTags = new gfMergeTagsObj( form );

					}
				}
			}
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
				start: function( event, ui ) {
					$( '#directory-fields, #single-fields' ).find( ".active-drop-container-widget" ).addClass('is-receivable');
				},
				stop: function( event, ui ) {
					$( '#directory-fields, #single-fields' ).find( ".active-drop-container-widget" ).removeClass('is-receivable');
				},
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
				start: function( event, ui ) {
					$( '#directory-fields, #single-fields, #edit-fields' ).find( ".active-drop-container-field" ).addClass('is-receivable');
				},
				stop: function( event, ui ) {
					$( '#directory-fields, #single-fields, #edit-fields' ).find( ".active-drop-container-field" ).removeClass('is-receivable');
				},
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

			vcfg.hasUnsavedChanges = true;

			// Nice little easter egg: when holding down control, get rid of all fields in the zone at once.
			if ( e.altKey && $( area ).find( '.gv-fields' ).length > 1 ) {
				vcfg.removeAllFields( e, area );

				return;
			}

			$( e.currentTarget ).parents( '.gv-fields' ).fadeOut( 'fast', function () {

				$( this ).remove();

				$('body').trigger( 'gravityview/field-removed', $( this ) );

				vcfg.toggleDropMessage();
			} );

		},

		/**
		 * Remove all fields from an area
		 *
		 * @param e
		 * @param area If passed, the jQuery DOM object where .gv-fields are that should be removed.
		 */
		removeAllFields: function ( e, area ) {

			e.preventDefault();

			// Show a confirm dialog
			var remove_all = window.confirm( gvGlobals.remove_all_fields );

			// If yes, remove all, otherwise don't do anything
			if ( ! remove_all ) {
				return;
			}

			area = area || null;

			// If the area name hasn;
			if ( ! area ) {
				area_id = $( e.originalEvent.target ).data( 'areaid' );
				area = $( e.originalEvent.target ).parents( 'div[data-areaid="' + area_id + '"]' )[ 0 ];
			}

			$( area ).find( '.gv-fields' ).remove();

			$('body').trigger( 'gravityview/all-fields-removed' );

			viewConfiguration.toggleDropMessage();
		},

		toggleRemoveAllFields: function ( e, item ) {

			has_fields = false;

			$( ".active-drop:visible" ).each( function ( index, item ) {
				has_fields = ( $( this ).find( '.gv-fields' ).length > 1 );
				$( '.clear-all-fields', $( item ).parents('.gv-droppable-area') ).toggle( has_fields );
			});
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

			$( '.gv-field-settings', parent ).addClass( 'active' );

			vcfg.updateVisibilitySettings( e, true );

			// Toggle checkbox when changing field visibility
			$( 'body' ).on( 'change', '.gv-fields input:checkbox', vcfg.updateVisibilitySettings );

			var buttons = [
				{
					text: gvGlobals.label_close,
					class: 'button button-link',
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

			$( ".gv-setting-list", $parent ).trigger( 'change' );

			$( 'input:checkbox', $parent ).attr( 'disabled', null );

			vcfg.hasUnsavedChanges = true;
		},

		/**
		 * Show/Hide Visibility of an input's container list item based on the value of a checkbox
		 *
		 * @param  {jQuery} $checkbox The checkbox to use when determining show/hide. Checked: show; unchecked: hide
		 * @param  {jQuery} $toggled  The field whose container to show/hide
		 * @param  {boolean} first_run Is this the first run (on load)? If so, show/hide immediately
		 * @param  {boolean} inverse   Should the logic be flipped (unchecked = show)?
		 * @return {void}
		 */
		toggleVisibility: function ( $checkbox, $toggled, first_run, inverse ) {

			var speed = 0;

			var checked = $checkbox.is( ':checked' );

			checked = inverse ? ! checked : checked;

			if ( checked ) {
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
				vcfg.hasUnsavedChanges = false;

				// Serialize the inputs so that `max_input_vars`
				return vcfg.serializeForm( e );
			}

			return false;

		},

		/**
		 * Serialize all GV field data and submit it all as one field value
		 *
		 * To fix issues where there are too many array items, causing PHP max_input_vars threshold to be met
		 *
		 * @param  {jQueryEvent} e jQuery event object
		 *
		 * @return {boolean}
		 */
		serializeForm: function ( e ) {

			var $post = $('#post');
			var serialized_data, $fields;

			if ( $post.data( 'gv-valid' ) ) {
				return true;
			}

			e.stopImmediatePropagation();

			$post.data( 'gv-valid', false );

			if ( $post.data( 'gv-serialized' ) ) {
				// Guard against double seralization/remove attempts
				serialized_data = $post.data( 'gv-serialized' );
			} else {
				// Get all the fields where the `name` attribute start with `fields`
				$fields = $post.find( ':input[name^=fields]' );

				// Serialize the data
				serialized_data = $fields.serialize();

				// Remove the fields from the $_POSTed data
				$fields.remove();

				$post.data( 'gv-serialized', serialized_data );
			}

			// Add a field to the form that contains all the data.
			$post.find( ':input[name=gv_fields]' ).remove();
			$post.append( $( '<input/>', {
				'name': 'gv_fields',
				'value': serialized_data,
				'type': 'hidden'
			} ) );

			// make sure the "slow" browsers did append all the serialized data to the form
			setTimeout( function () {

				$post.data( 'gv-valid', true );

				if ( 'click' === e.type ) {
					$( e.target ).trigger('click');
				} else {
					$post.trigger('submit');
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
							$target.trigger( 'click' );
						} else {
							$('#post').trigger('submit');
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
				.on('change', viewGeneralSettings.updateSettingsDisplay )
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

	jQuery(function ( $ ) {

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

		if ( false === viewConfiguration.getCookieVal( activate_tab ) ) {
			activate_tab = 0;
		}

		if ( location.hash && $( location.hash ).length ) {
			activate_tab = $( location.hash ).index() - 1;
		}

		// View Configuration - Tabs (and persist after refresh)
		$( "#gv-view-configuration-tabs" ).tabs( {
			active: activate_tab,
			hide: false,
			show: false,
			activate: function ( event, ui ) {
				// When the tab is activated, set a new cookie
				$.cookie( cookie_key, ui.newTab.index(), { path: gvGlobals.cookiepath } );

				$( 'body' ).trigger( 'gravityview/tabs-ready' );
			}
		} );

		$( 'body' ).trigger( 'gravityview/loaded' );
	} );

	// Expose globally methods to initialize/destroy tooltips and to display dialog window
	window.gvAdminActions = {
		initTooltips: viewConfiguration.init_tooltips,
		removeTooltips: viewConfiguration.remove_tooltips,
		showDialog: viewConfiguration.showDialog,
	};

	// Enable inserting GF merge tags into WP's CodeMirror
	$( window ).load( function() {
		var _sendToEditor = window.send_to_editor;

		window.send_to_editor = function( val ) {
			var $el = $( '#' + window.wpActiveEditor );

			if ( ! $el.hasClass( 'codemirror' ) ) {
				return _sendToEditor( val );
			}

			var codeMirror = $el.next( '.CodeMirror' )[ 0 ].CodeMirror;
			var cursorPosition = codeMirror.getCursor();
			codeMirror.replaceRange( val, window.wp.CodeMirror.Pos( cursorPosition.line, cursorPosition.ch ) );
		};
	} );

}(jQuery));
