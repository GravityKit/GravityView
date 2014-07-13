/**
 * Custom js script loaded on Views edit screen (admin)
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

var gvDataTablesExt = {
	init: function() {

		$('#gravityview_directory_template').change( gvDataTablesExt.showMetabox ).change();

		$('#datatables_settingstabletools, #datatables_settingsscroller').change( gvDataTablesExt.showGroupOptions ).change();

		$('#gv_dt_tt_showbuttons').click( gvDataTablesExt.showButtonsOptions );

		$('#datatables_settingsscroller').change( gvDataTablesExt.toggleNonCompatible ).change();

	},

	showMetabox: function() {
		var template = $('#gravityview_directory_template').val();
		if( 'datatables_table' === template ) {
			$('#gravityview_datatables_settings').slideDown();
		} else {
			$('#gravityview_datatables_settings').slideUp();
		}
	},

	showButtonsOptions: function(e) {
		e.preventDefault();
		$('#gv_dt_tt_buttons').slideToggle();
	},


	showGroupOptions: function() {
		var _this = $(this);
		if( _this.is(':checked') ) {
			_this.parents('tr').siblings().not('#gv_dt_tt_buttons').slideDown();
		} else {
			_this.parents('tr').siblings().slideUp();
		}
	},

	toggleNonCompatible: function() {
		var _this = $(this),
			fixedHeaderCB = $('#datatables_settingsfixedheader');

		if( _this.is(':checked') ) {
			fixedHeaderCB.prop('checked', false).parents('tr').hide();
		} else {
			fixedHeaderCB.parents('tr').show();
		}
	}

};



$(document).ready( function() {
	gvDataTablesExt.init();
});


}(jQuery));
