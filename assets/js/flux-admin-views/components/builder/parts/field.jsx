var React = require('react');
var ReactDOM = require('react-dom');
var ViewConstants = require('../../../constants/view-constants');
var ViewActions = require('../../../actions/view-actions.js');
var DragSource = require('react-dnd').DragSource;
var DropTarget = require('react-dnd').DropTarget;

var fieldSource = {
    beginDrag: function ( props ) {
        // Return the data describing the dragged item
        var pointer = { context: props.tabId, row: props.rowId, col: props.colId, index: props.order };
        var item = { data: props.data, source: pointer };
        return item;
    },

    isDragging: function( props, monitor ) {
        return props.data.id === monitor.getItem().data.id
    }

    /*endDrag: function ( props, monitor, component ) {
        if ( !monitor.didDrop() ) {
            return;
        }

        // When dropped on a compatible target, do something
        var item = monitor.getItem();
        var dropResult = monitor.getDropResult();

    }*/
};

var fieldTarget = {

    hover( props, monitor, component ) {
        const item = monitor.getItem(),
            dragPointer = item.source;
        const hoverPointer = { context: props.tabId, row: props.rowId, col: props.colId, index: props.order };

        // Don't replace items with themselves
        if ( dragPointer === hoverPointer || item.data.id === props.data.id ) {
            return;
        }

        // Time to actually perform the action (it will be opacity=0 until drag is over)
        ViewActions.moveField( item.data, dragPointer, hoverPointer );

        // Note: we're mutating the monitor item here!
        monitor.getItem().source = hoverPointer;
    }

};

function collectSource( connect, monitor ) {
    return {
        connectDragSource: connect.dragSource(),
        isDragging: monitor.isDragging()
    };
}

function collectTarget( connect, monitor ) {
    return {
        connectDropTarget: connect.dropTarget(),
        isOver: monitor.isOver()
    };
}


var Field = React.createClass({

    propTypes: {
        tabId: React.PropTypes.string, // tab id
        rowId: React.PropTypes.string, // row id
        colId: React.PropTypes.number, // Column order on the row
        order: React.PropTypes.number,
        data: React.PropTypes.object, // Field detail object
    },


    handleFieldSettings: function(e) {
        e.preventDefault();

        var fieldArgs = {
            'context': this.props.tabId,
            'row': this.props.rowId,
            'col': this.props.colId,
            'field': this.props.data
        };

        ViewActions.editFieldSettings( fieldArgs );
    },

    handleFieldRemove: function(e) {
        e.preventDefault();

        var fieldArgs = {
            'context': this.props.tabId,
            'row': this.props.rowId,
            'col': this.props.colId,
            'field': this.props.data.id
        };

        ViewActions.removeField( fieldArgs );
    },

    render: function() {

        var connectDragSource = this.props.connectDragSource,
            connectDropTarget = this.props.connectDropTarget,
            isDragging = this.props.isDragging;

        const opacity = isDragging ? 0.2 : 1;

        var label = this.props.data['gv_settings']['custom_label'] || this.props.data['gv_settings']['label'];

        return connectDragSource( connectDropTarget(
            <div className="gv-view-field" style={{opacity}}>
                <a onClick={this.handleFieldSettings} title={gravityview_i18n.field_settings} className="gv-view-field__settings" data-icon="&#xe009;"><span className="gv-screen-reader-text">{gravityview_i18n.field_settings}</span></a>
                <span className="gv-view-field__description">{label}</span>
                <a onClick={this.handleFieldRemove} title={gravityview_i18n.field_remove} className="gv-view-field__remove" data-icon="&#xe006;"><span className="gv-screen-reader-text">{gravityview_i18n.field_remove}</span></a>
            </div>
        ));
    }


});

module.exports =    DragSource( ViewConstants.TYPE_FIELD, fieldSource, collectSource ) (
                        DropTarget( ViewConstants.TYPE_FIELD, fieldTarget, collectTarget )(Field)
                    );
