/**
 * Custom js script at post edit screen
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
		
		// add actions to bulk select box
		$("#bulk_action, #bulk_action2").append('<optgroup label="GravityView"><option value="approve-'+ ajax_object.form_id +'">' + ajax_object.label_approve +'</option><option value="unapprove-'+ ajax_object.form_id +'">'+ ajax_object.label_disapprove +'</option></optgroup>');
		
		
		
		
		
	});
 
}(jQuery));
