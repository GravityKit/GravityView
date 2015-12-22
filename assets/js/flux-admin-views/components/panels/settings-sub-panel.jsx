var React = require('react');
var Panel = require('./panels/panel.jsx');

var InputNumber = require('./inputs/input-number.jsx');
var InputCheckbox = require('./inputs/input-checkbox.jsx');
var InputText = require('./inputs/input-text.jsx');
var InputSelect = require('./inputs/input-select.jsx');
var InputHidden = require('./inputs/input-hidden.jsx');
var InputRadio = require('./inputs/input-radio.jsx');
var InputTextarea = require('./inputs/input-textarea.jsx');

var ViewConstants = require('../../constants/view-constants.js');
var ViewActions = require('../../actions/view-actions.js');
var ViewCommon = require('../../api/view-common.js');


var SettingsSubPanel = React.createClass({

    propTypes: {
        returnPanel: React.PropTypes.string, // holds the panel ID when going back
        currentPanel: React.PropTypes.string, // the current active panel
        settingsValues: React.PropTypes.object, // holds the settings values
        sections: React.PropTypes.array, // holds the settings sections
        inputs: React.PropTypes.object, // holds the settings inputs
    },

    /**
     * Check if this panel is visible
     * @returns {boolean}
     */
    isPanelVisible: function() {
        return this.props.currentPanel !== ViewConstants.PANEL_SETTINGS &&
            this.props.currentPanel !== 'settings_source' &&
            this.props.returnPanel === ViewConstants.PANEL_SETTINGS;
    },

    /**
     * Calculate the Panel Title (main and sub-panel)
     * @return string
     */
    renderTitle: function() {
        if ( this.isPanelVisible() ) {
            var sections = ViewCommon.convertSections( this.props.sections );
            var sectionID = this.props.currentPanel.replace( 'settings_', '' );
            return sections[ sectionID ].label;
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

    renderInputs: function( item, i ) {

        var inputField = null,
            leftLabel = null;

        switch ( item.type ) {

            case 'number':
                inputField = (
                    <InputNumber args={item} values={this.props.settingsValues} handleChange={this.handleChange} />
                );
                break;

            case 'checkbox':
                leftLabel = (
                    <label>{item.left_label}</label>
                );
                inputField = (
                    <InputCheckbox args={item} values={this.props.settingsValues} handleChange={this.handleCheckChange} />
                );
                break;

            case 'hidden':
                inputField = (
                    <InputHidden args={item} values={this.props.settingsValues} />
                );
                break;
/*
            case 'radio':
                inputField = (
                    <InputRadio args={item} values={this.props.settingsValues} handleChange={this.handleChange} />
                );
                break;
*/
            case 'select':
                inputField = (
                    <InputSelect args={item} values={this.props.settingsValues} handleChange={this.handleChange} />
                );
                break;

            case 'text':
                inputField = (
                    <InputText args={item} values={this.props.settingsValues} handleChange={this.handleChange} />
                );
                break;

           case 'textarea':
                inputField = (
                    <InputTextarea args={item} values={this.props.settingsValues} handleChange={this.handleChange} />
                );
                break;
        }
        //todo: change class name (li)
        return(
            <fieldset key={item.id} id={item.id}>
                {leftLabel}
                {inputField}
            </fieldset>
        );

    },

    renderSettingsContent: function() {

        if ( ! this.isPanelVisible() ) {
            return null;
        }

        var sectionId = this.props.currentPanel.replace( 'settings_', '' );
        var inputs = this.props.inputs[ sectionId ];

        return inputs.map(  this.renderInputs, this );
    },

    render: function() {

        return (
            <Panel isVisible={this.isPanelVisible()} returnPanel={this.props.returnPanel} title={this.renderTitle()}>
                <div className="gv-panel__forms">
                    {this.renderSettingsContent()}
                </div>
            </Panel>
        );
    }


});

module.exports = SettingsSubPanel;