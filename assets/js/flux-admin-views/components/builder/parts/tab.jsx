var React = require('react');

var Tab = React.createClass({

    propTypes: {
        key: React.PropTypes.string,
        id: React.PropTypes.string,
        label: React.PropTypes.string,
        tabClass: React.PropTypes.string,
        activeClass: React.PropTypes.string,
        iconClass: React.PropTypes.string,
        isCurrent: React.PropTypes.bool,
        changeTab:React.PropTypes.func
    },

    handleClick: function(e) {
        e.preventDefault();
        this.props.changeTab( this.props.id );
    },

    renderIcon: function() {
        if( !this.props.iconClass ) { return null; }
        return(
            <i className={'dashicons dashicons-' + this.props.iconClass}></i>
        );
    },

    render: function () {


        var tabClass = this.props.tabClass;
        tabClass += this.props.isCurrent ? ' '+ this.props.activeClass : '';

        return(
            <a onClick={this.handleClick} className={tabClass}>
                {this.renderIcon()}
                {this.props.label}
            </a>
        );
    }

});

module.exports = Tab;