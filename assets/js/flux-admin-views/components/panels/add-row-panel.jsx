var React = require('react');
var Panel = require('./panel.jsx');

var ViewConstants = require('../../constants/view-constants.js');
var ViewActions = require('../../actions/view-actions.js');


var AddRowPanel = React.createClass({

    propTypes: {
        returnPanel: React.PropTypes.string, // holds the panel ID when going back
        currentPanel: React.PropTypes.string, // the current active panel
        extraArgs: React.PropTypes.object, // the layout pointer indicating where we need to insert the row
    },

    renderOptions: function( item, i ) {
        return(
            <li key={i} className="gv-panel__category"  id={i}>
                <a data-option={item.struct} title={item.label} onClick={this.handleClick}>{item.label}</a>
            </li>
        );
    },

    /**
     * Handler for menu items click
     * @param e
     */
    handleClick: function(e) {
        e.preventDefault();
        var struct = e.target.getAttribute( 'data-option' );
        ViewActions.addRow( this.props.extraArgs['context'], this.props.extraArgs['pointer'], struct );
        ViewActions.closePanel();
    },

    render: function() {

        var rowOptions = [
            { 'struct': '12', 'label': 'single column' },
            { 'struct': '6-6', 'label': 'two equal columns' },
            { 'struct': '3-9', 'label': '1/3 - 2/3' },
            { 'struct': '9-3', 'label': '2/3 - 1/3' },
            { 'struct': '4-4-4', 'label': '4-4-4' },
            { 'struct': '6-3-3', 'label': '6-3-3' },
            { 'struct': '3-6-3', 'label': '3-6-3' },
            { 'struct': '3-3-6', 'label': '3-3-6' },
            { 'struct': '3-3-3-3', 'label': '3-3-3-3' }
        ];

        var options = rowOptions.map( this.renderOptions, this );

        var isPanelVisible = ( this.props.currentPanel === ViewConstants.PANEL_ADD_ROW ) || ( this.props.returnPanel === ViewConstants.PANEL_ADD_ROW );

        return (

            <Panel isVisible={isPanelVisible} returnPanel={this.props.returnPanel} title={gravityview_i18n.panel_add_row_title}>
                <ul className="gv-panel__list">
                    {options}
                </ul>
            </Panel>

        );
    }


});

module.exports = AddRowPanel;