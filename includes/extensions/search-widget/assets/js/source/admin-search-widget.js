/**
 * New Search widget UI at Add New / Edit Views screen
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.2
 */


(function( $ ) {



	var gvSearchWidget = {

		dialog: null,

		selectFields : null,

		init: function() {
			var gvsw = gvSearchWidget;

			$('body')
				// hook on all the open settings buttons for search_bar widget
				.on( 'dialogopen', '[data-fieldid="search_bar"] .gv-dialog-options', gvsw.openDialog )

				// hook to add/remove rows
				.on( 'click', ".gv-dialog-options a[href='#addSearchField']", gvsw.addField )

				.on( 'click', ".gv-dialog-options a[href='#removeSearchField']", gvsw.removeField )

				// hook to update row input types
				.on( 'change', ".gv-dialog-options select.gv-search-fields", gvsw.updateRow )

				// add alt class to table when sorting
				.on('sortcreate sortupdate sort', '.gv-dialog-options table', gvsw.zebraStripe )

				// hook on dialog close to update widget config
				.on( 'dialogbeforeclose', '[data-fieldid="search_bar"] .gv-dialog-options', gvsw.updateOnClose );

			// hook on assigned form/template change to clear cache
			$('#gravityview_form_id, #gravityview_directory_template').change( gvsw.clearCache );

		},

		/**
		 * Capture the widget dialog and call to render the widget settings content
		 * @param  object e event
		 */
		openDialog: function(e) {
			e.preventDefault();
			gvSearchWidget.dialog = $(this);
			gvSearchWidget.renderUI( $(this).parents('.gv-fields') );
		},

		/**
		 * Add a search field to the table
		 * @param  object e event
		 */
		addField: function(e) {
			e.preventDefault();

			var table = $(this).parents( 'table' ),
				row = $(this).parents( 'tr' );

			// if no fields message exists, remove it
			if( row.hasClass('no-search-fields') ) {
				row.remove();
				row = null;
			}

			gvSearchWidget.addRow( table, row, null );

			return false;
		},

		/**
		 * Remove a search field to the table
		 * @param  object e event
		 */
		removeField: function(e) {
			e.preventDefault();
			var table = $(this).parents( 'table' );

			//remove line
			$(this).parents( 'tr' ).fadeTo( 'normal', 0.4, function() {

				$(this).remove();

				//check if is there any
				if( $('tr.gv-search-field-row', table ).length < 1 && $('tr.no-search-fields', table ).length < 1 ) {

					gvSearchWidget.addEmptyMsg( table );

				}

				gvSearchWidget.updateAvailableFields();

				gvSearchWidget.styleRow( table );
			});

			return false;

		},

		/**
		 * Render search fields table (includes a pre-loader animation)
		 * @param  {jQuery DOM object} parent The dialog div object
		 */
		renderUI: function( parent ) {

			var fields = $('.gv-search-fields-value', parent ).val();

			// get fields from server
			if( gvSearchWidget.selectFields === null ) {
				gvSearchWidget.dialog.append( '<p id="gv-loading"><span class="spinner"></span>' + gvGlobals.loading_text + '</p>' );
				gvSearchWidget.getSelectFields( parent );
				return;
			}

			// Is this dialog already rendered before & not loading fields again
			if( $('table', gvSearchWidget.dialog ).length && $('#gv-loading').length < 1 ) {
				return;
			}

			//add table and header
			table = gvSearchWidget.addTable();

			if( fields.length === 0 ) {
				gvSearchWidget.addRow( table, null, null );
			} else {
				gvSearchWidget.populateRows( table, fields );
			}

			gvSearchWidget.dialog.append( table );

			//
			gvSearchWidget.dialog.find('table tbody').sortable({
				start: function( event, ui ) {
					$( ui.item ).removeClass( 'alt' );
				}
			});

			gvSearchWidget.updateAvailableFields();

			$('#gv-loading').remove();
		},

		/**
		 * Add alt classes on table sort
		 * @param  {jQuery event|DOM object} e_or_object
		 * @return {void}
		 */
		zebraStripe: function() {

			// Zebra stripe the rows
			$( gvSearchWidget.dialog )
				.find('tr.gv-search-field-row')
					.removeClass('alt')
					.filter(':even').addClass('alt');

		},

		/**
		 * Given a JSON string convert it to the search fields table
		 * @param  {{jQuery DOM object}} table  The table DOM object
		 * @param  {string} fields JSON fields configuration
		 */
		populateRows: function( table, fields ) {
			var rows = $.parseJSON( fields ),
				pos = null;

			if( rows.length === 0 ) {
				gvSearchWidget.addEmptyMsg( table );
				return;
			}

			$.each( rows, function( i, values ) {
				gvSearchWidget.addRow( table, pos, values );
				pos = table.find('tbody tr:last');
			});

		},

		/**
		 * Init the search fields table
		 */
		addTable: function() {
			return $('<table class="form-table widefat">' +
						'<thead>'+
							'<tr>' +
								'<th class="cell-sort">&nbsp;</th>' +
								'<th class="cell-search-fields">' + gvSearchVar.label_searchfield +'</th>' +
								'<th class="cell-input-types">' + gvSearchVar.label_inputtype +'</th>' +
								'<th class="cell-add-remove">&nbsp;</th>' +
							'</tr>' +
						'</thead>'+
						'<tbody></tbody>' +
					'</table>' );
		},

		/**
		 * Add a "no-fields" message
		 * @param  {{jQuery DOM object}} table  The table DOM object
		 */
		addEmptyMsg: function( table ) {
			$( table ).append('<tr class="no-search-fields"><td colspan="4">'+ gvSearchVar.label_nofields +'&nbsp; <a href="#addSearchField">'+ gvSearchVar.label_addfield +'</a></td></tr>');
		},

		/**
		 * Add row to the table object
		 * @param {jQuery DOM object} table  The table DOM object
		 * @param {jQuery DOM object}  row   Table row object after which the new row will be added
		 * @param {object} curr  Configured values for the row ( field and input )
		 */
		addRow: function( table, row, curr ) {

			var rowString = $('<tr class="gv-search-field-row new-row hide-if-js" />')
				.append('<td class="cell-sort"><span class="icon gv-icon-caret-up-down" /></td>')
				.append('<td class="cell-search-fields">'+ gvSearchWidget.getSelectFields() +'</td>')
				.append('<td class="cell-input-types"><select class="gv-search-inputs" /></td>')
				.append('<td class="cell-add-remove"><a href="#addSearchField" class="dashicons dashicons-plus-alt" /><a href="#removeSearchField" class="dashicons dashicons-dismiss" /></td>');

			// add row
			if( row !== null && row.length ) {
				$( row, table ).after( rowString );
			} else {
				$( 'tbody', table ).append( rowString );
			}

			table.find('tr.new-row').each( function() {
				$(this).removeClass('new-row');

				// Set saved search field value
				if( curr !== null ) {
					$(this).find('select.gv-search-fields').val( curr.field );
				}

				// update the available input types
				gvSearchWidget.updateSelectInput( $(this) );

				// Set saved input type value
				// !! Do not try to optimize this line. This needs to come after 'gvSearchWidget.updateSelectInput()'
 				if( curr !== null ) {
 					$(this).find('select.gv-search-inputs').val( curr.input );
 				}

				// Fade in
				$(this).fadeIn( function() {
					$(this).removeClass('hide-if-js');
				});

			});

			gvSearchWidget.styleRow( table );
		},

		/**
		 * Style the table rows - remove/add sorting icon, zebra stripe
		 * @param  {object} table Table
		 * @return {[type]}       [description]
		 */
		styleRow: function( table ) {

			var sort_icon = $( '.cell-sort .icon', gvSearchWidget.dialog );

			if( $( 'tbody tr', gvSearchWidget.dialog ).length === 1 ) {
				sort_icon.fadeOut('fast', function() {
					$(this).parents('td').addClass('no-sort');
				});
			} else {
				sort_icon.fadeIn('fast', function() {
					$(this).parents('td').removeClass('no-sort');
				});
			}

			gvSearchWidget.zebraStripe();

		},

		/**
		 * When field is changed, update the search fields selector (disable the ones in use) and the input types for the new field selected
		 * @return {[type]} [description]
		 */
		updateRow: function() {
			var row = $(this).parents('tr');
			gvSearchWidget.updateSelectInput( row );
			gvSearchWidget.updateAvailableFields();
		},

		/**
		 * Modify the gvSearchWidget.selectFields input to disable existing search fields, then replace the fields with the generated input.
		 * @return {void}
		 */
		updateAvailableFields: function() {

			// Clear out the disabled options first
			$( 'option', gvSearchWidget.selectFields).attr('disabled', null );


			$('tr.gv-search-field-row .gv-search-fields', gvSearchWidget.dialog)

				// Update the selectFields var to disable all existing values
				.each( function() {
					gvSearchWidget.selectFields
						.find('option[value="'+ $(this).val() +'"]')
						.attr('disabled', true);
				})

				// Then once we have the select input finalized, run through again
				// and replace the select inputs with the new one
				.each( function() {

					var select = gvSearchWidget.selectFields.clone();

					// Set the value
					select.val( $(this).val() );

					// Enable the option with the current value
					select.find('option:selected').attr('disabled', null );

					// Replace the select with the generated one
					$(this).replaceWith( select );
				});

		},

		/**
		 * Update the input types for the new field selected
		 * @param  {jQuery DOM object} tr table row object
		 * @return {[type]}    [description]
		 */
		updateSelectInput: function( tr ) {
			var type = tr.find('select.gv-search-fields option:selected').attr('data-inputtypes');
			var select = tr.find('select.gv-search-inputs');

			var options = gvSearchWidget.getSelectInput( type );

			select.html( options );

			// If there's only one option, disable ability to change it.
			select.prop( 'disabled', function() {
				return ( $('option', $(this)).length === 1 );
			} );

		},

		/**
		 * Get the Select DOM object populated with the available search fields
		 * If not already in cache, get it from server using AJAX request
		 * @param  {jQuery DOM object} parent The dialog div object
		 */
		getSelectFields: function( parent ) {

			if( gvSearchWidget.selectFields !== null ) {

				gvSearchWidget.updateAvailableFields();

				// .html() returns the <option>s, we want the <select>
				return gvSearchWidget.selectFields.prop('outerHTML');
			}

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				async: true,
				dataType: 'html',
				data: {
					action: 'gv_searchable_fields',
					nonce: gvSearchVar.nonce,
					formid: $('#gravityview_form_id').val(),
					template_id: $('#gravityview_directory_template').val()
				},
				success: function( response ) {
					if( response !== '0' ) {
						gvSearchWidget.selectFields = $(response);
						gvSearchWidget.renderUI( parent );
					}

				}
			});
		},

		getSelectInput: function( type ) {

			var labels = $.parseJSON( gvSearchVar.input_labels ),
				types = $.parseJSON( gvSearchVar.input_types ),
				options = [];

			// get list of inputs
			var inputs = gvSearchWidget.getValue( types, type );

			if( inputs === null ) {
				return '';
			}

			// iterate through the requested input types
			$.each( inputs, function( k, input ) {

				//get label
				var label = gvSearchWidget.getValue( labels, input );

				options.push('<option value="' + input + '">' + label + '</option>');
			});

			return options.join();
		},

		// helper: get value from a js object given a certain key
		getValue: function( obj, key ) {
			var value = null;
			$.each( obj, function( k, val ) {
				if( key === k ) {
					value = val;
					return false;
				}
			});
			return value;
		},

		/**
		 * Update widget config on dialog close
		 * @param  {object} event
		 * @param  {[type]} ui    [description]
		 */
		updateOnClose: function( event, ui ) {
			var configs = [];

			//loop throught table rows
			gvSearchWidget.dialog.find('table tr.gv-search-field-row').each( function() {
				var row = {};
				row.field = $(this).find('select.gv-search-fields').val();
				row.input = $(this).find('select.gv-search-inputs').val();
				configs.push( row );
			});

			// save
			$( '.gv-search-fields-value', gvSearchWidget.dialog ).val( JSON.stringify( configs ) );

		},

		/**
		 * When form or template change, clear the select fields cache and remove all the search_bar configs
		 */
		clearCache: function() {
			gvSearchWidget.selectFields = null;
			// clean table & values
			$('.gv-search-fields-value').each( function() {
				$(this).parents('.gv-dialog-options').find('table').remove();
				$(this).val('');
			});

		}



	}; // end





	$(document).ready( function() {
		gvSearchWidget.init();

	});

}(jQuery));
