var React = require('react');
var ViewConstants = require('../../../constants/view-constants');
var ViewActions = require('../../../actions/view-actions.js');


var RowControls = React.createClass({


    propTypes: {
        rowId: React.PropTypes.string,
        tabId: React.PropTypes.string // Tab where it is rendered
    },

    handleClick: function(e) {
        e.preventDefault();
        var action = e.target.getAttribute('data-action');
        if( 'add' === action ) {
            var argsPanel = {
                'context': this.props.tabId,
                'pointer': this.props.rowId
            };
            ViewActions.openPanel( ViewConstants.PANEL_ADD_ROW, false, argsPanel );
        }

    },

    render: function() {

        return (

            <div className="gv-row-controls">
                <div className="gv-button__group">
                    <button onClick={this.handleClick} data-action="add" className="gv-button" title={gravityview_i18n.button_row_add} data-icon="&#xe00e;"><span className="gv-screen-reader-text">{gravityview_i18n.button_row_add}</span></button>
                    <button onClick={this.handleClick} data-action="remove" className="gv-button" title={gravityview_i18n.button_row_remove} data-icon="&#xe00b;"><span className="gv-screen-reader-text">{gravityview_i18n.button_row_remove}</span></button>
                    <button onClick={this.handleClick} data-action="settings" className="gv-button" title={gravityview_i18n.button_row_settings} data-icon="&#xe009;"><span className="gv-screen-reader-text">{gravityview_i18n.button_row_settings}</span></button>
                </div>
            </div>
        );
    }


});

module.exports = RowControls;
