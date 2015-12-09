var ViewConstants = require('../constants/view-constants.js');
var ViewDispatcher = require('../dispatcher/view-dispatcher');

/**
 * Helper function to dispatch
 * @param action Action type constant
 * @param values
 */
function updateSettings( action, values ) {
    ViewDispatcher.dispatch({
        actionType: action,
        values: values
    });
}


var ViewApi = {

    // *** View Settings *** //

    /**
     * Fetch Settings Sections from server
     *
     * @param forms
     * @param templates
     */
    getSettingsSections: function( forms, templates ) {
        updateSettings( ViewConstants.UPDATE_SETTINGS_SECTIONS, gravityview_view_settings.settings_sections );
    },

    /**
     * Fetch Settings Inputs from server
     * @param forms
     * @param templates
     */
    getSettingsInputs: function( forms, templates ) {
        // todo: fetch using AJAX. When form changes or template, the settings inputs may change

        updateSettings( ViewConstants.UPDATE_SETTINGS_INPUTS, gravityview_view_settings.settings_inputs );
    },

    /**
     * Fetch saved Settings Values from server
     */
    getSettingsAllValues: function() {
        updateSettings( ViewConstants.UPDATE_SETTINGS_ALL, gravityview_view_settings.settings_values );
    },


    // *** View Fields *** //

    getSavedLayout: function() {

        var data = {
            action: 'gv_get_saved_layout',
            view: jQuery('#post_ID').val(),
            nonce: gvGlobals.nonce
        };

        jQuery.ajax( {
            type: 'POST',
            url: ajaxurl,
            data: data,
            dataType: 'json',
            async: true
        } ).done( function ( response ) {
            updateSettings( ViewConstants.UPDATE_LAYOUT_ALL, response.data );
        } ).fail( function ( jqXHR ) {
            console.log( jqXHR );
        } ).always( function () {
            //
        } );
    },


};

module.exports = ViewApi;