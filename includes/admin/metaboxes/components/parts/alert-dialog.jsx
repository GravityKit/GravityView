import React from 'react';

var AlertDialog = React.createClass({

    renderDialog: function() {

        var cancelButton = (
            <button className="button button-secondary gv-button-left-margin" onClick={this.props.cancelAction}>{gravityview_i18n.button_cancel}</button>
        );

        var continueButton = (
            <button className="button button-secondary gv-button-left-margin" data-change-value={this.props.changedValue} onClick={this.props.continueAction}>{gravityview_i18n.button_continue}</button>
        );

        return(
            <p>
                {this.props.message}
                {cancelButton}
                {continueButton}
            </p>
        );
    },

    render: function () {
        if (!this.props.isOpen) return null;
        return (
            <div className="gv-alert-message notice notice-warning">
                {this.renderDialog()}
            </div>
        );
    }
});

export default AlertDialog;


