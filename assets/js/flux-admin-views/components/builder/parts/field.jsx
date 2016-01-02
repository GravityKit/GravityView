var React = require('react');
var ViewConstants = require('../../../constants/view-constants');
var ViewActions = require('../../../actions/view-actions.js');
var DragSource = require('react-dnd').DragSource;

var fieldSource = {
    beginDrag: function ( props ) {
        // Return the data describing the dragged item
        var pointer = { context: props.tabId, row: props.rowId, col: props.colId };
        var item = { id: props.data.id, source: pointer };
        return item;
    },

    /*endDrag: function ( props, monitor, component ) {
        if ( !monitor.didDrop() ) {
            return;
        }

        // When dropped on a compatible target, do something
        var item = monitor.getItem();
        var dropResult = monitor.getDropResult();

    }*/
};

function collect( connect, monitor ) {
    return {
        // Call this function inside render()
        // to let React DnD handle the drag events:
        connectDragSource: connect.dragSource(),
        // You can ask the monitor about the current drag state:
        isDragging: monitor.isDragging()
    };
}


var Field = React.createClass({

    propTypes: {
        tabId: React.PropTypes.string, // tab id
        rowId: React.PropTypes.string, // row id
        colId: React.PropTypes.number, // Column order on the row
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

        var connectDragSource = this.props.connectDragSource;

        var label = this.props.data['gv_settings']['custom_label'] || this.props.data['gv_settings']['label'];

        return connectDragSource(
            <div className="gv-view-field">
                <a onClick={this.handleFieldSettings} title={gravityview_i18n.field_settings} className="gv-view-field__settings" data-icon="&#xe009;"><span className="gv-screen-reader-text">{gravityview_i18n.field_settings}</span></a>
                <span className="gv-view-field__description">{label}</span>
                <a onClick={this.handleFieldRemove} title={gravityview_i18n.field_remove} className="gv-view-field__remove" data-icon="&#xe006;"><span className="gv-screen-reader-text">{gravityview_i18n.field_remove}</span></a>
            </div>
        );
    }


});

module.exports = DragSource( ViewConstants.TYPE_FIELD, fieldSource, collect )(Field);
