var React = require('react');
var Row = require('./row.jsx');


var Rows = React.createClass({

    propTypes: {
        tabId: React.PropTypes.string, // active tab
        type: React.PropTypes.string, // widget, field
        zone: React.PropTypes.string, // for the widgets, 'above' or 'below'
        data: React.PropTypes.array // Layout Data, just the rows array
    },

    getDefaultProps: function() {
        return {
            zone: null
        };
    },

    renderRow: function( row, i ) {

        return (

            <Row
                key={row.id}
                zone={this.props.zone}
                tabId={this.props.tabId}
                rowId={row.id}
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
