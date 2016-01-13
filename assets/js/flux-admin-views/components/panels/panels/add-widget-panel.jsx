var React = require('react');
var Panel = require('./panel.jsx');
var PanelContentMenu = require('./panel-content-menu.jsx');

var ViewConstants = require('../../../constants/view-constants.js');
var ViewActions = require('../../../actions/view-actions.js');


var AddWidgetPanel = React.createClass({

    propTypes: {
        returnPanel: React.PropTypes.string, // holds the panel ID when going back
        currentPanel: React.PropTypes.string, // the current active panel
        extraArgs: React.PropTypes.object, // the layout vector (context, zone, row, col)
        widgets: React.PropTypes.object
    },


    handleClick: function( e ) {
        e.preventDefault();
        var field_id = e.target.getAttribute( 'data-next-panel' ).replace( 'widgets_', '' );
        var fieldsList = this.getActiveFieldsList();
        var fieldDetails = ViewCommon.getItemDetailsById( fieldsList, field_id );

        var fieldArgs = {
            'type': 'widget',
            'vector': {
                'context': this.props.extraArgs['context'],
                'row': this.props.extraArgs['row'],
                'col': this.props.extraArgs['col']
            },
            'field': {
                'id': ViewCommon.uniqid(),
                'field_id': field_id,
                'field_type': fieldDetails['type'],
                'form_id': fieldDetails['form_id'],
                'field_label': fieldDetails['label']
            }
        };

        ViewActions.addField( fieldArgs );
    },

    render: function() {

        var isPanelVisible = ( this.props.currentPanel === ViewConstants.PANEL_WIDGET_ADD );

        if ( isPanelVisible ) {
            var widgetsList = this.getActiveFieldsList();
        }

        return (
            <Panel isVisible={isPanelVisible} returnPanel={this.props.returnPanel} title={gravityview_i18n.panel_add_widgets}>
                <PanelContentMenu panelPrefix="widgets" menuItems={widgetsList} handleClick={this.handleClick} />
            </Panel>
        );
    }

});

module.exports = AddFieldPanel;