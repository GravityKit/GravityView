import React from 'react';
import Tab from './tab.jsx';

var Tabs = React.createClass({

    renderTabs: function( tab, i ) {
        return(
            <Tab
                key={tab.id}
                id={tab.id}
                changeTab={this.props.changeTab}
                label={tab.label}
                tabClass="nav-tab"
                activeClass="nav-tab-active"
                iconClass={tab.icon}
                isCurrent={(this.props.currentTab === tab.id)}
            />
        );
    },

    render: function () {

        var tabsLinks = this.props.tabList.map( this.renderTabs, this );

        return(
            <nav className="nav-tab-wrapper gv-tab-wrapper">
                {tabsLinks}
            </nav>
        );
    }


});

export default Tabs;