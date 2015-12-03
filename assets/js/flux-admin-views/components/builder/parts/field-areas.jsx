var React = require('react');
var RowControls = require('./row-controls.jsx');


var FieldAreas = React.createClass({

    propTypes: {

        tabId: React.PropTypes.string // Active Tab
    },

    render: function() {
        return (
            <div className="gv-grid gv-grid__has-row-controls">
                <RowControls tabId={this.props.tabId} />
            </div>
        );
    }


});

module.exports = FieldAreas;
