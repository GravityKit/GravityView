var React = require('react');
var Rows = require('./rows.jsx');

var TabContainer = React.createClass({

    propTypes: {
        key: React.PropTypes.string,
        tabId:React.PropTypes.string,
        activeTab: React.PropTypes.string, // Active Tab
        layoutData: React.PropTypes.object, // just the context layout data
        activeItem: React.PropTypes.string
    },

    render: function () {
        const fieldsRows = this.props.layoutData['rows'] || [];

        var widgets = this.props.layoutData['widgets'] || {};
        var widgetsAboveRows = widgets.hasOwnProperty('above') ? widgets['above']['rows'] : [],
            widgetsBelowRows = widgets.hasOwnProperty('below') ? widgets['below']['rows'] : [];

        const displayContainer = { display: this.props.tabId === this.props.activeTab ? 'block': 'none' };

        return(
            <div style={displayContainer}>

                <h3>{gravityview_i18n.widgets_title_above} <small>{gravityview_i18n.widgets_label_above}</small></h3>
                <Rows
                    tabId={this.props.tabId}
                    type="widget"
                    zone="above"
                    data={widgetsAboveRows}
                    activeItem={this.props.activeItem}
                />


                <h3>{gravityview_i18n.fields_title_multiple} <small>{gravityview_i18n.fields_label_multiple}</small></h3>
                <Rows
                    tabId={this.props.tabId}
                    type="field"
                    data={fieldsRows}
                    activeItem={this.props.activeItem}
                />


                <h3>{gravityview_i18n.widgets_title_below} <small>{gravityview_i18n.widgets_label_below}</small></h3>
                <Rows
                    tabId={this.props.tabId}
                    type="widget"
                    zone="below"
                    data={widgetsBelowRows}
                    activeItem={this.props.activeItem}
                />

            </div>
        );
    }


});

module.exports = TabContainer;