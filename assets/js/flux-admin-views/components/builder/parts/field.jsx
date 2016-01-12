var React = require('react');
var ReactDOM = require('react-dom');
var ViewConstants = require('../../../constants/view-constants');
var ViewActions = require('../../../actions/view-actions.js');
var DragSource = require('react-dnd').DragSource;
var DropTarget = require('react-dnd').DropTarget;

var fieldSource = {
    beginDrag: function ( props ) {
        // Return the data describing the dragged item
        var vector = { context: props.tabId, zone: props.zone, row: props.rowId, col: props.colId, index: props.order };
        return { type: props.type, data: props.data, source: vector, original: vector };
    },

    isDragging: function( props, monitor ) {
        return props.data.id === monitor.getItem().data.id
    },

    endDrag: function( props, monitor, component ) {
        // If dragged item was dropped outside a valid area, restore original position.
        if ( monitor.didDrop() ) {
            return;
        }

        const item = monitor.getItem();

        ViewActions.moveField( item.type, item.data, item.source, item.original );
    }

};

var fieldTarget = {

    canDrop: function ( props, monitor ) {
        var item = monitor.getItem();
        return props.type === item.type;
    },

    hover( props, monitor, component ) {

        if( !monitor.canDrop() ) {
            return;
        }

        const item = monitor.getItem(),
            dragVector = item.source;
        const hoverVector = { context: props.tabId, zone: props.zone, row: props.rowId, col: props.colId, index: props.order };

        // Don't replace items with themselves
        if ( dragVector === hoverVector || item.data.id === props.data.id ) {
            return;
        }

        // Time to actually perform the action (it will be opacity=0 until drag is over)
        ViewActions.moveField( item.type, item.data, dragVector, hoverVector );

        // Note: we're mutating the monitor item here!
        monitor.getItem().source = hoverVector;
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
        type: React.PropTypes.string, // type of item
        tabId: React.PropTypes.string, // tab id
        zone: React.PropTypes.string, // for the widgets, 'above' or 'below'
        rowId: React.PropTypes.string, // row id
        colId: React.PropTypes.number, // Column order on the row
        order: React.PropTypes.number,
        data: React.PropTypes.object, // Field detail object
    },


    handleFieldSettings: function(e) {
        e.preventDefault();

        var fieldArgs = {
            'type': this.props.type,
            'vector': {
                'context': this.props.tabId,
                'zone': this.props.zone,
                'row': this.props.rowId,
                'col': this.props.colId,
            },
            'field': this.props.data
        };

        ViewActions.editFieldSettings( fieldArgs );
    },

    handleFieldRemove: function(e) {
        e.preventDefault();

        var fieldArgs = {
            'type': this.props.type,
            'vector': {
                'context': this.props.tabId,
                'zone': this.props.zone,
                'row': this.props.rowId,
                'col': this.props.colId,
            },
            'field': this.props.data.id
        };

        ViewActions.removeField( fieldArgs );
    },

    renderSettingsLabel: function() {
        return this.props.type === 'field' ? gravityview_i18n.field_settings : gravityview_i18n.widget_settings;
    },

    renderRemoveLabel: function() {
        return this.props.type === 'field' ? gravityview_i18n.field_remove : gravityview_i18n.widget_remove;
    },

    render: function() {

        var connectDragSource = this.props.connectDragSource,
            connectDropTarget = this.props.connectDropTarget,
            isDragging = this.props.isDragging;

        const opacity = isDragging ? 0.2 : 1;

        var label = this.props.data['gv_settings']['custom_label'] || this.props.data['gv_settings']['label'];

        return connectDragSource( connectDropTarget(
            <div className="gv-view-field" style={{opacity}}>
                <a onClick={this.handleFieldSettings} title={this.renderSettingsLabel()} className="gv-view-field__settings" data-icon="&#xe009;"><span className="gv-screen-reader-text">{this.renderSettingsLabel()}</span></a>
                <span className="gv-view-field__description">{label}</span>
                <a onClick={this.handleFieldRemove} title={this.renderRemoveLabel()} className="gv-view-field__remove" data-icon="&#xe006;"><span className="gv-screen-reader-text">{this.renderRemoveLabel()}</span></a>
            </div>
        ));
    }


});

module.exports =    DragSource( ViewConstants.TYPE_FIELD, fieldSource, collectSource ) (
                        DropTarget( ViewConstants.TYPE_FIELD, fieldTarget, collectTarget )(Field)
                    );
