var React = require('react');
var Row = require('./row.jsx');

var ViewConstants = require('../../../constants/view-constants');
var ViewActions = require('../../../actions/view-actions.js');

var Rows = React.createClass({

    propTypes: {
        tabId: React.PropTypes.string, // active tab
        type: React.PropTypes.string, // widget, field
        zone: React.PropTypes.string, // for the widgets, 'above' or 'below'
        data: React.PropTypes.array, // Layout Data, just the rows array
        activeItem: React.PropTypes.string
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
                activeItem={this.props.activeItem}
            />

        );

    },

    handleFirstRowAdd: function(e) {
        e.preventDefault();
        var args = {
                'type': this.props.type,
                'context': this.props.tabId,
                'zone': this.props.zone,
                'row': null
            };

        ViewActions.openPanel( ViewConstants.PANEL_ROW_ADD, false, args );
    },

    renderEmptyRows: function() {

        return(
            <div className="gv-grid__col-12">
                <a onClick={this.handleFirstRowAdd} title={gravityview_i18n.button_row_add}>+ {gravityview_i18n.button_row_add}</a>
            </div>
        );

    },


    render: function() {

        if ( !this.props.data || this.props.data.length <= 0 ) {
            return this.renderEmptyRows();
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
