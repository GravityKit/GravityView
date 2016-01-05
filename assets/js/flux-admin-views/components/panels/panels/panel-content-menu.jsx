var React = require('react');

var PanelContentMenu = React.createClass({

    propTypes: {
        menuItems: React.PropTypes.array, // list of menu items
        handleClick: React.PropTypes.func, // Callback to change the panel content
        panelPrefix: React.PropTypes.string
    },

    renderMenuItems: function( item, i ) {
        return(
            <li className="gv-panel__category" key={item.id} id={item.id}>
                <a data-next-panel={this.props.panelPrefix + '_' + item.id} title={item.label} onClick={this.props.handleClick}>{item.label}</a>
            </li>
        );
    },

    render: function() {

        if( ! this.props.menuItems ) {
            return null;
        }

        var menuItems = this.props.menuItems.map( this.renderMenuItems, this );

        return (
            <ul className="gv-panel__list">
                {this.props.children}
                {menuItems}
            </ul>
        );
    }

});

module.exports = PanelContentMenu;