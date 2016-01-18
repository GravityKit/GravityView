var React = require('react');
var Tabs = require('./parts/tabs.jsx');
var TabsContainers = require('./parts/tabs-containers.jsx');
var ViewConstants = require('../../constants/view-constants.js');
var ViewActions = require('../../actions/view-actions.js');

var LayoutStore = require('../../stores/layout-store.js');
var PanelStore = require('../../stores/panel-store.js');

// DnD
var DragDropContext = require('react-dnd').DragDropContext;
var HTML5Backend = require('react-dnd-html5-backend');

var ViewBuilder = React.createClass({

    getState: function() {
        return {
            activeTab: LayoutStore.getActiveTab(), // which tab id is open
            layout: LayoutStore.getLayout(),
            currentPanel: PanelStore.getActivePanel(), // which panel id is open
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
        LayoutStore.addChangeListener( this.onStoreChange );
        PanelStore.addChangeListener( this.onStoreChange );

        // fetch Layout saved value
        ViewActions.fetchSavedLayout();

    },

    componentWillUnmount: function() {
        LayoutStore.removeChangeListener( this.onStoreChange );
        PanelStore.removeChangeListener( this.onStoreChange );
    },

    handleChangeTab: function( tabId ) {
        ViewActions.changeTab( tabId );
    },

    handleOpenSettings: function( e ) {
        e.preventDefault();
        if( this.state.currentPanel === ViewConstants.PANEL_SETTINGS ) {
            ViewActions.closePanel();
        } else {
            ViewActions.openPanel( ViewConstants.PANEL_SETTINGS, false );
        }

    },

    render: function () {
        console.log( this.state.layout );
        var tabs = [
            { 'id': 'directory', 'label': gravityview_i18n.tab_multiple },
            { 'id': 'single', 'label': gravityview_i18n.tab_single },
            { 'id': 'edit', 'label': gravityview_i18n.tab_edit },
            { 'id': 'export', 'label': gravityview_i18n.tab_export }
        ];

        return(
            <div className="gv-view__config">
                <Tabs
                    tabList={tabs}
                    changeTab={this.handleChangeTab}
                    activeTab={this.state.activeTab}
                    handleOpenSettings={this.handleOpenSettings}
                    currentPanel={this.state.currentPanel}
                />
                <TabsContainers tabList={tabs} activeTab={this.state.activeTab} layoutData={this.state.layout} />
            </div>
        );
    }


});

module.exports = DragDropContext(HTML5Backend)(ViewBuilder);
