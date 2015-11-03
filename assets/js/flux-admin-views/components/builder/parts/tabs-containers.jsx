var React = require('react');
var TabContainer = require('./tab-container.jsx');

var TabsContainers = React.createClass({

    renderContainers: function( tab, i ) {
        return(
            <TabContainer
                key={tab.id}
                id={tab.id}
                isCurrent={(this.props.currentTab === tab.id)}
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