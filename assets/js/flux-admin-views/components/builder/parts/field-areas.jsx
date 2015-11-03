var React = require('react');
var RowControls = require('./row-controls.jsx');


var FieldAreas = React.createClass({


    render: function() {

        return (
            <div className="gv-grid gv-grid__has-row-controls">
                <RowControls />
            </div>
        );
    }


});

module.exports = FieldAreas;
