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
			// hook on all the open settings buttons
			$('body').on( 'click', 'h5.field-id-search_widget a[href="#settings"]', gvsw.openDialog );

			// hook to add/remove rows
			$('body').on( 'click', ".gv-dialog-options a[href='#addSearchField']", gvsw.addField );
			$('body').on( 'click', ".gv-dialog-options a[href='#removeSearchField']", gvsw.removeField );

			// hook to update row input types
			$('body').on( 'change', ".gv-dialog-options select.gv-search-fields", gvsw.updateRow );

			// hook on dialog close to update widget config
			$('body').on( 'dialogbeforeclose', '.gv-dialog-options', gvsw.updateOnClose );

			// hook on assigned form change to clear cache
			$('#gravityview_form_id').change( gvsw.clearCache );

		},

		openDialog: function(e) {
			var gvsw = gvSearchWidget;
			e.preventDefault();
			gvsw.renderUI( $(this).parents('.gv-fields') );
		},

		addField: function(e) {
			e.preventDefault();
			var gvsw = gvSearchWidget,
				table = $(this).parents( 'table' ),
				row = $(this).parents( 'tr' );

			if( row.hasClass('no-search-fields') ) {
				row.remove();
				row = null;
			}
			// if no fields message exists, remove it
			table.find('tr.no-search-fields').remove();
			gvsw.addRow( table, row, null );
		},

		removeField: function(e) {
			e.preventDefault();
			var gvsw = gvSearchWidget,
				table = $(this).parents( 'table' );
			//remove line
			$(this).parents( 'tr' ).remove();
			//check if is there any
			if( table.find('tr').length < 2 ) {
				gvsw.addEmptyMsg( table );
			}
		},

		renderUI: function( parent ) {

			var gvsw = gvSearchWidget,
				fields = $('.gv-search-fields-value', parent ).val(),
				dialog = $( '.gv-dialog-options', parent );

			// Is this dialog already rendered before?
			if( $('table', dialog ).length ) {
				return;
			}

			//add table and header
			table = gvsw.addTable();

			if( fields === '' ) {
				gvsw.addEmptyMsg( table );
			} else {
				gvsw.populateRows( table, fields );
			}

			dialog.append( table );

			dialog.find('table tbody').sortable();

		},

		populateRows: function( table, fields ) {
			var gvsw = gvSearchWidget,
				rows = $.parseJSON( fields ),
				pos = null;

			$.each( rows, function( i, values ) {
				gvsw.addRow( table, pos, values );
				pos = table.find('tbody tr:last');
			});

		},

		addTable: function() {
			return $('<table cellpading="0" cellspacing="0" border="0">' +
						'<thead>'+
							'<tr>' +
								'<th>&nbsp;</th>' +
								'<th>' + gvSearchVar.label_searchfield +'</th>' +
								'<th>' + gvSearchVar.label_inputtype +'</th>' +
								'<th>&nbsp;</th>' +
							'</tr>' +
						'<thead>'+
						'<tbody></tbody>' +
					'</table>' );
		},

		addEmptyMsg: function( table ) {
			$( table ).append('<tr class="no-search-fields"><td colspan="4">&nbsp;'+ gvSearchVar.label_nofields +'&nbsp;&nbsp;<a href="#addSearchField">'+ gvSearchVar.label_addfield +'</a></td></tr>');
		},


		addRow: function( table, row, curr ) {
			var gvsw = gvSearchWidget;

			// get fields or cache
			if( gvsw.selectFields === null ) {
				gvsw.getSelectFields( table, row, curr );
				return;
			}

			var rowString = '<tr class="gv-search-field-row new-row">'+
								'<td><span class="dashicons dashicons-sort"></span></td>'+
								'<td>'+ gvsw.selectFields +'</td>'+
								'<td class="row-inputs"><select class="gv-search-inputs"></select></td>'+
								'<td class="row-options"><a href="#addSearchField" class="dashicons dashicons-plus-alt"></a><a href="#removeSearchField" class="dashicons dashicons-dismiss"></a></td>'+
							'</tr>';

			// add row
			if( row !== null && row.length ) {
				$( row, table ).after( rowString );
			} else {
				$( 'tbody', table ).append( rowString );
			}


			table.find('tr.new-row').each( function() {

				if( curr !== null ) {
					$(this).find('select.gv-search-fields').val( curr.field );
				}
				gvsw.updateSelectInput( $(this) );
				if( curr !== null ) {
					$(this).find('select.gv-search-inputs').val( curr.input );
				}
				$(this).removeClass('new-row');
			});

		},

		updateRow: function() {
			var row = $(this).parents('tr');
			gvSearchWidget.updateSelectInput( row );

		},

		updateSelectInput: function( tr ) {
			var gvsw = gvSearchWidget,
				type = tr.find('select.gv-search-fields option:selected').attr('data-type');

			var options = gvsw.getSelectInput( type );

			tr.find('select.gv-search-inputs').html( options );

		},

		// Fetch Form Searchable fields (AJAX)
		getSelectFields: function( table, row, curr ) {
			var gvsw = gvSearchWidget;

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				async: true,
				dataType: 'html',
				data: {
					action: 'gv_searchable_fields',
					nonce: gvSearchVar.nonce,
					formid: $('#gravityview_form_id').val(),
				},
				success: function( response ) {
					if( response !== '0' ) {
						gvsw.selectFields = response;
						gvsw.addRow( table, row, curr );
					}

				}
			});
		},

		getSelectInput: function( type ) {
			var types = $.parseJSON( gvSearchVar.inputs ),
				options = '',
				list = types.text;

			if( type === 'multi' ) {
				list = types.multi;
			} else if( type === 'date' ) {
				list = types.date;
			}

			$.each( list, function( key, label ) {
				options += '<option value="' + key + '">' + label + '</option>';
			});

			return options;
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
			gvsw.selectFields = null;
		}



	}; // end





	$(document).ready( function() {
		gvSearchWidget.init();

	});

}(jQuery));
