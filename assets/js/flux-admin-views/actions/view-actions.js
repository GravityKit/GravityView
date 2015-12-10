var ViewDispatcher = require('../dispatcher/view-dispatcher');
var ViewConstants = require('../constants/view-constants');
var ViewApi = require('../api/view-api.js');

var ViewActions = {

    /* -- Panel actions -- */

    /**
     * Open a specific panel
     * @param id        string  Active Panel ID
     * @param returnId  string  Return Panel ID
     * @param args      object  Extra arguments to the panel (optional)
     */
    openPanel: function( id, returnId, args = null ) {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.PANEL_OPEN,
            panelId: id,
            returnId: returnId,
            extraArgs: args
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

    /**
     *
     * @param id
     * @param value
     */
    updateSetting: function( id, value ) {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.UPDATE_SETTING,
            key: id,
            value: value
        });
    },

    /** -- Layout Actions -- **/

    //Load saved layout
    fetchSavedLayout: function() {
        ViewApi.getSavedLayout();
    },

    // Tabs
    /**
     * Change Tab
     * @param tabId string Tab id (directory, single, edit, export)
     */
    changeTab: function( tabId ) {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.CHANGE_TAB,
            tab: tabId,
        });
    },

    /**
     * Add a row of the type (columns structure) on the tab (context) at the row pointer.
     * @param context   Directory, Single, Edit, Export
     * @param pointer   Reference Row ID indicating where the new row should be inserted ( array index )
     * @param colStruct      Column structure of the row
     */
    addRow: function( context, pointer, colStruct ) {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.LAYOUT_ADD_ROW,
            context: context,
            pointer: pointer,
            struct: colStruct
        });
    },

    /**
     * Remove row on the tab (context) at the row pointer (row id).
     * @param args Object containing the tab id and the row id
     */
    removeRow: function( args ) {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.LAYOUT_DEL_ROW,
            context: args.context,
            pointer: args.pointer
        });
    },

    /**
     *
     * @param id Row Setting key
     * @param value Row Setting value
     * @param args Row pointer context
     */
    updateRowSetting: function( id, value, args ) {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.LAYOUT_SET_ROW,
            key: id,
            value: value,
            context: args.context,
            pointer: args.pointer
        });
    }




};

module.exports = ViewActions;