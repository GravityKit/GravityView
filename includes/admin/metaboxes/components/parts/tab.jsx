import React from 'react';

var Tab = React.createClass({

    handleClick: function() {
        this.props.changeTab( this.props.id );
    },

    render: function () {
        var iconClass = 'dashicons dashicons-' + this.props.iconClass;

        var tabClass = 'nav-tab';
        tabClass += this.props.isCurrent ? ' nav-tab-active' : '';

        return(
            <a onClick={this.handleClick} className={tabClass}>
                <i className={iconClass}></i>
                {this.props.label}
            </a>
        );
    }


});

export default Tab;