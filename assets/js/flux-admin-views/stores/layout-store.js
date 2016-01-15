var ViewDispatcher = require('../dispatcher/view-dispatcher.js');
var ViewConstants = require('../constants/view-constants.js');

var ViewCommon = require('../api/view-common.js');

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
     * Holds the fields list sections
     */
    fieldsSections: null,

    /**
     * Holds the available list of fields (depending on the context)
     */
    fieldsList: { 'directory': null, 'single': null, 'edit': null, 'export': null },

    /**
     * Holds the available list of widgets (depending on the context)
     */
    widgetsList: [],

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


    setFieldsSections: function( sections ) {
        this.fieldsSections = sections;
    },

    getFieldsSections: function() {
        return this.fieldsSections;
    },


    setFieldsList: function( list ) {
        console.log( 'setFieldsList' );
        console.log( list );
        this.fieldsList = list;
    },

    getFieldsList: function() {
        return this.fieldsList;
    },

    setWidgetsList: function( list ) {
        this.widgetsList = list;
    },

    getWidgetsList: function() {
        return this.widgetsList;
    },

    /** Helpers */

    /**
     * Returns the array of rows for a specific vector on layout
     * @param type string Item type: widget or field
     * @param vector object Vector to a position in layout: { context, zone, row, col, index }
     * @returns {*}
     */
    getRows: function( type, vector ) {
        if ( 'field' === type ) {
            return this.layout[ vector.context ]['rows'];
        } else if( 'widget' === type ) {
            return this.layout[ vector.context ]['widgets'][ vector.zone ]['rows'];
        }
    },

    setFields: function( type, vector, fields ) {
        if ( 'field' === type ) {
            this.layout[ vector.context ]['rows'][ vector.rowI ]['columns'][ vector.col ]['fields'] = fields;
        } else if( 'widget' === type ) {
            this.layout[ vector.context ]['widgets'][ vector.zone ]['rows'][ vector.rowI ]['columns'][ vector.col ]['fields'] = fields;
        }
    },


    /**
     * Add row to the layout structure
     * @param context string Directory, Single, Edit, Export
     * @param pointer string Reference Row ID indicating where the new row should be inserted ( array index )
     * @param colStruct string Column structure of the row
     */
    addRow: function( context, pointer, colStruct ) {

        var rows = this.layout[ context ]['rows'],
            newRow = this.buildRowStructure( colStruct );

        var index = ViewCommon.findRowIndex( rows, pointer );

        rows.splice( index, 0, newRow );
        this.layout[ context ]['rows'] = rows;
    },

    /**
     * Remove row off the layout structure
     * @param context string Directory, Single, Edit, Export
     * @param pointer string Reference Row ID indicating where the new row should be inserted ( array index )
     */
    removeRow: function( context, pointer ) {

        var rows = this.layout[ context ]['rows'];
        var index = ViewCommon.findRowIndex( rows, pointer );

        rows.splice( index, 1 );
        this.layout[ context ]['rows'] = rows;
    },

    /**
     * Build a new row object
     * @param type
     * @returns {{atts: {id: string, class: string, style: string}, columns: Array, id: *}}
     */
    buildRowStructure: function( type ) {
        var row = {
            'atts': { 'id': '', 'class': '', 'style': '' },
            'columns': [],
            'id': ViewCommon.uniqid()
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
     * Update a row setting
     * @param context string Directory, Single, Edit, Export
     * @param pointer string Row ID
     * @param key string Setting key
     * @param value string Setting value
     */
    updateRow: function( context, pointer, key, value ) {
        var rows = this.layout[ context ]['rows'];
        var index = ViewCommon.findRowIndex( rows, pointer );

        this.layout[ context ]['rows'][ index ]['atts'][ key ] = value;

    },

    /**
     * Remove Widget/Field from layout
     * @param type string Item type: widget or field
     * @param vector object Layout vector: {context, row, col, index} for fields or {context, zone, row, col, index} for widgets
     * @param field_id string Field id
     */
    removeField: function( type, vector, field_id ) {
        var rowsList = this.getRows( type, vector );

        vector.rowI = ViewCommon.findRowIndex( rowsList, vector.row );

        var fields = rowsList[ vector.rowI ]['columns'][ vector.col ]['fields'],
            fieldI = ViewCommon.findRowIndex( fields, field_id );

        fields.splice( fieldI, 1 );

        this.setFields( type, vector, fields );
    },


    /**
     * Add Widget/Field to Layout
     * @param type string Item type: widget or field
     * @param vector object Layout vector: {context, row, col, index} for fields or {context, zone, row, col, index} for widgets
     * @param field Object Field details
     */
    addField: function( type, vector, field ) {
        var rowsList = this.getRows( type, vector );

        vector.rowI = ViewCommon.findRowIndex( rowsList, vector.row );

        var fields = rowsList[ vector.rowI ]['columns'][ vector.col ]['fields'];

        // add the new field to layout
        if( vector.hasOwnProperty('index') ) {
            fields.splice( vector.index, 0, field );
        } else {
            fields.push( field );
        }

        this.setFields( type, vector, fields );
    },

    /**
     * Add the field settings values to the layout (gv_settings object)
     * @param type string Item type: widget or field
     * @param vector
     * @param field object Field details (field_id, form_id, field_type, ...)
     * @param settings
     */
    addFieldSettingsValues: function( type, vector, field, settings ) {
        var rowsList = this.getRows( type, vector );

        vector.rowI = ViewCommon.findRowIndex( rowsList, vector.row );

        var fields = rowsList[ vector.rowI ]['columns'][ vector.col ]['fields'];

        var index = ViewCommon.findRowIndex( fields, field['id'] );

        // replace the existent gv_settings object by the new one
        fields[ index ]['gv_settings'] = settings;

        this.setFields( type, vector, fields );
    },



});


ViewDispatcher.register( function( action ) {

    console.log( action );

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

        case ViewConstants.LAYOUT_DEL_ROW:
            LayoutStore.removeRow( action.context, action.pointer );
            LayoutStore.emitChange();
            break;

        case ViewConstants.LAYOUT_SET_ROW:
            LayoutStore.updateRow( action.context, action.pointer, action.key, action.value );
            LayoutStore.emitChange();
            break;

        case ViewConstants.LAYOUT_DEL_FIELD:
            LayoutStore.removeField( action.type, action.vector, action.field );
            LayoutStore.emitChange();
            break;

        case ViewConstants.LAYOUT_ADD_FIELD:

            // manipulate field object to the right format
            action.field['gv_settings'] = { 'label': action.field['field_label'] };
            delete action.field['field_label'];

            LayoutStore.addField( action.type, action.target, action.field );
            LayoutStore.emitChange();
            break;

        case ViewConstants.LAYOUT_MOV_FIELD:
            LayoutStore.removeField( action.type, action.source, action.item['id'] );
            LayoutStore.addField( action.type, action.target, action.item );
            LayoutStore.emitChange();
            break;

        case ViewConstants.UPDATE_FIELD_SETTINGS:
            LayoutStore.addFieldSettingsValues(
                action.values['type'],
                action.values['vector'],
                action.values['field'],
                action.values['settings']
            );
            LayoutStore.emitChange();
            break;


        // Add field panel
        case ViewConstants.UPDATE_FIELDS_SECTIONS:
            LayoutStore.setFieldsSections( action.values );
            LayoutStore.emitChange();
            break;

        case ViewConstants.UPDATE_FIELDS_LIST:
            LayoutStore.setFieldsList( action.values );
            LayoutStore.emitChange();
            break;

        // Add widget panel
        case ViewConstants.UPDATE_WIDGETS_LIST:
            LayoutStore.setWidgetsList( action.values );
            LayoutStore.emitChange();
            break;




    }

    return true; // Needed for Flux promise resolution

});



module.exports = LayoutStore;