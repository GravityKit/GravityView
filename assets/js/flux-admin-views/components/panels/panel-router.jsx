var React = require('react');
var Panel = require('./panel.jsx');
var ViewConstants = require('../../constants/view-constants.js');
var ViewActions = require('../../actions/view-actions.js');
var PanelStore = require('../../stores/panel-store.js');

var PanelRouter = React.createClass({

    getState: function() {
        return {
            currentPanel: PanelStore.getActivePanel(), // which panel id is open
        };
    },

    getInitialState: function() {
        return this.getState();
    },

    /**
     * Panel Store communications
     */
    onStoreChange: function() {
        this.setState( this.getState() );
    },

    componentDidMount: function() {
        PanelStore.addChangeListener( this.onStoreChange );
    },

    componentWillUnmount: function() {
        PanelStore.removeChangeListener( this.onStoreChange );
    },



    render: function() {

        return (
           <div>
               <Panel isOpen={this.state.currentPanel === ViewConstants.PANEL_SETTINGS } isSubPanel={false} title="View Settings">
                   <ul className="gv-panel__list">
                       <li className="gv-panel__list-fields">
                           <input id="gv-formfield-1" type="checkbox" name="gv-formfield-1" value="" />
                           <label for="gv-formfield-1">Field Name 1</label>
                       </li>
                       <li className="gv-panel__list-fields">
                           <input id="gv-formfield-2" type="checkbox" name="gv-formfield-2" value="" />
                           <label for="gv-formfield-2">Field Name 2</label>
                       </li>
                   </ul>
               </Panel>
           </div>
        );
    }


});

module.exports = PanelRouter;