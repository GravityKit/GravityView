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
     * Holds the attributes object of the Row that is under configuration (Configure Row Panel)
     */
    activeRowSettings: {},

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

    getActiveRowSettings: function() {
        return this.activeRowSettings;
    },

    setActiveRowSettings: function( vector ) {
        var rows = this.getRows( vector.type, vector );
        var index = ViewCommon.findRowIndex( rows, vector.row );

        this.activeRowSettings = rows[ index ]['atts'];
    },


    setFieldsSections: function( sections ) {
        this.fieldsSections = sections;
    },

    getFieldsSections: function() {
        return this.fieldsSections;
    },


    setFieldsList: function( list ) {
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

    setRows: function( type, vector, rows ) {
        if ( 'field' === type ) {
            this.layout[ vector.context ]['rows'] = rows;
        } else if( 'widget' === type ) {
            this.layout[ vector.context ]['widgets'][ vector.zone ]['rows'] = rows;
        }
    },


    /**
     * Add row to the layout structure
     * @param vector object Layout vector: {context, type, zone, row}
     * @param colStruct string Column structure of the row
     */
    addRow: function( vector, colStruct ) {
        var rows = this.getRows( vector.type, vector );
        var newRow = this.buildRowStructure( colStruct );

        // add row
        if( vector.hasOwnProperty('row') && null !== vector.row ) {
            var index = ViewCommon.findRowIndex( rows, vector.row );
            index++; // add the new row below
            rows.splice( index, 0, newRow );
        } else {
            rows.push( newRow );
        }

        // update layout
        this.setRows( vector.type, vector, rows );
    },

    /**
     * Remove row off the layout structure
     * @param vector Object Vector containing Context, Type, Zone, Row
     */
    removeRow: function( vector ) {
        var rows = this.getRows( vector.type, vector );
        var index = ViewCommon.findRowIndex( rows, vector.row );
        rows.splice( index, 1 );
        this.setRows( vector.type, vector, rows );
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
     * @param vector object (type, context, zone, row)
     * @param key string Setting key
     * @param value string Setting value
     */
    updateRow: function( vector, key, value ) {
        var rows = this.getRows( vector.type, vector );
        var index = ViewCommon.findRowIndex( rows, vector.row );

        rows[ index ]['atts'][ key ] = value;

        // update layout
        this.setRows( vector.type, vector, rows );

        // update active row settings
        this.activeRowSettings = rows[ index ]['atts'];
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


        // When Panel Open is triggered, prepare some Layout Data
        case ViewConstants.PANEL_OPEN:

            if ( ViewConstants.PANEL_ROW_SETTINGS !== action.panelId ) {
                break;
            }
            // If Configure Row Panel is opened, set the Active Row Settings storage
            LayoutStore.setActiveRowSettings( action.extraArgs );
            LayoutStore.emitChange();
            break;



        case ViewConstants.LAYOUT_ADD_ROW:
            LayoutStore.addRow( action.vector, action.struct );
            LayoutStore.emitChange();
            break;

        case ViewConstants.LAYOUT_DEL_ROW:
            LayoutStore.removeRow( action.vector );
            LayoutStore.emitChange();
            break;

        case ViewConstants.LAYOUT_SET_ROW:
            LayoutStore.updateRow( action.vector, action.key, action.value );
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