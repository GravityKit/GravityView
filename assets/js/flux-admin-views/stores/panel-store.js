var ViewDispatcher = require('../dispatcher/view-dispatcher.js');
var ViewConstants = require('../constants/view-constants.js');
var EventEmitter = require('events').EventEmitter;
var assign = require('object-assign');

var CHANGE_EVENT = 'change';


/**
 * Store about the panel status, content, and more
 */
var PanelStore = assign( {}, EventEmitter.prototype, {

    /**
     * Holds the current open panel ID
     */
    activePanel: null,

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


    setActivePanel: function( id ) {
        this.activePanel = id;
    },

    // Get the Active Panel ID
    getActivePanel: function() {
        return this.activePanel;
    }

});


ViewDispatcher.register( function( action ) {

    switch( action.actionType ) {

        case ViewConstants.PANEL_OPEN:
            PanelStore.setActivePanel( action.panelId );
            PanelStore.emitChange();
            break;

    }

    return true; // Needed for Flux promise resolution

});

module.exports = PanelStore;