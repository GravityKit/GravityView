var React = require('react');
var Tab = require('./tab.jsx');

var ViewConstants = require('../../../constants/view-constants.js');

var Tabs = React.createClass({

    propTypes: {
        tabList: React.PropTypes.array,
        changeTab:React.PropTypes.func,
        activeTab: React.PropTypes.string, // Active Tab
        handleOpenSettings: React.PropTypes.func,
        currentPanel: React.PropTypes.string,
    },

    renderTabs: function( tab, i ) {
        return(
            <Tab
                key={tab.id}
                id={tab.id}
                changeTab={this.props.changeTab}
                label={tab.label}
                tabClass="gv-tabs"
                activeClass="gv-tabs__active"
                iconClass=""
                isCurrent={(this.props.activeTab === tab.id)}
            />
        );
    },

    renderSettingsButton: function() {

        var buttonClass = 'gv-button gv-button__secondary';

        if ( ViewConstants.PANEL_SETTINGS === this.props.currentPanel ) {
            buttonClass += ' gv-panel__is-open';
        }

        return (
            <div onClick={this.props.handleOpenSettings} className="gv-view__config-settings">
                <a className={buttonClass} title={gravityview_i18n.button_settings} data-icon="&#xe009;"><span>{gravityview_i18n.button_settings}</span></a>
            </div>
        );
    },

    render: function () {

        var tabsLinks = this.props.tabList.map( this.renderTabs, this );

        return(
            <nav className="gv-tabs__group">
                {tabsLinks}
                {this.renderSettingsButton()}
            </nav>
        );
    }


});

module.exports = Tabs;