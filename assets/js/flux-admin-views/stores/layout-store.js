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


    setFieldsList: function( context, list ) {
        this.fieldsList[ context ] = list;
    },

    getFieldsList: function( context ) {
        if( null === context ) {
            return this.fieldsList;
        }
        return this.fieldsList[ context ];
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
     * Remove Field from layout
     * @param context
     * @param row
     * @param col
     * @param field string Field id
     */
    removeField: function( context, row, col, field ) {
        var rowI = ViewCommon.findRowIndex( this.layout[ context ]['rows'], row ),
            fields = this.layout[ context ]['rows'][ rowI ]['columns'][ col ]['fields'],
            fieldI = ViewCommon.findRowIndex( fields, field );

        fields.splice( fieldI, 1 );
        this.layout[ context ]['rows'][ rowI ]['columns'][ col ]['fields'] = fields;
    },


    /**
     * Add Field to Layout
     * @param context
     * @param row
     * @param col
     * @param field Object Field
     */
    addField: function( context, row, col, index, field ) {

        var rowI = ViewCommon.findRowIndex( this.layout[ context ]['rows'], row ),
            fields = this.layout[ context ]['rows'][ rowI ]['columns'][ col ]['fields'];

        // add the new field to layout
        if( null === index ) {
            fields.push( field );
        } else {
            fields.splice( index, 0, field );
        }

        this.layout[ context ]['rows'][ rowI ]['columns'][ col ]['fields'] = fields;
    },

    /**
     * Add the field settings values to the layout (gv_settings object)
     * @param context
     * @param row
     * @param col
     * @param field object Field details (field_id, form_id, field_type, ...)
     * @param settings
     */
    addFieldSettingsValues: function( context, row, col, field, settings ) {
        var rowI = ViewCommon.findRowIndex( this.layout[ context ]['rows'], row ),
            fields = this.layout[ context ]['rows'][ rowI ]['columns'][ col ]['fields'];

        var index = ViewCommon.findRowIndex( fields, field['id'] );

        // replace the existent gv_settings object by the new one
        fields[ index ]['gv_settings'] = settings;

        this.layout[ context ]['rows'][ rowI ]['columns'][ col ]['fields'] = fields;

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

        case ViewConstants.LAYOUT_DEL_ROW:
            LayoutStore.removeRow( action.context, action.pointer );
            LayoutStore.emitChange();
            break;

        case ViewConstants.LAYOUT_SET_ROW:
            LayoutStore.updateRow( action.context, action.pointer, action.key, action.value );
            LayoutStore.emitChange();
            break;

        case ViewConstants.LAYOUT_DEL_FIELD:
            LayoutStore.removeField( action.context, action.row, action.col, action.field );
            LayoutStore.emitChange();
            break;

        case ViewConstants.LAYOUT_ADD_FIELD:

            // manipulate field object to the right format
            action.field['gv_settings'] = { 'label': action.field['field_label'] };
            delete action.field['field_label'];

            LayoutStore.addField( action.context, action.row, action.col, null, action.field );
            LayoutStore.emitChange();
            break;

        case ViewConstants.LAYOUT_MOV_FIELD:
            var source = action.source,
                target = action.target;
            LayoutStore.removeField( source.context, source.row, source.col, action.item['id'] );
            LayoutStore.addField( target.context, target.row, target.col, target.index, action.item );
            LayoutStore.emitChange();
            break;

        case ViewConstants.UPDATE_FIELD_SETTINGS:

            var args = action.values['pointer'],
                settings = action.values['settings'];

            LayoutStore.addFieldSettingsValues( args['context'], args['row'], args['col'], args['field'], settings );
            LayoutStore.emitChange();
            break;


        // Add field panel
        case ViewConstants.UPDATE_FIELDS_SECTIONS:
            LayoutStore.setFieldsSections( action.values );
            LayoutStore.emitChange();
            break;

        case ViewConstants.UPDATE_FIELDS_LIST:
            LayoutStore.setFieldsList( action.context, action.values );
            LayoutStore.emitChange();
            break;



    }

    return true; // Needed for Flux promise resolution

});



module.exports = LayoutStore;