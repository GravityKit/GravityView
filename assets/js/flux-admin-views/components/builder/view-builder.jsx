var React = require('react');
var Tabs = require('./parts/tabs.jsx');
var TabsContainers = require('./parts/tabs-containers.jsx');
var ViewConstants = require('../../constants/view-constants.js');
var ViewActions = require('../../actions/view-actions.js');
var PanelStore = require('../../stores/panel-store.js');


var ViewBuilder = React.createClass({



    getInitialState: function() {
        return {
            currentTab: 'directory' // which tab id is active


        };
    },

    handleChangeTab: function( tabId ) {
        this.setState({ currentTab: tabId });
    },

    handleOpenSettings: function(e) {
        e.preventDefault();
        ViewActions.openPanel( ViewConstants.PANEL_SETTINGS, false );
    },



    render: function () {
        var tabs = [
            { 'id': 'directory', 'label': gravityview_i18n.tab_multiple },
            { 'id': 'single', 'label': gravityview_i18n.tab_single },
            { 'id': 'edit', 'label': gravityview_i18n.tab_edit },
            { 'id': 'export', 'label': gravityview_i18n.tab_export }
        ];



        return(
            <div className="gv-view__config">
                <Tabs tabList={tabs} changeTab={this.handleChangeTab} currentTab={this.state.currentTab} handleOpenSettings={this.handleOpenSettings} />
                <TabsContainers tabList={tabs} currentTab={this.state.currentTab} />
            </div>
        );
    }


});

module.exports = ViewBuilder;