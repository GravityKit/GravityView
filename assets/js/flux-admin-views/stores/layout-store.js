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

        var index = this.findIndex( rows, pointer );

        rows.splice( index, 0, newRow );
        this.layout[ context ]['rows'] = rows;
    },

    /**
     * Build a new row object
     * @param type
     * @returns {{atts: {id: string, class: string, style: string}, columns: Array, row_id: *}}
     */
    buildRowStructure: function( type ) {
        var row = {
            'atts': { 'id': '', 'class': '', 'style': '' },
            'columns': [],
            'row_id': this.uniqid()
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
    },
    /**
     * Find the array index where the id == row_id property
     * @param rows
     * @param id
     * @returns {number}
     */
    findIndex: function( rows, id ) {
        for ( var i = 0; i < rows.length; i++ ) {
            if ( rows[i]['row_id'] === id ) {
                return i;
            }
        }
        return 0;
    },

    /**
     * Create a unique ID similar to PHP uniqid
     * Thanks to https://gist.github.com/larchanka/7080820
     * todo: move to common functions
     * @param pr
     * @param en
     * @returns {*}
     */
    uniqid: function ( pr, en ) {
        var pr = pr || '', en = en || false, result;

        this.seed = function (s, w) {
            s = parseInt(s, 10).toString(16);
            return w < s.length ? s.slice(s.length - w) : (w > s.length) ? new Array(1 + (w - s.length)).join('0') + s : s;
        };

        result = pr + this.seed(parseInt(new Date().getTime() / 1000, 10), 8) + this.seed(Math.floor(Math.random() * 0x75bcd15) + 1, 5);

        if (en) result += (Math.random() * 10).toFixed(8).toString();

        return result;
    },





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