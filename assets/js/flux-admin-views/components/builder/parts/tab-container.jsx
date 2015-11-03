var React = require('react');
var Rows = require('./rows.jsx');
var FieldAreas = require('./field-areas.jsx');

var TabContainer = React.createClass({

    getInitialState: function() {
        return {
            activeRow: null // which row id is triggering actions


        };
    },

    render: function () {

        var displayContainer = { display: this.props.isCurrent ? 'block': 'none' };

        // test data
        var rowsData = [
            { 'id': 'abc', 'columns': ['1-1'], 'fields': '' },
            { 'id': 'efg', 'columns': ['1-3', '2-3'], 'fields': '' }
        ];

        return(
            <div style={displayContainer}>

                <h3>{gravityview_i18n.widgets_title_above} <small>{gravityview_i18n.widgets_label_above}</small></h3>
                <Rows
                    tab={this.props.id}
                    type="widget"
                    zone="header"
                    data={rowsData}
                    />


                <h3>{gravityview_i18n.fields_title_multiple} <small>{gravityview_i18n.fields_label_multiple}</small></h3>
                <FieldAreas />


                <h3>{gravityview_i18n.widgets_title_below} <small>{gravityview_i18n.widgets_label_below}</small></h3>
                <Rows tab={this.props.id} type="widget" zone="footer" data={rowsData} />

            </div>
        );
    }


});

module.exports = TabContainer;