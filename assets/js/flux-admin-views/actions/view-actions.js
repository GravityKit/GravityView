var ViewDispatcher = require('../dispatcher/view-dispatcher');
var ViewConstants = require('../constants/view-constants');
var ViewApi = require('../api/view-api.js');

var ViewActions = {

    /* -- Panel actions -- */

    /**
     * Open a specific panel
     * @param id        string  Active Panel ID
     * @param returnId  string  Return Panel ID
     */
    openPanel: function( id, returnId ) {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.PANEL_OPEN,
            panelId: id,
            returnId: returnId
        });
    },

    /**
     * Close All Panels
     */
    closePanel: function() {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.PANEL_CLOSE
        });
    },

    /** -- Settings Actions -- */

    // Load all the view settings values
    fetchSettingsAllValues: function() {
        ViewApi.getSettingsAllValues();
    },
    fetchSettingsSections: function( forms, templates ) {
        ViewApi.getSettingsSections( forms, templates );
    },
    fetchSettingsInputs: function( forms, templates ) {
        ViewApi.getSettingsInputs( forms, templates );
    },



    // update functions (to update new values into store)

    updateSetting: function( id, value ) {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.UPDATE_SETTING,
            key: id,
            value: value
        });
    }

};

module.exports = ViewActions;