var React = require('react');
var Field = require('./field.jsx');
var ViewConstants = require('../../../constants/view-constants');
var ViewActions = require('../../../actions/view-actions.js');

var DropTarget = require('react-dnd').DropTarget;


var columnTarget = {
    drop: function ( props, monitor ) {

        // if this target column has fields in it, handle drop on the field target.
        if( props.data['fields'].length > 0 ) {
            return;
        }
        var pointer = { context: props.tabId, row: props.rowId, col: props.colId, index: null };
        var item = monitor.getItem();
        ViewActions.moveField( item.data, item.source, pointer );
    }
};

function collect(connect, monitor) {
    return {
        connectDropTarget: connect.dropTarget(),
        isOver: monitor.isOver()
    };
}

var RowColumn = React.createClass({

    propTypes: {
        type: React.PropTypes.string, // type of item
        data: React.PropTypes.object, // Column object details
        tabId: React.PropTypes.string, // tab id
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
                tabId={this.props.tabId}
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

        return connectDropTarget(
            <div className={areaClass}>
                <div className="gv-grid__droppable-area">
                    {fields}
                    <a onClick={this.handleFieldAdd} title={this.renderAddLabel()}>+ {this.renderAddLabel()}</a>
                </div>
            </div>
        );
    }


});

module.exports = DropTarget( ViewConstants.TYPE_FIELD, columnTarget, collect )(RowColumn);
