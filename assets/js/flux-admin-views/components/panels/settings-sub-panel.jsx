var React = require('react');
var Panel = require('./panel.jsx');

var InputNumber = require('./inputs/input-number.jsx');
var InputCheckbox = require('./inputs/input-checkbox.jsx');
var InputText = require('./inputs/input-text.jsx');
var InputSelect = require('./inputs/input-select.jsx');
var InputHidden = require('./inputs/input-hidden.jsx');
var InputRadio = require('./inputs/input-radio.jsx');
var InputTextarea = require('./inputs/input-textarea.jsx');

var ViewConstants = require('../../constants/view-constants.js');
var ViewActions = require('../../actions/view-actions.js');


var SettingsSubPanel = React.createClass({

    propTypes: {
        returnPanel: React.PropTypes.string, // holds the panel ID when going back
        currentPanel: React.PropTypes.string, // the current active panel
        settingsValues: React.PropTypes.object // holds the settings values
    },

    getInitialState: function() {
        return {
            sections: this.getConvertedSections(),  // holds the settings sections ( loaded via js wp_localize_script )
            inputs: gravityview_view_settings.settings_inputs, // holds the settings inputs ( loaded via js wp_localize_script )
        };
    },

    /**
     * Converts the section array into an object
     * @returns object
     */
    getConvertedSections: function() {
        var newSections = {},
            sections = gravityview_view_settings.settings_sections;

        for ( var i = 0, len = sections.length; i < len; i++) {
            newSections[ sections[i].id ] = sections[i];
        }

        return newSections;
    },

    /**
     * Check if this panel is visible
     * @returns {boolean}
     */
    isPanelVisible: function() {
        return this.props.currentPanel !== ViewConstants.PANEL_SETTINGS && this.props.returnPanel === ViewConstants.PANEL_SETTINGS;
    },

    /**
     * Calculate the Panel Title (main and sub-panel)
     * @return string
     */
    renderTitle: function() {
        if ( this.isPanelVisible() ) {
            var sectionID = this.props.currentPanel.replace( 'settings_', '' );
            return this.state.sections[ sectionID ].title;
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

        var inputField = null;

        switch ( item.type ) {

            case 'number':
                inputField = (
                    <InputNumber args={item} values={this.props.settingsValues} handleChange={this.handleChange} />
                );
                break;

            case 'checkbox':
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

        return(
            <div key={item.id} id={item.id}>
                {inputField}
            </div>
        );

    },

    renderPanelContent: function() {

        if ( ! this.isPanelVisible() ) {
            return null;
        }

        var sectionID = this.props.currentPanel.replace( 'settings_', '' );
        var inputs = this.state.inputs[ sectionID ];

        return inputs.map(  this.renderInputs, this );
    },

    render: function() {

        return (
            <Panel isVisible={this.isPanelVisible()} returnPanel={this.props.returnPanel} title={this.renderTitle()}>

                {this.renderPanelContent()}

            </Panel>
        );
    }


});

module.exports = SettingsSubPanel;