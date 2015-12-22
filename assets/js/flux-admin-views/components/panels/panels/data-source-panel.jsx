var React = require('react');
var Panel = require('./panel.jsx');

var ViewConstants = require('../../../constants/view-constants.js');
var ViewActions = require('../../../actions/view-actions.js');
var ViewCommon = require('../../../api/view-common.js');

var DataSourcePanel = React.createClass({

    propTypes: {
        returnPanel: React.PropTypes.string, // holds the panel ID when going back
        currentPanel: React.PropTypes.string, // the current active panel
        forms: React.PropTypes.array, // View active forms
        formsList: React.PropTypes.array, // Available forms
        sections: React.PropTypes.array, // holds the settings sections
    },

    /**
     * Check if this panel is visible
     * @returns {boolean}
     */
    isPanelVisible: function() {
        return this.props.currentPanel === 'settings_source' && this.props.returnPanel === ViewConstants.PANEL_SETTINGS;
    },

    /**
     * Calculate the Panel Title (main and sub-panel)
     * @return string
     */
    renderTitle: function() {
        if ( this.isPanelVisible() ) {
            var sections = ViewCommon.convertSections( this.props.sections );
            var sectionId = this.props.currentPanel.replace( 'settings_', '' );
            return sections[ sectionId ].label;
        }
        return null;
    },

    handleChange: function( e ) {
        var id = e.target.getAttribute( 'id' ),
            value = e.target.value;
        ViewActions.updateSetting( id, value );
    },

    handleCheckChange: function( e ) {
        var id = e.target.getAttribute( 'id' ),
            checked = e.target.checked;
        ViewActions.updateSetting( id, checked );
    },

    renderFormsList: function( item, i ) {

        return(
            <li key={'gv-form-'+item.id} className="gv-panel__list-fields">
                <input id={'gv-form-'+item.id} type="checkbox" value={item.id} />
                <label htmlFor={'gv-form-'+item.id}>{item.title}</label>
            </li>
        );
    },


    renderPanelContent: function() {

        if ( ! this.isPanelVisible() ) {
            return null;
        }

        return this.props.formsList.map(  this.renderFormsList, this );
    },

    render: function() {

        return (
            <Panel isVisible={this.isPanelVisible()} returnPanel={this.props.returnPanel} title={this.renderTitle()}>
                <div className="gv-panel__list">
                    {this.renderPanelContent()}
                </div>
            </Panel>
        );
    }


});

module.exports = DataSourcePanel;