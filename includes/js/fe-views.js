/**
 * Custom js script loaded on Views frontend
 *
 * @package   GravityView
 * @author    Zack Katz <zack@katzwebservices.com>
 * @license   ToBeDefined
 * @link      http://www.katzwebservices.com
 * @copyright Copyright 2013, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */


(function( $ ) {

	

	$(document).ready( function() {
		
		
		// Manages the Go Back link in single entry view based on cookies
		if( $("#gravityview_back_link").length > 0 ) {
			// single entry view
			if( $.cookie('gravityview_back_link') != null ) {
				$("#gravityview_back_link").attr('href', $.cookie('gravityview_back_link') );
			}
			
		} else {
			// directory view
			
			//set cookie
			$.cookie('gravityview_back_link', window.location.href, { expires: 7, path: '/' });
			
		}
	

		
	});
 
}(jQuery));
