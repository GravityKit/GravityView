var React = require('react');
var RowControls = require('./row-controls.jsx');

var ViewConstants = require('../../../constants/view-constants');
var ViewActions = require('../../../actions/view-actions.js');

var Rows = React.createClass({

    propTypes: {
        tabId: React.PropTypes.string, // active tab
        type: React.PropTypes.string, // widgets, fields
        zone: React.PropTypes.string, // for the widgets, 'header' or 'footer'
        data: React.PropTypes.array
    },

    handleFieldAdd: function(e) {
        e.preventDefault();

        var areaArgs = {
            'context': this.props.tabId,
            'row': jQuery( e.target ).parents('div[data-row]').attr('data-row'),
            'col': jQuery( e.target ).parents('div[data-column]').attr('data-column')
        };

        ViewActions.openPanel( ViewConstants.PANEL_FIELD_ADD, false, areaArgs );
    },

    handleFieldSettings: function(e) {
        e.preventDefault();

        var fieldArgs = {
            'context': this.props.tabId,
            'row': jQuery( e.target ).parents('div[data-row]').attr('data-row'),
            'col': jQuery( e.target ).parents('div[data-column]').attr('data-column'),
            'field': jQuery( e.target ).parents('.gv-view-field').attr('id'),
        };

        ViewActions.openPanel( ViewConstants.PANEL_FIELD_SETTINGS, false, fieldArgs );
    },

    handleFieldRemove: function(e) {
        e.preventDefault();

        var fieldArgs = {
            'context': this.props.tabId,
            'row': jQuery( e.target ).parents('div[data-row]').attr('data-row'),
            'col': jQuery( e.target ).parents('div[data-column]').attr('data-column'),
            'field': jQuery( e.target ).parents('.gv-view-field').attr('id'),
        };

        ViewActions.removeField( fieldArgs );
    },

    renderAddLabel: function() {
        if( this.props.type === 'widget' ) {
            return gravityview_i18n.widgets_add;
        }
        return gravityview_i18n.fields_add;
    },

    renderFields: function( field, i ) {

        var label = field['gv_settings']['custom_label'] || field['gv_settings']['label'];

        return(
            <div key={field.id} className="gv-view-field" id={field.id} >
                <a onClick={this.handleFieldSettings} title={gravityview_i18n.field_settings} className="gv-view-field__settings" data-icon="&#xe009;"><span className="gv-screen-reader-text">{gravityview_i18n.field_settings}</span></a>
                <span className="gv-view-field__description">{label}</span>
                <a onClick={this.handleFieldRemove} title={gravityview_i18n.field_remove} className="gv-view-field__remove" data-icon="&#xe006;"><span className="gv-screen-reader-text">{gravityview_i18n.field_remove}</span></a>
            </div>
        );
    },

    renderColumn: function( column , i ) {

        var areaClass = 'gv-grid__col-' + column.colspan,
            fields = null;

        if( column.fields ) {
            fields = column.fields.map( this.renderFields, this );
        }

        return(
            <div key={i} className={areaClass} >
                <div className="gv-grid__droppable-area" data-column={i}>
                    {fields}
                    <a onClick={this.handleFieldAdd} title={this.renderAddLabel()}>+ {this.renderAddLabel()}</a>
                </div>
            </div>
        );
    },

    renderRow: function( row, i ) {

        var areas = row['columns'].map( this.renderColumn, this );

        return (
            <div key={row.id} className="gv-grid gv-grid__has-row-controls" data-row={row.id}>
                {areas}
                <RowControls
                    rowId={row.id}
                    tabId={this.props.tabId}
                />
            </div>
        );

    },


    render: function() {

        if ( !this.props.data ) {
            return null;
        }

        var rows = this.props.data.map( this.renderRow, this );

        return (
            <div>
                {rows}
            </div>
        );
    }


});

module.exports = Rows;
