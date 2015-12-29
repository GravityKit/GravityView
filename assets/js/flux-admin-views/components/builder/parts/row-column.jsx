var React = require('react');
var Field = require('./field.jsx');

var RowColumn = React.createClass({

    propTypes: {
        type: React.PropTypes.string, // type of item
        data: React.PropTypes.object, // Column object details
        colId: React.PropTypes.number, // Column order on the row
        onClickAddItem: React.PropTypes.func, // Add a field / widget on click
        onClickItemSettings: React.PropTypes.func, // Callback to process field/widget Settings
        onClickItemRemove: React.PropTypes.func // Callback to remove the field/widget from layout
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
                data={field}
                onClickItemSettings={this.props.onClickItemSettings}
                onClickItemRemove={this.props.onClickItemRemove}
            />
        );
    },

    render: function() {

        var areaClass = 'gv-grid__col-' + this.props.data.colspan,
            fields = null;

        if( this.props.data.fields ) {
            fields = this.props.data.fields.map( this.renderField, this );
        }

        return(
            <div className={areaClass} >
                <div className="gv-grid__droppable-area" data-column={this.props.colId}>
                    {fields}
                    <a onClick={this.props.onClickAddItem} title={this.renderAddLabel()}>+ {this.renderAddLabel()}</a>
                </div>
            </div>
        );
    }


});

module.exports = RowColumn;
