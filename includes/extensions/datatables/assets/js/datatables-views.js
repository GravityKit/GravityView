/**
 * Custom js script loaded on Views frontend to set DataTables
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

var gvDataTables = {

	init: function() {
		var table = $('.gv-datatables').DataTable( gvDTglobals );

		// init FixedHeader
		if( gvDTFixedHeaderColumns.fixedHeader == 1 ) {
			new $.fn.dataTable.FixedHeader( table );
		}

		// init FixedColumns
		if( gvDTFixedHeaderColumns.fixedColumns == 1 ) {
			new $.fn.dataTable.FixedColumns( table );
		}

	},

};





$(document).ready( function() {
	gvDataTables.init();
});


}(jQuery));