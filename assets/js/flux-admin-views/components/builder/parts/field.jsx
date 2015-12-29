var React = require('react');

var Field = React.createClass({

    propTypes: {
        data: React.PropTypes.object, // field detail object
        onClickItemSettings: React.PropTypes.func, // Callback to process Field Settings
        onClickItemRemove: React.PropTypes.func // Callback to remove the field from layout
    },

    render: function() {

        var label = this.props.data['gv_settings']['custom_label'] || this.props.data['gv_settings']['label'],
            dataField = JSON.stringify(this.props.data);

        return (
            <div className="gv-view-field" id={this.props.data.id} data-field={dataField}>
                <a onClick={this.props.onClickItemSettings} title={gravityview_i18n.field_settings} className="gv-view-field__settings" data-icon="&#xe009;"><span className="gv-screen-reader-text">{gravityview_i18n.field_settings}</span></a>
                <span className="gv-view-field__description">{label}</span>
                <a onClick={this.props.onClickItemRemove} title={gravityview_i18n.field_remove} className="gv-view-field__remove" data-icon="&#xe006;"><span className="gv-screen-reader-text">{gravityview_i18n.field_remove}</span></a>
            </div>
        );
    }


});

module.exports = Field;
