var React = require('react');
var Panel = require('./panel.jsx');
var PanelContentMenu = require('./panel-content-menu.jsx');

var ViewConstants = require('../../../constants/view-constants.js');
var ViewActions = require('../../../actions/view-actions.js');
var ViewCommon = require('../../../api/view-common.js');


var AddWidgetPanel = React.createClass({

    propTypes: {
        returnPanel: React.PropTypes.string, // holds the panel ID when going back
        currentPanel: React.PropTypes.string, // the current active panel
        extraArgs: React.PropTypes.object, // the layout vector (context, zone, row, col)
        widgets: React.PropTypes.array
    },


    handleClick: function( e ) {
        e.preventDefault();

        var widget_id = e.target.getAttribute( 'data-next-panel' ).replace( 'widgets_', '' );

        var widgetDetails = ViewCommon.getItemDetailsById( this.props.widgets, widget_id );

        var widgetArgs = {
            'type': 'widget',
            'vector': {
                'context': this.props.extraArgs['context'],
                'zone': this.props.extraArgs['zone'],
                'row': this.props.extraArgs['row'],
                'col': this.props.extraArgs['col']
            },
            'field': {
                'id': ViewCommon.uniqid(),
                'widget': widget_id,
                'field_label': widgetDetails['label']
            }
        };

        ViewActions.addField( widgetArgs );
    },

    render: function() {

        var isPanelVisible = ( this.props.currentPanel === ViewConstants.PANEL_WIDGET_ADD );

        var widgetsList = [];
        if ( isPanelVisible ) {
            widgetsList = ViewCommon.getWidgetsListByContext( this.props.widgets, this.props.extraArgs['context'] );
        }

        return (
            <Panel isVisible={isPanelVisible} returnPanel={this.props.returnPanel} title={gravityview_i18n.panel_add_widgets}>
                <PanelContentMenu panelPrefix="widgets" menuItems={widgetsList} handleClick={this.handleClick} />
            </Panel>
        );
    }

});

module.exports = AddWidgetPanel;