var React = require('react');
var Panel = require('./panel.jsx');

var InputNumber = require('../inputs/input-number.jsx');
var InputCheckbox = require('../inputs/input-checkbox.jsx');
var InputText = require('../inputs/input-text.jsx');
var InputSelect = require('../inputs/input-select.jsx');
var InputHidden = require('../inputs/input-hidden.jsx');
var InputRadio = require('../inputs/input-radio.jsx');
var InputTextarea = require('../inputs/input-textarea.jsx');

var ViewConstants = require('../../../constants/view-constants.js');
var ViewActions = require('../../../actions/view-actions.js');


var ConfigureFieldPanel = React.createClass({

    propTypes: {
        returnPanel: React.PropTypes.string, // holds the panel ID when going back
        currentPanel: React.PropTypes.string, // the current active panel
        extraArgs: React.PropTypes.object, // A vector containing the "pointer" (context, row, col, field) and the "settings" inputs ( defined at ViewApi.getFieldSettings() )

    },

    /**
     * Holds the current field settings values
     */
    settingsValues: null,

    handleChange: function( e ) {
        var id = e.target.getAttribute( 'id' );
        this.settingsValues[ id ] = e.target.value;
        ViewActions.updateFieldSetting( this.props.extraArgs['pointer'], this.settingsValues );
    },

    handleCheckChange: function( e ) {
        var id = e.target.getAttribute( 'id' );

        this.settingsValues[ id ] = e.target.checked;
        ViewActions.updateFieldSetting( this.props.extraArgs['pointer'], this.settingsValues );
    },

    shouldRenderInput: function ( item ) {

        if( 'only_loggedin_cap' !== item.id ) {
            return true;
        }
        // if 'only_loggedin' not checked
        return !!Number( this.settingsValues['only_loggedin'] );

    },

    renderInputs: function( item, i ) {

        var inputField = null,
            leftLabel = null;

        if( !this.shouldRenderInput( item ) ) {
            return;
        }

        switch ( item.type ) {

            case 'number':
                inputField = (
                    <InputNumber args={item} values={this.settingsValues} handleChange={this.handleChange} />
                );
                break;

            case 'checkbox':
                leftLabel = (
                    <label>{item.left_label}</label>
                );
                inputField = (
                    <InputCheckbox args={item} values={this.settingsValues} handleChange={this.handleCheckChange} />
                );
                break;

            case 'hidden':
                inputField = (
                    <InputHidden args={item} values={this.settingsValues} />
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
                    <InputSelect args={item} values={this.settingsValues} handleChange={this.handleChange} />
                );
                break;

            case 'text':
                inputField = (
                    <InputText args={item} values={this.settingsValues} handleChange={this.handleChange} />
                );
                break;

            case 'textarea':
                inputField = (
                    <InputTextarea args={item} values={this.settingsValues} handleChange={this.handleChange} />
                );
                break;
        }

        return(
            <fieldset key={item.id} id={item.id}>
                {leftLabel}
                {inputField}
            </fieldset>
        );

    },

    renderSettings: function() {
        var inputs = this.props.extraArgs['settings'];

        return inputs.map( this.renderInputs, this );
    },

    render: function() {

        var isPanelVisible = ( this.props.currentPanel === ViewConstants.PANEL_FIELD_SETTINGS );

        if( !isPanelVisible ) {
            return null;
        }

        // Update the current field settings values
        this.settingsValues = this.props.extraArgs['pointer']['field']['gv_settings'];

        return (

            <Panel isVisible={isPanelVisible} returnPanel={this.props.returnPanel} title={gravityview_i18n.panel_config_field_title}>
                <div className="gv-panel__forms">
                    {this.renderSettings()}
                </div>
            </Panel>

        );
    }


});

module.exports = ConfigureFieldPanel;


