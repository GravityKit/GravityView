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

    saveSetting: function( id, value ) {
        this.settings[ id ] = value;
    },


    saveAllSettings: function( values ) {
        this.settings = values;
    },

    saveSections: function( values ) {
        this.sections = values;
    },

    saveInputs: function( values ) {
        this.inputs = values;
    },


    // get functions
    getSections: function() {
        return this.sections;
    },

    getInputs: function() {
        return this.inputs;
    },

    getAllValues: function() {
        return this.settings;
    }





});


ViewDispatcher.register( function( action ) {

    switch( action.actionType ) {

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