var React = require('react');
var Panel = require('./panel.jsx');
var PanelContentMenu = require('./panel-content-menu.jsx');

var ViewConstants = require('../../../constants/view-constants.js');
var ViewActions = require('../../../actions/view-actions.js');


var AddFieldPanel = React.createClass({

    propTypes: {
        returnPanel: React.PropTypes.string, // holds the panel ID when going back
        currentPanel: React.PropTypes.string, // the current active panel
        extraArgs: React.PropTypes.object, // the layout pointer (context, row, col)
        sections: React.PropTypes.array,
        fields: React.PropTypes.object
    },


    /**
     * Handler for menu items click
     * @param e
     */
    handleSectionClick: function(e) {
        e.preventDefault();
        ViewActions.openPanel( e.target.getAttribute( 'data-next-panel' ), this.props.currentPanel, this.props.extraArgs );
    },

    render: function() {

        var isPanelVisible = ( this.props.currentPanel === ViewConstants.PANEL_FIELD_ADD ) || ( this.props.returnPanel === ViewConstants.PANEL_FIELD_ADD );

        return (

            <Panel isVisible={isPanelVisible} returnPanel={this.props.returnPanel} title={gravityview_i18n.panel_add_fields}>
                <PanelContentMenu panelPrefix="fields" menuItems={this.props.sections} handleClick={this.handleSectionClick}>
                    <li className="gv-panel__search">
                        <input id="gv-panel__search-field" type="search" name="gv-panel__search-field" value="" placeholder={gravityview_i18n.panel_search_fields} />
                        <button id="gv-panel__search-button" title={gravityview_i18n.search} data-icon="&#xe013;">
                            <span className="gv-screen-reader-text">{gravityview_i18n.search}</span>
                        </button>
                    </li>
                </PanelContentMenu>
            </Panel>

        );
    }

});

module.exports = AddFieldPanel;