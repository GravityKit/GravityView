var React = require('react');
var RowControls = require('./row-controls.jsx');

var Rows = React.createClass({






    renderColumn: function( col , i ) {
        var areaClass = 'gv-grid__col-' + col;
        return(
            <div key={i} className={areaClass}>
                <div className="gv-grid__droppable-area">
                    <a  title="{gravityview_i18n.widgets_add}">+ {gravityview_i18n.widgets_add}</a>
                </div>
            </div>
        );
    },


    renderRow: function( row, i ) {

        var areas = row.columns.map( this.renderColumn, this );

        return (
            <div key={i} className="gv-grid gv-grid__has-row-controls" >
                {areas}
                <RowControls
                    rowId={row.id}
                />
            </div>
        );

    },


    render: function() {

        var rows = this.props.data.map( this.renderRow, this );

        return (
            <div>
                {rows}
            </div>
        );
    }


});

module.exports = Rows;
