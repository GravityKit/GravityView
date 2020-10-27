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
            minimumInputLength: 3,
            ajax: {
                type: 'POST',
                url: GVEntryCreator.ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term, // search term
                        page: params.page,
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