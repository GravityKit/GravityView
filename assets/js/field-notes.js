/**
 * Javascript for Entry Notes
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 *
 * globals jQuery, gvGlobals, gvNotes
 */

var cb;
(function( $ ) {

    "use strict";

    var self = {},
        noteTextArea = $('textarea[name="new_note"]'),
        note = noteTextArea.val(),
        submit = $('input[name="add_note"]'),
        btnBulkActions = $('input[value="Apply"].button'),
        notes = $('tbody#the-comment-list tr[valign="top"]'),
        empty_to_start = notes.length === 0,
        lastChecked = null;

    if (empty_to_start === false){
        var bulk_cb =
            '<tr class="cb_bulk">' +
            '<th class="check-column" scope="row" style="padding:9px 3px 0 0">' +
            '<input type="checkbox" value="1051" name="note[]">' +
            '</th>' +
            '<td colspan="2"></td>' +
            '</tr>',
        existing_note = $(".gv-list-single-container tbody#the-comment-list tr:last");
        $('.gv-list-single-container  tbody#the-comment-list').prepend(bulk_cb);
        existing_note.before($(bulk_cb).clone());
    }

    submit.on('click', function( e ){
        e.preventDefault();
        e.stopPropagation();
        self.init( e );
    });
    btnBulkActions.on('click', function( e ){
        e.preventDefault();
        e.stopPropagation();
        var bulk_actions = $('select[name="bulk_action"] option:selected').val();

        if (bulk_actions !== ''){
            self.init( e , bulk_actions );
        }
    });


    self.init = function( e , bulk_actions ){

        var note = $('textarea[name="new_note"]').val(),
            email_address = $('select[name="gentry_email_notes_to"] option:selected').val(),
            email_subject = $('input[name="gentry_email_subject"]').val(),
            add_note = submit.val();

        $(noteTextArea).prop('disabled', true);
        $(submit).prop('disabled', true);

        self.trigger_ajax( note, email_address, email_subject, add_note, bulk_actions );

    };

    self.trigger_ajax = function( note, email_address, email_subject, add_note, bulk_actions ){

        var data = {
            action: 'gv_trigger_update_notes',
            gforms_update_note: gvNotes.nonce,
            new_note: note,
            add_note: add_note,
            bulk_action: bulk_actions,
            gentry_email_notes_to: email_address,
            gentry_email_subject: email_subject
        },
            cb = self.getChecked();

        if (bulk_actions === 'delete' && cb.noteID.length > 0){
            data['note'] = cb.noteID;
        }

        $.post( gvNotes.sAjaxUrl, data )
            .done(function(data) {
                var existing_note = $("tbody#the-comment-list tr[valign='top']"),
                    existing_note_length = existing_note.length;
                if (bulk_actions === 'delete'){
                    $("input:checkbox:checked").each(function(){
                        $(this).prop('checked', false);
                    });
                    $(cb.noteObj).each(function(){
                        $(this).parents('tr:not(".cb_bulk")').fadeOut('slow', function(){
                            this.remove();
                        });
                        existing_note_length = Math.max(0,existing_note_length - 1);
                    });
                    $("select[name='bulk_action']").val('');
                    if (existing_note_length < 1){
                        $('.cb_bulk').fadeOut('slow', function(){
                            this.remove();
                        });
                        $('.alignleft.actions').fadeOut('slow', function(){
                            this.remove();
                        });
                        empty_to_start = true;
                    }
                    btnBulkActions.blur();
                } else {
                    var new_note = $(data).find("table.entry-detail-notes tr[valign='top']:last");

                    if (existing_note.length < 1){
                        existing_note = $("tbody#the-comment-list tr");
                        $(existing_note).before(new_note.hide());
                    } else {
                        $(existing_note).after(new_note.hide());
                    }

                    new_note.fadeIn('slow');
                    existing_note_length++;
                    $('textarea[name="new_note"]').val('');
                    submit.blur();

                    $('tbody#the-comment-list').prepend(bulk_cb);
                    existing_note.before($(bulk_cb).clone());

                    $('input[type=checkbox]').click(self.handleChecked);
                    self.clickAllBoxes();

                    if (empty_to_start === true){
                        var bulk_action_html = '' +
                            '<div class="alignleft actions" style="padding:3px 0;">' +
                                '<label class="hidden" for="bulk_action">Bulk action</label>' +
                                '<select name="bulk_action" id="bulk_action">' +
                                    '<option value="">Bulk action</option>' +
                                    '<option value="delete">Delete</option>' +
                                '</select>' +
                                '<input type="submit" class="button" value="Apply" style="width: 50px;" />' +
                            '</div>';

                        var bulk_cb =
                                '<tr class="cb_bulk">' +
                                '<th class="check-column" scope="row" style="padding:9px 3px 0 0">' +
                                '<input type="checkbox" value="1051" name="note[]">' +
                                '</th>' +
                                '<td colspan="2"></td>' +
                                '</tr>',
                            existing_note = $("tbody#the-comment-list tr:last");

                        $('table.entry-detail-notes').before(bulk_action_html);
                        $('tbody#the-comment-list').prepend(bulk_cb);
                        existing_note.before($(bulk_cb).clone());

                        empty_to_start = false;
                        btnBulkActions = $('input[value="Apply"].button');

                        btnBulkActions.on('click', function( e ){
                            e.preventDefault();
                            e.stopPropagation();
                            var bulk_actions = $('select[name="bulk_action"] option:selected').val();

                            if (bulk_actions !== ''){
                                self.init( e , bulk_actions );
                            }
                        });
                    }
                }
                console.log( 'success!' );

            })
            .fail(function() {
                console.log( "error" );
            })
            .always(function() {
                $(noteTextArea).prop('disabled', '');
                $(submit).prop('disabled', '');
                console.log( "finished" );
            });
    };

    //turn on shift click for bulk row selection
    self.handleChecked = function(e) {
        if(lastChecked && e.shiftKey) {

            var cb = $('input[type="checkbox"]');
            var i = cb.index(lastChecked);
            var j = cb.index(e.target);
            var checkboxes = [];
            if (j > i) {
                checkboxes = $('input[type="checkbox"]:gt('+ (i-1) +'):lt('+ (j-i) +')');
            } else {
                checkboxes = $('input[type="checkbox"]:gt('+ j +'):lt('+ (i-j) +')');
            }

            if (!$(e.target).is(':checked')) {
                $(checkboxes).removeAttr('checked');
            } else {
                $(checkboxes).prop('checked', 'checked');
            }
        }
        lastChecked = e.target;

        // Other click action code.

    };

    //toggle all rows on/ff
    self.clickAllBoxes = function() {
        $('table').find('.cb_bulk').toggle(
            function() {
                $('input[type=checkbox]').prop('checked', true);
            },
            function() {
                $('input[type=checkbox]').prop('checked', false);
            }
        );
    };
    $('input[type=checkbox]').click(self.handleChecked);
    self.clickAllBoxes();

    self.getChecked = function(){
        var cb = {noteID: [], noteObj: []};
        $("input:checkbox:checked").each(function( i ){
            cb.noteID.push($(this).val());
            cb.noteObj.push($(this));
        });
        return cb;
    };

} (jQuery) );
