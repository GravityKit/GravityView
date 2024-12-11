/**
 * New Search widget UI at Add New / Edit Views screen
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.2
 */

( function ( $ ) {

	var gvSearchWidget = {

		// holds the settings div class (depending on the context)
		wrapClass: null,

		// holds the current widget settings DOM object
		widgetTarget: $( '.gv-dialog-options' ),

		searchModal: null,

		selectFields: null,

		wp_widget_id: 'gravityview_search',

		// The default search should be a single "Search All" field
		default_search_fields: '[{"field":"search_all","input":"input_text"}]',

		init: function ( wrapClass ) {

			gvSearchWidget.wrapClass = wrapClass;

			var wp_widget_id = gvSearchWidget.wp_widget_id;

			$( document.body )

				// [View] hook on all the open settings buttons for search_bar widget
				.on( 'dialogopen', '[data-fieldid="search_bar"] .' + wrapClass, gvSearchWidget.openDialog )
				.on( 'dialogclose dialogdestroy', '[data-fieldid="search_bar"] .' + wrapClass, gvSearchWidget.closeDialog )

				// [WP widget] When opening the WP widget settings, trigger the search fields table
				.bind( 'click.widgets-toggle', gvSearchWidget.openWidget )

				// [View, WP widget] hook to add/remove rows
				.on( 'click', '.' + wrapClass + ' .gv-add-search-field', gvSearchWidget.addField )

				.on( 'click', '.' + wrapClass + ' .gv-remove-search-field', gvSearchWidget.removeField )

				// [View, WP widget] hook to update row input types
				.on( 'change', '.' + wrapClass + ' select.gv-search-fields', gvSearchWidget.updateRow )
				.on( 'change', '.' + wrapClass + ' select.gv-search-inputs', gvSearchWidget.toggleSearchMode )
				.on( 'keyup', '.' + wrapClass + ' input.gv-search-labels', gvSearchWidget.updateWidgetConfig )

				// [View, WP widget] add alt class to table when sorting
				.on( 'sortcreate sortupdate sort', '.' + wrapClass + ' table', gvSearchWidget.zebraStripe )

				// [WP widget] hook on update widget config to save the fields into the hidden input field
				.on( 'click', '.widget[id*=\'' + wp_widget_id + '\'] input.widget-control-save', gvSearchWidget.saveWidget )

				// [View] hook on assigned form/template change to clear cache
				.on( 'change', '#gravityview_form_id', gvSearchWidget.clearViewSearchData )

				// [WP widget] hook on assigned view id change to clear cache
				.on( 'change', '#gravityview_view_id', gvSearchWidget.clearWidgetSearchData )

				.on ( 'click', '#search-fields .gv-field-settings', gvSearchWidget.openFieldSettings )

				.on( 'click', '#search-fields > .gv-dialog-options [data-close-settings]', gvSearchWidget.closeFieldSettings )

				.on( 'click', '#search-view', gvSearchWidget.closeFieldSettingsOutside )
			;

			// Refresh widget searchable settings after saving or adding the widget
			// Bind to document because WP triggers document, not body
			$( document )
				.on( 'widget-added widget-updated', gvSearchWidget.refreshWidget )
				// [View] If submitting a View by hitting enter inside a Widget, make sure it saves
				.on( 'submit', 'form#post', gvSearchWidget.updateWidgetConfig );
		},

		/**
		 * [Specific for Search WP Widget]
		 * Calculate the widget target and reset the view fields and the DOM target to insert the settings table
		 * @param  {jQuery} obj event
		 */
		resetWidgetTarget: function ( obj ) {
			gvSearchWidget.widgetTarget = obj.closest( 'div.widget' ).find( 'div.' + gvSearchWidget.wrapClass );
			// reset fields to the exist appended to the table (if none, it gets undefined)
			gvSearchWidget.selectFields = null;
		},

		/**
		 * [Specific for Search WP Widget]
		 * Reset Widget target and removes the settings table
		 * @param  {jQuery} obj event
		 */
		resetWidgetData: function ( obj ) {
			gvSearchWidget.resetWidgetTarget( obj );
			$( 'table', gvSearchWidget.widgetTarget ).remove();
		},

		/**
		 * [Specific for Search WP Widget]
		 * Capture the widget slidedown and call to render the widget settings content
		 * @param  {jQuery} e event
		 */
		openWidget: function ( e ) {
			var target = $( e.target ),
				widget, widgetId;

			if ( target.parents( '.widget-top' ).length && !target.parents( '#available-widgets' ).length ) {
				e.preventDefault();
				widget = $( e.target ).closest( 'div.widget' );
				widgetId = widget.attr( 'id' );

				if ( !widget.hasClass( 'open' ) && widgetId.indexOf( gvSearchWidget.wp_widget_id ) > 0 ) {
					gvSearchWidget.resetWidgetData( target );
					gvSearchWidget.renderUI( widget );
				}
			}
		},

		/**
		 * [Specific for Search WP Widget]
		 * Refreshes the Widget table settings after saving
		 * @param  {jQuery} e event
		 * @param  {jQuery} widget jQuery widget DOM
		 */
		refreshWidget: function ( e, widget ) {

			if ( $( widget ).hasClass( 'open' ) ) {
				gvSearchWidget.widgetTarget = $( widget ).find( 'div.' + gvSearchWidget.wrapClass );
				gvSearchWidget.renderUI( widget );
			}

		},

		/**
		 * [Specific for View Search Widget]
		 * Capture the widget dialog and call to render the widget settings content
		 * @param  {jQuery} e event
		 */
		openDialog: function ( e ) {
			e.preventDefault();

			// Remove ui-front to add field dialogs to <body>, fixing their appearance.
			$( this ).closest( '[role="dialog"]' ).removeClass( 'ui-front' );

			gvSearchWidget.widgetTarget = $( this );
			if ( !gvSearchWidget.searchModal ) {
				gvSearchWidget.searchModal = $( '#search-view' );
			}

			// Add to the end of the stack so the content is in the modal.
			setTimeout( () => {
				const $sortables = gvSearchWidget.searchModal.find( '.active-drop-search' );

				// Sortable needs to be reinitialized when the modal opens.
				$sortables.each( ( _, el ) => {
					const sortable = $( el ).sortable( 'instance' );
					sortable && sortable.destroy(); // Remove sorting if it is active.
				} );

				gvAdminActions.initDroppables( gvSearchWidget.searchModal ); // Add sorting (back).
			} );

			if ( gvSearchWidget.searchModal ) {
				gvSearchWidget.searchModal.attr( 'aria-hidden', 'false' );
				gvSearchWidget.searchModal.appendTo( $( this ).find( '[data-search-fields]' ) );
			}
		},

		/**
		 * [Specific for View Search Widget]
		 * Capture the widget dialog and call to render the widget settings content
		 * @param  {jQuery} e event
		 */
		closeDialog: function ( e ) {
			e.preventDefault();

			// Close any open field settings first.
			gvSearchWidget.closeFieldSettings(e);

			gvSearchWidget.widgetTarget = $( this );
			if ( gvSearchWidget.searchModal ) {
				gvSearchWidget.searchModal.attr( 'aria-hidden', 'true' );
				gvSearchWidget.searchModal.appendTo( $( '#gv-view-configuration-tabs' ) );
			}
		},

		/** Table manipulation */

		/**
		 * Add a search field to the table
		 * @param  {jQuery} e event
		 */
		addField: function ( e ) {
			e.preventDefault();

			// make sure the select fields data is fetched from the target table (only for WP Widget!)
			if ( 'gv-widget-search-fields' === gvSearchWidget.wrapClass ) {
				gvSearchWidget.resetWidgetTarget( $( this ) );
			}

			var table = $( this ).parents( 'table' ),
				row = $( this ).parents( 'tr' );

			// if no fields message exists, remove it
			if ( row.hasClass( 'no-search-fields' ) ) {
				row.remove();
				row = null;
			}

			gvSearchWidget.addRow( table, row, null );

			return false;
		},

		/**
		 * Remove a search field to the table
		 * @param  {jQuery} e event
		 */
		removeField: function ( e ) {
			e.preventDefault();
			var table = $( this ).parents( 'table' );

			//remove line
			$( this ).parents( 'tr' ).fadeTo( 100, 0.4, function () {

				$( this ).remove();

				var table_row_count = $( 'tr.gv-search-field-row', table ).length;

				//check if is there any
				if ( table_row_count < 1 && $( 'tr.no-search-fields', table ).length < 1 ) {

					gvSearchWidget.addEmptyMsg( table );

				}

				gvSearchWidget.updateAvailableFields();

				gvSearchWidget.updateWidgetConfig();

				gvSearchWidget.triggerWidgetChange( table );

				gvSearchWidget.styleRow( table );
			} );

			return false;

		},

		/**
		 * Render search fields table (includes a pre-loader animation)
		 * @param  {jQuery} parent The dialog div object
		 */
		renderUI: function ( parent ) {
			var fields = $( '.gv-search-fields-value', parent ).val(),
				viewId = $( '#gravityview_view_id', parent ).val();

			gvSearchWidget.widgetTarget = $( parent ).find( 'div.' + gvSearchWidget.wrapClass );

			if ( viewId === '' ) {
				return;
			}

			$gvloading = $( '#gv-loading' );

			// Is this dialog already rendered before & not loading fields again
			if ( $( 'table', gvSearchWidget.widgetTarget ).length && $gvloading.length < 1 ) {
				return;
			}

			if ( $gvloading && $gvloading.attr( 'gv-error' ) ) {
				return;
			}

			// get fields from server
			if ( gvSearchWidget.selectFields === null || 0 === $gvloading.length ) {
				gvSearchWidget.widgetTarget.append( '<p id="gv-loading"><span class="spinner"></span>' + gvGlobals.loading_text + '</p>' );
				gvSearchWidget.getSelectFields( parent );
				return;
			}

			//add table and header
			table = gvSearchWidget.addTable();

			if ( fields && fields.length === 0 ) {
				gvSearchWidget.addRow( table, null, null );
			} else {
				gvSearchWidget.populateRows( table, fields );
			}

			if ( gvSearchWidget.widgetTarget.is( '.gv-widget-search-fields' ) ) {
				// WP Widget
				gvSearchWidget.widgetTarget.append( table );
			} else {
				// GV widget
				gvSearchWidget.widgetTarget.find( '.gv-setting-container-search_fields' ).after( table );
			}

			gvSearchWidget.toggleSearchMode();

			gvSearchWidget.widgetTarget.find( 'table tbody' ).sortable( {
				start: function ( event, ui ) {
					$( ui.item ).removeClass( 'alt' );
					$( ui.item ).find( '.cell-add-remove' ).toggle();
				},
				stop: function ( event, ui ) {
					$( ui.item ).find( '.cell-add-remove' ).toggle();

					gvSearchWidget.updateWidgetConfig( ui.item );

					gvSearchWidget.triggerWidgetChange( ui.item );
				}
			} );

			gvSearchWidget.updateAvailableFields();

			gvSearchWidget.updateWidgetConfig();

			$gvloading.remove();
		},

		/**
		 * Triggers change on the widget thus enabling the save/update buttons
		 * @param  {{jQuery DOM object}} el
		 */
		triggerWidgetChange: function ( el ) {
			$( el ).parents( '.widget-content' ).find( 'p input' ).trigger( 'change' );
		},

		/**
		 * Add alt classes on table sort
		 * @param  {jQuery} e_or_object
		 * @return {void}
		 */
		zebraStripe: function () {

			// Zebra stripe the rows
			$( gvSearchWidget.widgetTarget )
				.find( 'tr.gv-search-field-row' )
				.removeClass( 'alt' )
				.filter( ':even' ).addClass( 'alt' );

		},

		/**
		 * Given a JSON string convert it to the search fields table
		 * @param  {{jQuery DOM object}} table  The table DOM object
		 * @param  {string} fields JSON fields configuration
		 */
		populateRows: function ( table, fields ) {
			var rows = JSON.parse( fields ),
				pos = null;

			if ( !rows || rows.length === 0 ) {
				gvSearchWidget.addEmptyMsg( table );
				return;
			}

			$.each( rows, function ( i, values ) {
				gvSearchWidget.addRow( table, pos, values );
				pos = table.find( 'tbody tr:last' );
			} );
		},

		/**
		 * Init the search fields table
		 */
		addTable: function () {
			return $( '<table class="form-table widefat">' +
				'<thead>' +
				'<tr>' +
				'<th class="cell-sort">&nbsp;</th>' +
				'<th class="cell-search-fields" scope="col">' + gvSearchVar.label_searchfield + '</th>' +
				'<th class="cell-input-label" scope="col">' + gvSearchVar.label_label + '</th>' +
				'<th class="cell-input-types" scope="col">' + gvSearchVar.label_inputtype + '</th>' +
				'<th class="cell-add-remove">&nbsp;</th>' +
				'</tr>' +
				'</thead>' +
				'<tbody></tbody>' +
				'</table>' );
		},

		/**
		 * Add a "no-fields" message
		 * @param  {{jQuery DOM object}} table  The table DOM object
		 */
		addEmptyMsg: function ( table ) {
			$( table ).append( '<tr class="no-search-fields"><td colspan="5">' + gvSearchVar.label_nofields + '&nbsp; <button class="button button-primary button-large gv-add-search-field">' + gvSearchVar.label_addfield + '</button></td></tr>' );
		},

		/**
		 * Add row to the table object
		 * @param {jQuery} table  The table DOM object
		 * @param {jQuery}  row   Table row object after which the new row will be added
		 * @param {object} curr  Configured values for the row ( field and input )
		 */
		addRow: function ( table, row, curr ) {

			var rowString = $( '<tr class="gv-search-field-row new-row hide-if-js" />' )
				.append( '<td class="cell-sort"><span class="icon gv-icon-caret-up-down" style="display:none;" aria-label="' + gvGlobals.label_reorder_search_fields + '" /></td>' )
				.append( '<td class="cell-search-fields">' + gvSearchWidget.getSelectFields() + '</td>' )
				.append( '<td class="cell-input-label"><input type="text" class="widefat gv-search-labels" /></td>' )
				.append( '<td class="cell-input-types"><select class="gv-search-inputs" /></td>' )
				.append( '<td class="cell-add-remove"><button class="gv-add-search-field" aria-label="' + gvGlobals.label_add_search_field + '"><span class="dashicons dashicons-plus-alt"></span></button><button class="gv-remove-search-field" aria-label="' + gvGlobals.label_remove_search_field + '"><span class="dashicons dashicons-dismiss"></span></button></td>' );

			// add row
			if ( row !== null && row.length ) {
				$( row, table ).after( rowString );
			} else {
				$( 'tbody', table ).append( rowString );
			}

			table.find( 'tr.new-row' ).each( function () {
				$( this ).removeClass( 'new-row' );

				// Set saved search field value
				if ( curr !== null ) {
					$( this ).find( 'select.gv-search-fields' ).val( curr.field );
				}

				// Set search label
				if ( curr !== null ) {
					$( this ).find( '.cell-input-label input' ).val( curr.label );
				}

				// update the available input types
				gvSearchWidget.updateSelectInput( $( this ) );

				gvSearchWidget.updatePlaceholder( $( this ) );

				// Set saved input type value
				// !! Do not try to optimize this line. This needs to come after 'gvSearchWidget.updateSelectInput()'
				if ( curr !== null ) {
					$( this ).find( 'select.gv-search-inputs' ).val( curr.input );
				}

				$( this ).find( 'select.gv-search-fields, input.gv-search-labels, select.gv-search-inputs' ).on( 'change keyup', gvSearchWidget.updateWidgetConfig );

				gvSearchWidget.updateWidgetConfig();

				gvSearchWidget.triggerWidgetChange( this );

				// Fade in
				$( this ).show().removeClass( 'hide-if-js' );
			} );

			gvSearchWidget.styleRow( table );
		},

		/**
		 * Update the label text input placeholder value, so users know what the default label is
		 * @since 1.14
		 * @param $row
		 */
		updatePlaceholder: function ( $row ) {

			var $label_input = $row.find( '.cell-input-label input' );
			var $placeholder_option = $row.find( 'select.gv-search-fields option' ).filter( ':selected' );
			var placeholder_text = $placeholder_option.attr( 'data-placeholder' ) ? $placeholder_option.attr( 'data-placeholder' ) : $placeholder_option.text();

			$label_input.attr( 'placeholder', placeholder_text );
		},

		/**
		 * Show or hide the Search Mode settings based on whether there's a single search field. If there's only one, then "all" and "any" are the same.
		 * @since 1.14
		 * @return {void}
		 */
		toggleSearchMode: function () {

			var table_row_count = $( 'tbody tr', gvSearchWidget.widgetTarget ).length,
				$search_mode = $( 'input[name*="search_mode"]', gvSearchWidget.widgetTarget ),
				$search_mode_container = $search_mode.parents( '.gv-setting-container' ),
				has_date_range = ( $( 'option:selected[value="date_range"]', gvSearchWidget.widgetTarget ).length > 0 );

			$search_mode_container.find( 'input' ).each( function () {
				if ( has_date_range ) {
					$( this ).prop( 'disabled', true );
					$( this ).prop( 'checked', $( this ).val() === 'all' );
				} else {
					$( this ).prop( 'disabled', false );
				}
			} );

			if ( table_row_count > 1 ) {
				$search_mode_container.show();
			} else {
				$search_mode_container.hide( 100 );
			}

		},

		/**
		 * Style the table rows - remove/add sorting icon, zebra stripe
		 * @param  {object} table Table
		 * @return {[type]}       [description]
		 */
		styleRow: function ( table ) {

			table_row_count = $( 'tbody tr', table ).length;

			var sort_icon = $( '.cell-sort .icon', table );

			gvSearchWidget.toggleSearchMode();

			if ( table_row_count <= 1 ) {
				sort_icon.hide();
				$( this ).parents( 'td' ).addClass( 'no-sort' );
			} else {
				sort_icon.show();
				$( this ).parents( 'td' ).removeClass( 'no-sort' );
			}

			gvSearchWidget.zebraStripe();
		},

		/**
		 * When field is changed, update the search fields selector (disable the ones in use) and the input types for the new field selected
		 * @param  {jQuery} e
		 * @return {[type]} [description]
		 */
		updateRow: function ( e ) {
			var $row = $( this ).parents( 'tr' );
			gvSearchWidget.updateSelectInput( $row );
			gvSearchWidget.updateAvailableFields();
			gvSearchWidget.updatePlaceholder( $row );
			gvSearchWidget.updateWidgetConfig( $row );
		},

		/**
		 * Modify the gvSearchWidget.selectFields input to disable existing search fields, then replace the fields with the generated input.
		 * @return {void}
		 */
		updateAvailableFields: function () {

			// Clear out the disabled options first
			$( 'option', gvSearchWidget.selectFields ).attr( 'disabled', null );

			$( 'tr.gv-search-field-row .gv-search-fields', gvSearchWidget.widgetTarget )

				// Update the selectFields var to disable all existing values
				.each( function () {
					gvSearchWidget.selectFields
						.find( 'option[value="' + $( this ).val() + '"]' )
						.attr( 'disabled', true );
				} )

				// Then once we have the select input finalized, run through again
				// and replace the select inputs with the new one
				.each( function () {

					var select = gvSearchWidget.selectFields.clone();

					// Set the value
					select.val( $( this ).val() );

					// Enable the option with the current value
					select.find( 'option:selected' ).attr( 'disabled', null );

					// Replace the select with the generated one
					$( this ).replaceWith( select );
				} );

		},

		/**
		 * Update the input types for the new field selected
		 * @param  {jQuery} tr table row object
		 * @return {[type]}    [description]
		 */
		updateSelectInput: function ( tr ) {
			var type = tr.find( 'select.gv-search-fields option:selected' ).attr( 'data-inputtypes' );
			var select = tr.find( 'select.gv-search-inputs' );

			var options = gvSearchWidget.getSelectInput( type );

			select.html( options ).trigger( 'change' );

			// If there's only one option, disable ability to change it.
			select.prop( 'disabled', function () {
				return ( $( 'option', $( this ) ).length === 1 );
			} );

		},

		/**
		 * Get the Select DOM object populated with the available search fields
		 * If not already in cache, get it from server using AJAX request
		 * @param  {jQuery} parent The dialog div object
		 */
		getSelectFields: function ( parent ) {

			// check if fields exist on cache
			if ( gvSearchWidget.selectFields !== null ) {

				gvSearchWidget.updateAvailableFields();

				// .html() returns the <option>s, we want the <select>
				return gvSearchWidget.selectFields.prop( 'outerHTML' );
			}

			var fields = gvSearchWidget.widgetTarget.data( 'gvSelectFields' );

			if ( fields !== undefined ) {
				gvSearchWidget.selectFields = $( fields );
				gvSearchWidget.updateAvailableFields();
				if ( $( 'table', gvSearchWidget.widgetTarget ).length ) {
					return gvSearchWidget.selectFields.prop( 'outerHTML' );
				} else {
					gvSearchWidget.renderUI( parent );
					return;
				}

			}

			var ajaxdata = {
				action: 'gv_searchable_fields',
				nonce: gvSearchVar.nonce,
				formid: $( '#gravityview_form_id' ).val(),
				view_id: $( '#gravityview_view_id', parent ).val(),
				template_id: $( '#gravityview_directory_template' ).val()
			};

			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				async: true,
				dataType: 'html',
				data: ajaxdata,
				success: function ( response ) {
					if ( response !== '0' ) {
						gvSearchWidget.selectFields = $( response );
						gvSearchWidget.widgetTarget.data( 'gvSelectFields', response );
						gvSearchWidget.renderUI( parent );
					} else {
						// The nonce is likely invalid. Hide search bar settings and show error.
						$( parent ).find( '.gv-setting-container' ).hide();
						$( '#gv-loading' ).text( gvSearchVar.label_ajaxerror ).attr( 'gv-error', 1 );
					}
				}
			} );
		},

		getSelectInput: function ( type ) {

			var labels = JSON.parse( gvSearchVar.input_labels ),
				types = JSON.parse( gvSearchVar.input_types ),
				options = [];

			// get list of inputs
			var inputs = gvSearchWidget.getValue( types, type );

			if ( inputs === null ) {
				return '';
			}

			// iterate through the requested input types
			$.each( inputs, function ( k, input ) {

				//get label
				var label = gvSearchWidget.getValue( labels, input );

				options.push( '<option value="' + input + '">' + label + '</option>' );
			} );

			return options.join();
		},

		// helper: get value from a js object given a certain key
		getValue: function ( obj, key ) {
			var value = null;
			$.each( obj, function ( k, val ) {
				if ( key === k ) {
					value = val;
					return false;
				}
			} );
			return value;
		},

		/** Save Settings */

		/**
		 * [Specific for View Search Widget]
		 * Update config on widget Save
		 */
		saveWidget: function () {
			gvSearchWidget.resetWidgetTarget( $( this ) );
			gvSearchWidget.updateWidgetConfig();
		},

		/**
		 * Stringify search field data and update other widget values
		 * @param {jQuery} e Event
		 */
		updateWidgetConfig: function ( e ) {
			var widgetTarget;
			var configs = [];

			if ( e ) {
				widgetTarget = e.target ? $( e.target ).parents( '.gv-widget-search-fields, .gv-fields' ) : e.parents( '.gv-widget-search-fields, .gv-fields' );
				// If one of the search fields uses a "date range", the search mode option input fields become disabled.
				// In order to pass the selected search mode option to the server, we need to enable the checked input field.
				$( '.gv-setting-container-search_mode' ).find( 'input:checked:disabled' ).prop( 'disabled', false );
			} else {
				widgetTarget = gvSearchWidget.widgetTarget;
			}

			// Loop through table rows
			widgetTarget.find( 'table tr.gv-search-field-row' ).each( function () {
				var row = {
					'field': $( this ).find( 'select.gv-search-fields' ).val(),
					'input': $( this ).find( 'select.gv-search-inputs' ).val(),
					'label': $( this ).find( 'input.gv-search-labels' ).val()
				};
				configs.push( row );
			} );

			// Save
			$( '.gv-search-fields-value', widgetTarget ).val( JSON.stringify( configs ) );
		},

		/** Reset on View Change */

		/**
		 * [Specific for View Search Widget]
		 * When form changes, clear the select fields cache and remove all the search_bar configs
		 */
		clearViewSearchData: function () {
			gvSearchWidget.selectFields = null;
			$( '.gv-search-fields-value' ).each( function () {
				$( this ).parents( '.' + gvSearchWidget.wrapClass ).find( 'table' ).remove();
				$( this ).val( gvSearchWidget.default_search_fields );
			} );

		},

		/**
		 * [Specific for Search WP Widget]
		 * When View changes clear select fields cache, remove table and refresh the data
		 */
		clearWidgetSearchData: function () {
			gvSearchWidget.resetWidgetData( $( this ) );
			gvSearchWidget.widgetTarget.removeData( 'gvSelectFields' );
			$( '.gv-search-fields-value', gvSearchWidget.widgetTarget ).val( gvSearchWidget.default_search_fields );

			var widget = gvSearchWidget.widgetTarget.closest( 'div.widget' );

			$( '.hide-on-view-change:visible', widget ).slideUp( 100 );

			if ( '' !== $( this ).val() ) {
				gvSearchWidget.renderUI( widget );
			}

		},

		openFieldSettings: function ( e ) {
			gvSearchWidget.closeFieldSettings(e); // Close any open panels.

			const $field = $( this ).closest( '.gv-fields' );
			const $options = $field.find( '.gv-dialog-options' );
			$options.data( 'field', $field ); // Store the originating field.

			$field.addClass('has-options-panel');

			$( '#search-view' )
				.addClass('has-options-panel')
				.append( $options ); // Move options to search view div.
		},

		closeFieldSettings: function ( e ) {
			e.preventDefault();

			$( '#search-view' ).removeClass( 'has-options-panel' );

			const $options = $( '#search-view > .gv-dialog-options' );
			if ( !$options.length ) {
				return;
			}

			const $field = $options.data( 'field' );
			if ( !$field.length ) {
				return;
			}

			$field
				.removeClass( 'has-options-panel' )
				.append( $options ); // Return options to field.

			gvAdminActions.setCustomLabel( $field );
		},

		closeFieldSettingsOutside: function ( e ) {
			if ( e.target.id !== 'search-view' ) {
				return;
			}

			gvSearchWidget.closeFieldSettings(e);
		}
	}; // end

	$( document ).ready( function () {

		var contextClass = $( 'body' ).hasClass( 'widgets-php' ) ? 'gv-widget-search-fields' : 'gv-dialog-options';

		gvSearchWidget.init( contextClass );

	} );

}( jQuery ) );
