var React = require('react');
var Row = require('./row.jsx');


var Rows = React.createClass({

    propTypes: {
        tabId: React.PropTypes.string, // active tab
        type: React.PropTypes.string, // widgets, fields
        zone: React.PropTypes.string, // for the widgets, 'header' or 'footer'
        data: React.PropTypes.array // Layout Data, just the rows array
    },


    renderRow: function( row, i ) {

        return (

            <Row
                key={row.id}
                tabId={this.props.tabId}
                rowId={this.props.rowId}
                type={this.props.type}
                data={row}
            />

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
