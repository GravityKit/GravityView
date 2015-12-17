var React = require('react');
var ReactTooltip = require('react-tooltip');

var AddFieldPanel = require('./panels/add-field-panel.jsx');
var AddFieldSubPanel = require('./panels/add-field-sub-panel.jsx');
var ConfigureFieldPanel = require('./panels/configure-field-panel.jsx');
var AddRowPanel = require('./add-row-panel.jsx');
var ConfigureRowPanel = require('./configure-row-panel.jsx');
var SettingsMenuPanel = require('./settings-menu-panel.jsx');
var SettingsSubPanel = require('./settings-sub-panel.jsx');

var ViewConstants = require('../../constants/view-constants.js');
var ViewActions = require('../../actions/view-actions.js');
var PanelStore = require('../../stores/panel-store.js');
var SettingsStore = require('../../stores/settings-store.js');
var LayoutStore = require('../../stores/layout-store.js');

var PanelRouter = React.createClass({

    getState: function() {
        return {
            // generic for the panels
            currentPanel: PanelStore.getActivePanel(), // which panel id is open
            returnPanel: PanelStore.getReturnPanel(), // when the go back panel control links
            extraPanelArgs: PanelStore.getExtraArgs(), // if panel has extra arguments (like information of the place to insert a row)

            // Used on the Settings panel only
            settingsValues: SettingsStore.getAllValues(),
            settingsInputs: SettingsStore.getInputs(),
            settingsSections: SettingsStore.getSections(),

            // Used on the Configure Row panel
            layout: LayoutStore.getLayout(),

            fieldsSections: LayoutStore.getFieldsSections(),
            fieldsList: LayoutStore.getFieldsList(),

        };
    },

    getInitialState: function() {
        return this.getState();
    },

    /**
     * Panel Store communications
     */
    onStoreChange: function() {
        this.setState( this.getState() );
    },

    componentDidMount: function() {
        PanelStore.addChangeListener( this.onStoreChange );
        SettingsStore.addChangeListener( this.onStoreChange );
        LayoutStore.addChangeListener( this.onStoreChange );

        // Trigger Flux to get the initial settings
        ViewActions.fetchSettingsSections();
        ViewActions.fetchSettingsInputs();
        ViewActions.fetchSettingsAllValues();

        // todo: get the form and the context dynamic
        ViewActions.fetchFieldsSections();
        ViewActions.fetchFieldsList( [254], 'directory' );

    },

    componentWillUnmount: function() {
        PanelStore.removeChangeListener( this.onStoreChange );
        SettingsStore.removeChangeListener( this.onStoreChange );
        LayoutStore.removeChangeListener( this.onStoreChange );
    },

    render: function() {

        // <ConfigureFieldPanel returnPanel={this.state.returnPanel} currentPanel={this.state.currentPanel} extraArgs={this.state.extraPanelArgs} layoutData={this.state.layout} />


        return (
           <div>
               <AddFieldPanel returnPanel={this.state.returnPanel} currentPanel={this.state.currentPanel} extraArgs={this.state.extraPanelArgs} sections={this.state.fieldsSections} fields={this.state.fieldsList} />
               <AddFieldSubPanel returnPanel={this.state.returnPanel} currentPanel={this.state.currentPanel} extraArgs={this.state.extraPanelArgs} sections={this.state.fieldsSections} fields={this.state.fieldsList} />
               <AddRowPanel returnPanel={this.state.returnPanel} currentPanel={this.state.currentPanel} extraArgs={this.state.extraPanelArgs} />
               <ConfigureRowPanel returnPanel={this.state.returnPanel} currentPanel={this.state.currentPanel} extraArgs={this.state.extraPanelArgs} layoutData={this.state.layout} />
               <SettingsMenuPanel returnPanel={this.state.returnPanel} currentPanel={this.state.currentPanel} sections={this.state.settingsSections} />
               <SettingsSubPanel returnPanel={this.state.returnPanel} currentPanel={this.state.currentPanel} settingsValues={this.state.settingsValues} sections={this.state.settingsSections} inputs={this.state.settingsInputs} />
               <ReactTooltip html={true} place="bottom" type="info" effect="float" />
           </div>
        );
    }


});

module.exports = PanelRouter;