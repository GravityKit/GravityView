var ViewDispatcher = require('../dispatcher/view-dispatcher.js');
var ViewConstants = require('../constants/view-constants.js');
var EventEmitter = require('events').EventEmitter;
var assign = require('object-assign');

var CHANGE_EVENT = 'change';


/**
 * Store about the View Settings, configuration sections, inputs and values
 */
var SettingsStore = assign( {}, EventEmitter.prototype, {

    /**
     * Holds the View Settings Sections
     */
    sections: null,

    /**
     * Holds the View Settings inputs
     */
    inputs: null,

    /**
     * Holds the view settings values
     */
    settings: null,

    /**
     * Holds the list of forms active for this view
     */
    forms: null,

    /**
     * Holds the list of available forms
     */
    formsList: null,

    /**
     * Holds the list of available templates (includes the presets)
     */
    templatesList: null,


    emitChange: function() {
        this.emit( CHANGE_EVENT );
    },

    /**
     * @param {function} callback
     */
    addChangeListener: function( callback ) {
        this.on( CHANGE_EVENT, callback );
    },

    /**
     * @param {function} callback
     */
    removeChangeListener: function( callback ) {
        this.removeListener( CHANGE_EVENT, callback );
    },

    /** Handle settings */

    /**
     * Update one setting
     * @param id
     * @param value
     */
    saveSetting: function( id, value ) {
        this.settings[ id ] = value;
    },

    /**
     * Update all settings
     * @param values
     */
    saveAllSettings: function( values ) {
        this.settings = values;
    },

    /**
     * Get the settings object
     * @returns {null}
     */
    getAllValues: function() {
        return this.settings;
    },

    /** Handle sections and inputs */

    /**
     * Save Sections array
     * @param values
     */
    saveSections: function( values ) {
        this.sections = values;
    },

    /**
     * Get the list of sections
     * @returns {null}
     */
    getSections: function() {
        return this.sections;
    },

    /**
     * Save Settings Inputs
     * @param values
     */
    saveInputs: function( values ) {
        this.inputs = values;
    },

    /**
     * Get Settings Inputs
     * @returns {null}
     */
    getInputs: function() {
        return this.inputs;
    },

    /** Handle Forms list */

    /**
     * Save the available forms list
     * @param values
     */
    saveFormsList: function( values ) {
        this.formsList = values;
    },

    /**
     * Get the available forms list
     * @returns {null}
     */
    getFormsList: function() {
        return this.formsList;
    },

    /**
     * Save/update the active forms assigned to this view
     * @param values
     */
    saveActiveForms: function( values ) {
        this.forms = values;
    },

    /**
     * Get the active forms (assigned to the present view)
     * @returns {null}
     */
    getActiveForms: function() {
        return this.forms;
    },




});


ViewDispatcher.register( function( action ) {

    switch( action.actionType ) {

        case ViewConstants.UPDATE_FORMS_LIST:
            SettingsStore.saveFormsList( action.values );
            SettingsStore.emitChange();
            break;

        case ViewConstants.UPDATE_FORMS_ACTIVE:
            SettingsStore.saveActiveForms( action.values );
            SettingsStore.emitChange();
            break;

        case ViewConstants.UPDATE_SETTINGS_SECTIONS:
            SettingsStore.saveSections( action.values );
            SettingsStore.emitChange();
            break;

        case ViewConstants.UPDATE_SETTINGS_INPUTS:
            SettingsStore.saveInputs( action.values );
            SettingsStore.emitChange();
            break;


        case ViewConstants.UPDATE_SETTINGS_ALL:
            SettingsStore.saveAllSettings( action.values );
            SettingsStore.emitChange();
            break;

        case ViewConstants.UPDATE_SETTING:
            SettingsStore.saveSetting( action.key, action.value );
            SettingsStore.emitChange();
            break;
        
    }

    return true; // Needed for Flux promise resolution

});

module.exports = SettingsStore;