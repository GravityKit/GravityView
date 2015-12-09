var ViewDispatcher = require('../dispatcher/view-dispatcher.js');
var ViewConstants = require('../constants/view-constants.js');
var EventEmitter = require('events').EventEmitter;
var assign = require('object-assign');

var CHANGE_EVENT = 'change';


/**
 * Store the builder layout configuration ( rows, columns, fields...)
 */
var LayoutStore = assign( {}, EventEmitter.prototype, {

    /**
     * Active tab
     */
    activeTab: 'directory',

    /**
     * Holds the complete layout configuration
     *  - directory, single, edit, export
     *
     */
    layout: {},

    /**
     *
     */
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

    /**
     * Set active tab
     * @param tab string
     */
    changeTab: function( tab ) {
        this.activeTab = tab;
    },

    /**
     * Set active tab
     * @param tab string
     */
    getActiveTab: function() {
        return this.activeTab;
    },


    setLayout: function( layout ) {
        this.layout = layout;
    },

    /**
     *
     * @returns {Array}
     */
    getLayout: function() {
        return this.layout;
    },




    /**
     * Add row to the layout structure
     * @param context string Directory, Single, Edit, Export
     * @param pointer string Reference Row ID indicating where the new row should be inserted ( array index )
     * @param colStruct string Column structure of the row
     */
    addRow: function( context, pointer, colStruct ) {
        console.log( 'context:'+context+' pointer:'+pointer+' struct:'+colStruct);
        var rows = this.layout[ context ]['rows'],
            newRow = this.buildRowStructure( colStruct );

        rows.splice( pointer, 0, newRow );
        this.layout[ context ]['rows'] = rows;
    },

    buildRowStructure: function( type ) {
        var row = {
            'atts': { 'id': '', 'class': '', 'style': '' },
            'columns': []
        };
        var cols = type.split('-');

        cols.forEach( function( elem, i, array ) {
            row['columns'][ i ] = {
                'colspan': elem,
                'atts': { 'id': '', 'class': '', 'style': '' },
                'fields': []
            };
        } );

        return row;
    }





});


ViewDispatcher.register( function( action ) {

    switch( action.actionType ) {

        case ViewConstants.UPDATE_LAYOUT_ALL:
            LayoutStore.setLayout( action.values );
            LayoutStore.emitChange();
            break;

        case ViewConstants.CHANGE_TAB:
            LayoutStore.changeTab( action.tab );
            LayoutStore.emitChange();
            break;

        case ViewConstants.LAYOUT_ADD_ROW:
            LayoutStore.addRow( action.context, action.pointer, action.struct );
            LayoutStore.emitChange();
            break;


    }

    return true; // Needed for Flux promise resolution

});



module.exports = LayoutStore;