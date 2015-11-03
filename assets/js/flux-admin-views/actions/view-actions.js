var ViewDispatcher = require('../dispatcher/view-dispatcher');
var ViewConstants = require('../constants/view-constants');

var ViewActions = {

    /* -- Panel actions -- */

    /**
     * Open a specific panel
     * @param id string Panel ID
     */
    openPanel: function( id ) {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.PANEL_OPEN,
            panelId: id
        });
    },

};

module.exports = ViewActions;