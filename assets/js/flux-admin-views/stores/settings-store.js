var ViewDispatcher = require('../dispatcher/view-dispatcher.js');
var ViewConstants = require('../constants/view-constants.js');
var EventEmitter = require('events').EventEmitter;
var assign = require('object-assign');

var CHANGE_EVENT = 'change';


/**
 * Store about the panel status, content, and more
 */
var SettingsStore = assign( {}, EventEmitter.prototype, {

    /**
     * Holds the View Settings inputs
     */
    inputs: null,

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

    // Get the Active Panel ID
    getAllSettings: function() {
        return this.settings;
    }

});


ViewDispatcher.register( function( action ) {

    switch( action.actionType ) {

        case ViewConstants.UPDATE_ALL_SETTINGS:
            SettingsStore.saveAllSettings( action.settingsValues );
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