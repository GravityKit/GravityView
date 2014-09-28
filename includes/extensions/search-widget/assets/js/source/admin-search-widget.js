/**
 * New Search widget UI at Add New / Edit Views screen
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.1.7
 */


(function( $ ) {



	var gvSearchWidget = {

		selectFields : null,

		init: function() {
			var gvsw = gvSearchWidget;

			$('body')
				// hook on all the open settings buttons
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

		openDialog: function(e) {
			e.preventDefault();

			gvSearchWidget.renderUI( $(this).parents('.gv-fields') );
		},

		addField: function(e) {
			e.preventDefault();

			var table = $(this).parents( 'table' ),
				row = $(this).parents( 'tr' );

			if( row.hasClass('no-search-fields') ) {
				row.remove();
				row = null;
			}
			// if no fields message exists, remove it
			table.find('tr.no-search-fields').remove();

			gvSearchWidget.addRow( table, row, null );

			return false;
		},

		removeField: function(e) {
			e.preventDefault();
			var table = $(this).parents( 'table' );

			//remove line
			$(this).parents( 'tr' ).fadeTo( 'normal', 0.4, function() {

				$(this).remove();

				//check if is there any
				if( $('tr.gv-search-field-row', table ).length < 1 && $('tr.no-search-fields', table ).length < 1 ) {

					gvSearchWidget.addEmptyMsg( table );

				} else {

					gvSearchWidget.zebraStripe( table );

				}
			});

			return false;

		},

		renderUI: function( parent ) {

			var gvsw = gvSearchWidget,
				fields = $('.gv-search-fields-value', parent ).val(),
				dialog = $( '.gv-dialog-options', parent );

			if( gvsw.selectFields === null ) {
				dialog.append( '<p id="gv-loading"><span class="spinner"></span>' + gvGlobals.loading_text + '</p>' );
				gvsw.getSelectFields( parent );
				return;
			}

			// Is this dialog already rendered before & not loading fields again
			if( $('table', dialog ).length && $('#gv-loading').length < 1 ) {
				return;
			}

			//add table and header
			table = gvsw.addTable();

			if( fields.length === 0 ) {
				gvsw.addRow( table, null, null );
			} else {
				gvsw.populateRows( table, fields );
			}

			dialog.append( table );

			//
			dialog.find('table tbody').sortable({
				start: function( event, ui ) {
					$( ui.item ).removeClass( 'alt' );
				}
			});

			$('#gv-loading').remove();
		},

		/**
		 * Add alt classes on table sort
		 * @param  {jQuery event|DOM object} e_or_object
		 * @return {void}
		 */
		zebraStripe: function( e_or_object ) {

			var target = e_or_object.target || e_or_object;

			// Zebra stripe the rows
			$( target )
				.find('tr.gv-search-field-row')
					.removeClass('alt')
					.filter(':even').addClass('alt');

		},

		populateRows: function( table, fields ) {
			var rows = $.parseJSON( fields ),
				pos = null;

			$.each( rows, function( i, values ) {
				gvSearchWidget.addRow( table, pos, values );
				pos = table.find('tbody tr:last');
			});

		},

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

		addEmptyMsg: function( table ) {
			$( table ).append('<tr class="no-search-fields"><td colspan="4">'+ gvSearchVar.label_nofields +'&nbsp; <a href="#addSearchField">'+ gvSearchVar.label_addfield +'</a></td></tr>');
		},


		addRow: function( table, row, curr ) {
			// get fields or cache
			// if( gvSearchWidget.selectFields === null ) {
			// 	gvSearchWidget.getSelectFields( table, row, curr );
			// 	return;
			// }

			var rowString = $('<tr class="gv-search-field-row new-row hide-if-js" />')
				.append('<td class="cell-sort"><span class="icon gv-icon-caret-up-down" /></td>')
				.append('<td class="cell-search-fields">'+ gvSearchWidget.selectFields +'</td>')
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
				if( curr !== null ) {
					$(this).find('select.gv-search-fields').val( curr.field );
				}
				gvSearchWidget.updateSelectInput( $(this) );
				if( curr !== null ) {
					$(this).find('select.gv-search-inputs').val( curr.input );
				}
				$(this).fadeTo( 'normal', 1, function() { $(this).removeClass('hide-if-js'); });
			});

			gvSearchWidget.zebraStripe( table );

		},

		updateRow: function() {
			var row = $(this).parents('tr');
			gvSearchWidget.updateSelectInput( row );

		},

		updateSelectInput: function( tr ) {
			var type = tr.find('select.gv-search-fields option:selected').attr('data-inputtypes'),
				select = tr.find('select.gv-search-inputs');

			var options = gvSearchWidget.getSelectInput( type );

			select.html( options );

			if( select.find('option').length < 2 ) {
				select.prop( 'disabled', true );
			} else {
				select.prop( 'disabled', false );
			}

		},

		// Fetch Form Searchable fields (AJAX)
		getSelectFields: function( parent ) {

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				async: true,
				dataType: 'html',
				data: {
					action: 'gv_searchable_fields',
					nonce: gvSearchVar.nonce,
					formid: $('#gravityview_form_id').val(),
					template_id: $('#gravityview_directory_template').val(),
				},
				success: function( response ) {
					if( response !== '0' ) {
						gvSearchWidget.selectFields = response;
						gvSearchWidget.renderUI( parent );
					}

				}
			});
		},

		getSelectInput: function( type ) {

			var labels = $.parseJSON( gvSearchVar.input_labels ),
				types = $.parseJSON( gvSearchVar.input_types ),
				options = '';

			// get list of inputs
			var inputs = gvSearchWidget.getValue( types, type );

			if( inputs === null ) {
				return '';
			}

			// iterate through the requested input types
			$.each( inputs, function( k, input ) {

				//get label
				var label = gvSearchWidget.getValue( labels, input );

				options += '<option value="' + input + '">' + label + '</option>';
			});

			return options;
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


		updateOnClose: function( event, ui ) {
			var dialog = $(this),
				configs = [];

			//loop throught table rows
			dialog.find('table tr.gv-search-field-row').each( function() {
				var row = {};
				row.field = $(this).find('select.gv-search-fields').val();
				row.input = $(this).find('select.gv-search-inputs').val();
				configs.push( row );
			});

			// save
			$( '.gv-search-fields-value', dialog ).val( JSON.stringify( configs ) );

		},

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
