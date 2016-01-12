var React = require('react');
var Field = require('./field.jsx');
var ViewConstants = require('../../../constants/view-constants');
var ViewActions = require('../../../actions/view-actions.js');

var DropTarget = require('react-dnd').DropTarget;

var columnTarget = {

    canDrop: function ( props, monitor ) {
        var item = monitor.getItem();
        return props.type === item.type;
    },

    drop: function ( props, monitor ) {
        const vector = { context: props.tabId, zone: props.zone, row: props.rowId, col: props.colId, index: null };
        const item = monitor.getItem();

        // if field already belongs to this drop area, don't accept it on the drop.
        if( item.source.row === vector.row && item.source.col === vector.col ) {
            return;
        }

        ViewActions.moveField( item.type, item.data, item.source, vector );
    },

    hover( props, monitor, component ) {

        if( !monitor.canDrop() ) {
            return;
        }

        // if this target column has fields in it, handle drop on the field target.
        if( props.data['fields'].length > 0 ) {
            return;
        }

        const item = monitor.getItem();
        const targetVector = { context: props.tabId, zone: props.zone, row: props.rowId, col: props.colId, index: 0 };

        // Time to actually perform the action (it will be opacity=0 until drag is over)
        ViewActions.moveField( item.type, item.data, item.source, targetVector );

        // Note: we're mutating the monitor item here!
        monitor.getItem().source = targetVector;
    }

};

function collect(connect, monitor) {
    return {
        connectDropTarget: connect.dropTarget(),
        isOver: monitor.isOver(),
        isOverCurrent: monitor.isOver({ shallow: true }),
        canDrop: monitor.canDrop(),
    };
}

var RowColumn = React.createClass({

    propTypes: {
        type: React.PropTypes.string, // type of item
        data: React.PropTypes.object, // Column object details
        tabId: React.PropTypes.string, // tab id
        zone: React.PropTypes.string, // for the widgets, 'above' or 'below'
        rowId: React.PropTypes.string, // row id
        colId: React.PropTypes.number, // Column order on the row
    },

    handleFieldAdd: function(e) {
        e.preventDefault();

        var areaArgs = {
            'context': this.props.tabId,
            'row': this.props.rowId,
            'col': this.props.colId
        };

        ViewActions.openPanel( ViewConstants.PANEL_FIELD_ADD, false, areaArgs );
    },

    renderAddLabel: function() {
        if( this.props.type === 'widget' ) {
            return gravityview_i18n.widgets_add;
        }
        return gravityview_i18n.fields_add;
    },

    renderField: function( field, i ) {

        return(
            <Field
                key={field.id}
                type={this.props.type}
                tabId={this.props.tabId}
                zone={this.props.zone}
                rowId={this.props.rowId}
                colId={this.props.colId}
                order={i}
                data={field}
            />
        );
    },

    render: function() {

        var connectDropTarget = this.props.connectDropTarget;

        var areaClass = 'gv-grid__col-' + this.props.data.colspan,
            fields = null;

        if( this.props.data.fields ) {
            fields = this.props.data.fields.map( this.renderField, this );
        }

        //todo: replace this by a css class
        var highlight = this.props.isOver && this.props.canDrop ? { border: '1px dashed #00A0D2' } : {};

        return connectDropTarget(
            <div className={areaClass}>
                <div className="gv-grid__droppable-area" style={highlight}>
                    {fields}
                    <a onClick={this.handleFieldAdd} title={this.renderAddLabel()}>+ {this.renderAddLabel()}</a>
                </div>
            </div>
        );
    }

});

module.exports = DropTarget( ViewConstants.TYPE_FIELD, columnTarget, collect )(RowColumn);
