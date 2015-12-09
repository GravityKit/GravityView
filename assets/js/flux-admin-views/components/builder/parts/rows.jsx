var React = require('react');
var RowControls = require('./row-controls.jsx');

var Rows = React.createClass({

    propTypes: {
        tabId: React.PropTypes.string, // active tab
        type: React.PropTypes.string, // widgets, fields
        zone: React.PropTypes.string, // for the widgets, 'header' or 'footer'
        data: React.PropTypes.array
    },

    renderAddLabel: function() {
        if( this.props.type === 'widget' ) {
            return gravityview_i18n.widgets_add;
        }
        return gravityview_i18n.fields_add;
    },

    renderColumn: function( column , i ) {
        var areaClass = 'gv-grid__col-' + column.colspan;

        return(
            <div key={i} className={areaClass}>
                <div className="gv-grid__droppable-area">
                    <a title={this.renderAddLabel()}>+ {this.renderAddLabel()}</a>
                </div>
            </div>
        );
    },

    renderRow: function( row, i ) {

        var areas = row.columns.map( this.renderColumn, this );

        return (
            <div key={i} className="gv-grid gv-grid__has-row-controls">
                {areas}
                <RowControls
                    rowId={i}
                    tabId={this.props.tabId}
                />
            </div>
        );

    },


    render: function() {

        if( !this.props.data || this.props.data.length === 0 ) {
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
