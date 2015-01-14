/**
 * Custom js script loaded on Views frontend
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */


jQuery(document).ready( function( $ ) {

	var gvFront = {

		init: function() {
			this.cookies();
			this.datepicker();
		},

		datepicker: function() {

			// If datepicker is loaded
			if( jQuery.fn.datepicker ) {

				$('.gv-datepicker').each(
				    function (){
				        var element = jQuery(this);
				        var image = "";
				        var showOn = "focus";

				        if(element.hasClass("datepicker_with_icon")){
				            showOn = "both";
				            image = jQuery('#gforms_calendar_icon_' + this.id).val();
				        }

				        gvGlobals.datepicker.showOn = showOn;
				        gvGlobals.datepicker.buttonImage = image;
				        gvGlobals.datepicker.buttonImageOnly = true;

				        // Process custom date formats
				        if( !gvGlobals.datepicker.dateFormat ) {

				        	var format = "mm/dd/yy";

				        	if(element.hasClass("mdy"))
				        	    format = "mm/dd/yy";
				        	else if(element.hasClass("dmy"))
				        	    format = "dd/mm/yy";
				        	else if(element.hasClass("dmy_dash"))
				        	    format = "dd-mm-yy";
				        	else if(element.hasClass("dmy_dot"))
				        	    format = "dd.mm.yy";
				        	else if(element.hasClass("ymd_slash"))
				        	    format = "yy/mm/dd";
				        	else if(element.hasClass("ymd_dash"))
				        	    format = "yy-mm-dd";
				        	else if(element.hasClass("ymd_dot"))
				        	    format = "yy.mm.dd";

				        	gvGlobals.datepicker.dateFormat = format;
					    }

					    element.datepicker( gvGlobals.datepicker );
				    }
				);

			}
		},

		cookies: function() {
			if( $("#gravityview-view-id").length > 0 ) {

				var viewId = $("#gravityview-view-id").val();

				// Manages the Go Back link in single entry view based on cookies
				if( $("#gravityview_back_link").length > 0 ) {
					// single entry view
					if( $.cookie('gravityview_back_link_'+ viewId ) != null ) {
						$("#gravityview_back_link").attr('href', $.cookie('gravityview_back_link_'+ viewId) );
					}

				} else {
					// directory view

					//set cookie
					$.cookie('gravityview_back_link_'+ viewId, window.location.href, { path: gvGlobals.cookiepath } );

				}
			}
		}
	};

	gvFront.init();

});
