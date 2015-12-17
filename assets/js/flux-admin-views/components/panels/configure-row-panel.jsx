var React = require('react');
var Panel = require('./panels/panel.jsx');

var ViewCommon = require('../../api/view-common.js');

var ViewConstants = require('../../constants/view-constants.js');
var ViewActions = require('../../actions/view-actions.js');


var ConfigureRowPanel = React.createClass({

    propTypes: {
        returnPanel: React.PropTypes.string, // holds the panel ID when going back
        currentPanel: React.PropTypes.string, // the current active panel
        extraArgs: React.PropTypes.object, // the layout pointer indicating to which row does this configuration belongs
        layoutData: React.PropTypes.object,
    },

    rowSettings: null, // hold the row settings object

    /**
     * Handler for input on change
     * @param e
     */
    handleChange: function( e ) {
        var id = e.target.getAttribute( 'id' ),
            value = e.target.value;
        ViewActions.updateRowSetting( id, value, this.props.extraArgs );
    },

    renderFields: function( item, i ) {
        return(
            <fieldset key={item.id} id={item.id}>
                <label htmlFor={item}>{item}</label>
                <input onChange={this.handleChange} id={item} type="text" value={this.rowSettings[ item ]}  />
            </fieldset>
        );
    },

    renderSettings: function() {
        var context = this.props.extraArgs['context'],
            rows = this.props.layoutData[ context ]['rows'],
            index = ViewCommon.findRowIndex( rows, this.props.extraArgs['pointer'] );

        this.rowSettings = this.props.layoutData[ context ]['rows'][ index ]['atts'];

        return Object.keys( this.rowSettings ).map( this.renderFields, this );

    },

    render: function() {

        var isPanelVisible = ( this.props.currentPanel === ViewConstants.PANEL_ROW_SETTINGS ) || ( this.props.returnPanel === ViewConstants.PANEL_ROW_SETTINGS );

        if( !isPanelVisible ) {
            return null;
        }

        return (

            <Panel isVisible={isPanelVisible} returnPanel={this.props.returnPanel} title={gravityview_i18n.panel_config_row_title}>
                <div className="gv-panel__forms">
                    {this.renderSettings()}
                </div>
            </Panel>

        );
    }


});

module.exports = ConfigureRowPanel;