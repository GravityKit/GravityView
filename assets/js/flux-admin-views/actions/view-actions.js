var ViewDispatcher = require('../dispatcher/view-dispatcher');
var ViewConstants = require('../constants/view-constants');

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

    updateAllSettings: function( values ) {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.UPDATE_ALL_SETTINGS,
            settingsValues: values,
        });
    },

    updateSetting: function( id, value ) {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.UPDATE_SETTING,
            key: id,
            value: value
        });
    }





};

module.exports = ViewActions;