var React = require('react');
var Panel = require('./panels/panel.jsx');

var ViewCommon = require('../../api/view-common.js');

var ViewConstants = require('../../constants/view-constants.js');
var ViewActions = require('../../actions/view-actions.js');


var ConfigureRowPanel = React.createClass({

    propTypes: {
        returnPanel: React.PropTypes.string, // holds the panel ID when going back
        currentPanel: React.PropTypes.string, // the current active panel
        extraArgs: React.PropTypes.object, // the layout vector containing {context, type, zone, row}
        rowSettings: React.PropTypes.object, // contains the attributes of the Active Row
    },


    /**
     * Handler for input on change
     * @param e
     */
    handleChange: function( e ) {
        var id = e.target.getAttribute( 'data-id' ),
            value = e.target.value;
        ViewActions.updateRowSetting( id, value, this.props.extraArgs );
    },

    renderFields: function( item, i ) {
        return(
            <fieldset key={item}>
                <label htmlFor={'row-setting-'+item}>{item}</label>
                <input onChange={this.handleChange} id={'row-setting-'+item} data-id={item} type="text" value={this.props.rowSettings[ item ]}  />
            </fieldset>
        );
    },

    renderSettings: function() {

        return Object.keys( this.props.rowSettings ).map( this.renderFields, this );
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