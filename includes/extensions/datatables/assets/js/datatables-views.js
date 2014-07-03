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
TableTools.buttons.newpdf =  {
	"sAction": "flash_pdf",
    "sTag": "default",
    "sFieldBoundary": "",
    "sFieldSeperator": "\t",
    "sNewLine": "\n",
    "sFileName": "*.pdf",
    "sPdfOrientation": "portrait",
    "sPdfSize": "A4",
    "sPdfMessage": "",
    "sToolTip": "",
    "sButtonClass": "DTTT_button_pdf",
    "sButtonClassHover": "DTTT_button_pdf_hover",
    "sButtonText": "whatever",
    "mColumns": "all",
    "bHeader": true,
    "bFooter": true,
    "sDiv": "",
    "fnMouseover": null,
    "fnMouseout": null,
    "fnClick": function( nButton, oConfig, flash ) {

    	var _thisB = this;

    	//_thisB.s.dt.oApi._fnProcessingDisplay( _thisB.s.dt, true );
    	_thisB.s.dt.iDraw++;

		//var iColumns = _thisB.s.dt.aoColumns.length;
		var queryData = _thisB.s.dt.oApi._fnAjaxParameters( _thisB.s.dt );

		queryData.start = 0;
		queryData.length = -1;

    	_thisB.s.dt.oApi._fnBuildAjax( _thisB.s.dt, queryData, function( json ) {

    		//_thisB.s.dt.oApi._fnClearTable( _thisB.s.dt );

			var data = _thisB.s.dt.oApi._fnAjaxDataSrc( _thisB.s.dt, json );
			for ( var i=0, ien=data.length ; i<ien ; i++ ) {
				_thisB.s.dt.oApi._fnAddData( _thisB.s.dt, data[i] );
			}

			//_thisB.s.dt.oApi._fnProcessingDisplay( _thisB.s.dt, false );


				_thisB.fnSetText( flash,
					"title:"+ _thisB.fnGetTitle(oConfig) +"\n"+
					"message:"+ oConfig.sPdfMessage +"\n"+
					"colWidth:"+ _thisB.fnCalcColRatios(oConfig) +"\n"+
					"orientation:"+ oConfig.sPdfOrientation +"\n"+
					"size:"+ oConfig.sPdfSize +"\n"+
					"--/TableToolsOpts--\n" +
					_thisB.fnGetTableData(oConfig)
				);




		} );

    },
    "fnSelect": null,
    "fnComplete": function() { console.log('completed'); },
    "fnInit": null,
	};

		var table = $('.gv-datatables').DataTable( gvDTglobals );


		// capture click on TableTools export buttons
		// table.on( 'init.dt', function () {
  //   		alert( 'Table init' );
  //   		gvDataTables.hijackClickTableTools();
		// } );

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