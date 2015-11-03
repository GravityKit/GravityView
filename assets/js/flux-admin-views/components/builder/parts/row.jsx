var React = require('react');
var RowControls = require('./row-controls.jsx');

var Row = React.createClass({





    render: function() {

        return (
            <div className="gv-grid gv-grid__has-row-controls">




                <div className="gv-grid__col-1-1">
                    <div className="gv-grid__droppable-area">
                        <a  title="{gravityview_i18n.widgets_add}">+ {gravityview_i18n.widgets_add}</a>
                    </div>
                </div>


                <RowControls   />
            </div>
        );
    }


});

module.exports = Row;
