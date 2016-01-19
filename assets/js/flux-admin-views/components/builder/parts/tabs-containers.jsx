var React = require('react');
var TabContainer = require('./tab-container.jsx');

var TabsContainers = React.createClass({

    propTypes: {
        tabList: React.PropTypes.array, // list of tabs
        activeTab: React.PropTypes.string,
        layoutData: React.PropTypes.object,
        activeItem: React.PropTypes.string
    },

    renderContainers: function( tab, i ) {
        var contextLayoutData = this.props.layoutData[ tab.id ] || {};

        return(
            <TabContainer
                key={tab.id}
                tabId={tab.id}
                activeTab={this.props.activeTab}
                layoutData={contextLayoutData}
                activeItem={this.props.activeItem}
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