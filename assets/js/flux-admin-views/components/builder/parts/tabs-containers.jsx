var React = require('react');
var TabContainer = require('./tab-container.jsx');

var TabsContainers = React.createClass({

    propTypes: {
        tabList: React.PropTypes.array, // list of tabs
        activeTab: React.PropTypes.string // Active Tab
    },

    renderContainers: function( tab, i ) {
        return(
            <TabContainer
                key={tab.id}
                tabId={tab.id}
                activeTab={this.props.activeTab}

                />
        );
    },

    render: function () {

        var containers = this.props.tabList.map( this.renderContainers, this );

        return (
            <div className="gv-tabs__container">
                {containers}
            </div>
        );
    }
});

module.exports = TabsContainers;