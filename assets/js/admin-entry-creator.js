/**
 * JS for Entry Creator function.
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityView <hello@gravityview.co>
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 *
 * globals jQuery, GVEntryCreator
 */

(function($) {

    "use strict";

    $(document).ready(function() {

        var gv_nonce = $('#gv_entry_creator_nonce').val();

        $('#change_created_by').selectWoo({
            ajax: {
                type: 'POST',
                url: GVEntryCreator.ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    var on_load = 0;
                    if (typeof params.term == 'undefined') {
                        on_load = 1;
                    }
                    return {
                        q: params.term,
                        page: params.page,
                        on_load: on_load,
                        action: GVEntryCreator.action,
                        gv_nonce: gv_nonce
                    };
                },
                processResults: function(data, params) {
                    var terms = [];
                    if (data) {
                        $.each(data, function(index, user) {
                            terms.push({
                                id: user.ID,
                                text: user.display_name + ' (' + user.user_nicename + ')'
                            });
                        });
                    }
                    return {
                        results: terms
                    };
                },
                cache: true
            },
        });
    });

}(jQuery));