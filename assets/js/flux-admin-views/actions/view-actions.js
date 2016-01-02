var ViewDispatcher = require('../dispatcher/view-dispatcher');
var ViewConstants = require('../constants/view-constants');
var ViewApi = require('../api/view-api.js');

module.exports = {

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

    fetchActiveForms: function() {
        ViewApi.getConfiguredActiveForms();
    },

    fetchFormsList: function() {
        ViewApi.getFormsList();
    },

    updateActiveForms: function( forms ) {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.UPDATE_FORMS_ACTIVE,
            values: forms
        });
    },

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
     * Update the settings of a row
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
    },


    /** Fields handling */

    fetchFieldsSections: function( forms, templates ) {
        ViewApi.getFieldsSections( forms, templates );
    },

    fetchFieldsList: function( forms ) {
        ViewApi.getFieldsList( forms );
    },

    /**
     * Trigger the Add Field to the Layout
     * @param args Object Field arguments: Context, Row, Col, Field (id, field_id, field_type, form_id, field_label)
     */
    addField: function( args ) {

        // fetch the field settings values ('gv_settings')
        ViewApi.getFieldSettingsValues( args );

        // add the field without 'gv_settings' while loading the settings
        ViewDispatcher.dispatch({
            actionType: ViewConstants.LAYOUT_ADD_FIELD,
            context: args.context,
            row: args.row,
            col: args.col,
            field: args.field
        });
    },

    removeField: function( args ) {
        ViewDispatcher.dispatch({
            actionType: ViewConstants.LAYOUT_DEL_FIELD,
            context: args.context,
            row: args.row,
            col: args.col,
            field: args.field
        });
    },

    moveField: function( id, source, target ) {
        console.log('drag'+id);
        ViewDispatcher.dispatch({
            actionType: ViewConstants.LAYOUT_MOV_FIELD,
            itemId: id,
            source: source,
            target: target
        });
    },


    /**
     * Trigger the field settings edit process (fetch field settings, and open the field settings panel)
     * @param args Object Field arguments ( context, row, col, field [id, field_id, form_id, field_type, gv_settings] )
     */
    editFieldSettings: function( args ) {
        ViewApi.getFieldSettings( args );
    },


    updateFieldSetting: function( args, newSettings ) {

        var newValues = {
            pointer: args,
            settings: newSettings
        };

        ViewDispatcher.dispatch({
            actionType: ViewConstants.UPDATE_FIELD_SETTINGS,
            values: newValues
        });
    }







};

