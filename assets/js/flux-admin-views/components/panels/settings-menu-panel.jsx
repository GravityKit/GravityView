var React = require('react');
var Panel = require('./panels/panel.jsx');
var PanelContentMenu = require('./panels/panel-content-menu.jsx');

var ViewConstants = require('../../constants/view-constants.js');
var ViewActions = require('../../actions/view-actions.js');


var SettingsMenuPanel = React.createClass({

    propTypes: {
        returnPanel: React.PropTypes.string, // holds the panel ID when going back
        currentPanel: React.PropTypes.string, // the current active panel
        sections: React.PropTypes.array // the menu sections
    },

    /**
     * Handler for menu items click
     * @param e
     */
    handleSectionClick: function(e) {
        e.preventDefault();
        ViewActions.openPanel( e.target.getAttribute( 'data-next-panel' ), this.props.currentPanel );
    },

    render: function() {

        var isPanelVisible = ( this.props.currentPanel === ViewConstants.PANEL_SETTINGS ) || ( this.props.returnPanel === ViewConstants.PANEL_SETTINGS );

        return (

            <Panel isVisible={isPanelVisible} returnPanel={this.props.returnPanel} title={gravityview_i18n.panel_settings_title}>
                <PanelContentMenu panelPrefix="settings" menuItems={this.props.sections} handleClick={this.handleSectionClick} />
            </Panel>

        );
    }


});

module.exports = SettingsMenuPanel;