var React = require('react');

var PanelContentMenu = React.createClass({

    propTypes: {
        menuItems: React.PropTypes.array, // list of menu items
        handleClick: React.PropTypes.func // Callback to change the panel content
    },

    renderMenuItems: function( item, i ) {
        return(
            <li className="gv-panel__category" key={item.id} id={item.id}>
                <a data-next-panel={'settings_'+item.id} title={item.title} onClick={this.props.handleClick}>{item.title}</a>
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
                {menuItems}
            </ul>
        );
    }

});

module.exports = PanelContentMenu;