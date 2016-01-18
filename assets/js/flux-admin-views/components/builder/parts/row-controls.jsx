var React = require('react');
var ViewConstants = require('../../../constants/view-constants');
var ViewActions = require('../../../actions/view-actions.js');


var RowControls = React.createClass({


    propTypes: {
        type: React.PropTypes.string,
        rowId: React.PropTypes.string,
        zone: React.PropTypes.string, // In case of widget area
        tabId: React.PropTypes.string // Tab where it is rendered
    },

    handleClick: function(e) {
        e.preventDefault();
        var action = e.target.getAttribute('data-action'),
            rowArgs = {
                'type': this.props.type,
                'context': this.props.tabId,
                'zone': this.props.zone,
                'row': this.props.rowId
            };

        if( 'add' === action ) {
            ViewActions.openPanel( ViewConstants.PANEL_ROW_ADD, false, rowArgs );
        } else if( 'remove' === action ) {
            ViewActions.removeRow( rowArgs );
        } else if( 'settings' === action ) {
            ViewActions.openPanel( ViewConstants.PANEL_ROW_SETTINGS, false, rowArgs );
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
