
(function( $ ) {

	"use strict";

	var self = {
		/**
		 * @var {string} jQuery selector used to find approval target
		 */
		'selector': '.gv-user-activation-link'
	};

	$(function() {
		self.userActivation();
	});


    self.userActivation = function(){
        $(document).on('click', self.selector, function(e){
            e.preventDefault();

            var that = $(this);
            var activationKey = that.attr('activation-key');

            if (!confirm(gvUserActivation.confirm_message)) {
                return;
            }
            var spinner = self.ajaxSpinner(that, 'margin-left:10px');

            jQuery.post(gvUserActivation.ajax_url, {
                key:     activationKey,
                action: 'gf_user_activate',
                nonce:  gvUserActivation.nonce
            }, function (response) {

                // if there is an error message, alert it
                if ( ! response.success ) {

                    alert( response.data.message );
                    spinner.destroy();

                } else {

                    that.parent().html(gvUserActivation.success_message);
                    spinner.destroy();

                }

            });

        });
    },

    self.ajaxSpinner = function(elem, style) {

        this.elem = elem;
        this.image = '<img src="' + gvUserActivation.spinner_url + '" style="' + style + '" />';

        this.init = function () {
            this.spinner = jQuery(this.image);
            jQuery(this.elem).after(this.spinner);
            return this;
        }

        this.destroy = function () {
            jQuery(this.spinner).remove();
        }

        return this.init();
    }

} (jQuery) );
