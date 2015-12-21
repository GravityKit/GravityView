var React = require('react');
var Panel = require('./panel.jsx');
var PanelContentMenu = require('./panel-content-menu.jsx');

var ViewConstants = require('../../../constants/view-constants.js');
var ViewActions = require('../../../actions/view-actions.js');
var ViewCommon = require('../../../api/view-common.js');

var AddFieldSubPanel = React.createClass({

    propTypes: {
        returnPanel: React.PropTypes.string, // holds the panel ID when going back
        currentPanel: React.PropTypes.string, // the current active panel
        extraArgs: React.PropTypes.object, // the layout pointer (context, row, col)
        sections: React.PropTypes.array,
        fields: React.PropTypes.object
    },

    getCurrentSection: function() {
        return this.props.currentPanel.replace('fields_', '');
    },

    getActiveFieldsList: function() {
        var sectionId = this.getCurrentSection();
        return this.props.fields[ this.props.extraArgs['context'] ][ sectionId ];
    },

    /**
     * Check if this panel is visible
     * @returns {boolean}
     */
    isPanelVisible: function() {
        return this.props.currentPanel !== ViewConstants.PANEL_FIELD_ADD && this.props.returnPanel === ViewConstants.PANEL_FIELD_ADD;
    },

    /**
     * Calculate the Panel Title (main and sub-panel)
     * @return string
     */
    renderTitle: function() {
        if ( this.isPanelVisible() ) {
            var sections = ViewCommon.convertSections( this.props.sections );
            var sectionId = this.getCurrentSection();
            return sections[ sectionId ].label;
        }
        return null;
    },

    handleClick: function( e ) {
        e.preventDefault();
        var field_id = e.target.getAttribute( 'data-next-panel' ).replace( 'fields_', '' );
        var fieldsList = this.getActiveFieldsList();
        var fieldDetails = ViewCommon.getItemDetailsById( fieldsList, field_id );

        var fieldArgs = {
            'context': this.props.extraArgs['context'],
            'row': this.props.extraArgs['row'],
            'col': this.props.extraArgs['col'],
            'field': {
                'id': ViewCommon.uniqid(),
                'field_id': field_id,
                'field_type': fieldDetails['type'],
                'form_id': fieldDetails['form_id'],
                'field_label': fieldDetails['label']
            }
        };

        ViewActions.addField( fieldArgs );
    },

    render: function() {

        if ( this.isPanelVisible() ) {
            var fieldsList = this.getActiveFieldsList();
        }

        return (
            <Panel isVisible={this.isPanelVisible()} returnPanel={this.props.returnPanel} title={this.renderTitle()}>
                <PanelContentMenu panelPrefix="fields" menuItems={fieldsList} handleClick={this.handleClick}>
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

module.exports = AddFieldSubPanel;