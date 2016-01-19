var React = require('react');
var RowControls = require('./row-controls.jsx');
var RowColumn = require('./row-column.jsx');

var Row = React.createClass({

    propTypes: {
        type: React.PropTypes.string, // widget, field
        tabId: React.PropTypes.string, // active tab
        rowId: React.PropTypes.string, // row ID
        zone: React.PropTypes.string, // for the widgets, 'above' or 'below'
        data: React.PropTypes.object, // Layout Data, just the row array
        activeItem: React.PropTypes.string
    },

    renderColumn: function( column , i ) {

        // todo: sanitize the layout structure before arriving here. Check the convert function.
        if( !column.hasOwnProperty('fields') ) {
            column['fields'] = [];
        }

        return(
            <RowColumn
                key={i}
                tabId={this.props.tabId}
                zone={this.props.zone}
                rowId={this.props.rowId}
                colId={i}
                type={this.props.type}
                data={column}
                activeItem={this.props.activeItem}
            />
        );
    },

    render: function() {

        if( this.props.data['columns'].length <= 0 ) {
            return null;
        }

        var areas = this.props.data['columns'].map( this.renderColumn, this );

        return (
            <div className="gv-grid gv-grid__has-row-controls">
                {areas}
                <RowControls
                    type={this.props.type}
                    tabId={this.props.tabId}
                    zone={this.props.zone}
                    rowId={this.props.rowId}
                />
            </div>
        );
    }


});

module.exports = Row;
