var React = require('react');
var RowControls = require('./row-controls.jsx');
var RowColumn = require('./row-column.jsx');

var Row = React.createClass({

    propTypes: {
        tabId: React.PropTypes.string, // active tab
        rowId: React.PropTypes.string, // row ID
        type: React.PropTypes.string, // widgets, fields
        data: React.PropTypes.object // Layout Data, just the row array
    },

    renderColumn: function( column , i ) {

        return(
            <RowColumn
                key={i}
                tabId={this.props.tabId}
                rowId={this.props.rowId}
                colId={i}
                type={this.props.type}
                data={column}
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
                    tabId={this.props.tabId}
                    rowId={this.props.rowId}
                />
            </div>
        );
    }


});

module.exports = Row;
