var React = require('react');
var RowControls = require('./row-controls.jsx');
var RowColumn = require('./row-column.jsx');

var ViewConstants = require('../../../constants/view-constants');
var ViewActions = require('../../../actions/view-actions.js');

var Rows = React.createClass({

    propTypes: {
        tabId: React.PropTypes.string, // active tab
        type: React.PropTypes.string, // widgets, fields
        zone: React.PropTypes.string, // for the widgets, 'header' or 'footer'
        data: React.PropTypes.array // Layout Data, just the rows array
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

        var field = JSON.parse( jQuery( e.target ).parents('.gv-view-field').attr('data-field') );

        var fieldArgs = {
            'context': this.props.tabId,
            'row': jQuery( e.target ).parents('div[data-row]').attr('data-row'),
            'col': jQuery( e.target ).parents('div[data-column]').attr('data-column'),
            'field': field
        };

        ViewActions.editFieldSettings( fieldArgs );
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

    renderColumn: function( column , i ) {

        return(
            <RowColumn
                key={i}
                type={this.props.type}
                data={column}
                colId={i}
                onClickAddItem={this.handleFieldAdd}
                onClickItemSettings={this.handleFieldSettings}
                onClickItemRemove={this.handleFieldRemove}
            />
        );
    },

    renderRow: function( row, i ) {

        if( row['columns'].length <= 0 ) {
            return null;
        }

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

        if ( !this.props.data || this.props.data.length <= 0 ) {
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
