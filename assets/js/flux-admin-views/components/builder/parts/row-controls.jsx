var React = require('react');


var RowControls = React.createClass({

    handleClick: function(e) {
        e.preventDefault();
        var action = e.target.getAttribute('data-action');

        if( 'add' === action ) {
            //
            console.log( action );
        }




    },



    render: function() {


        return (

            <div className="gv-row-controls">
                <div className="gv-button__group">
                    <button onClick={this.handleClick} data-action="add" className="gv-button" title="{gravityview_i18n.button_row_add}" data-icon="&#xe00e;"><span className="gv-screen-reader-text">{gravityview_i18n.button_row_add}</span></button>
                    <button onClick={this.handleClick} data-action="remove" className="gv-button" title="{gravityview_i18n.button_row_remove}" data-icon="&#xe00b;"><span className="gv-screen-reader-text">{gravityview_i18n.button_row_remove}</span></button>
                    <button onClick={this.handleClick} data-action="settings" className="gv-button" title="{gravityview_i18n.button_row_settings}" data-icon="&#xe009;"><span className="gv-screen-reader-text">{gravityview_i18n.button_row_settings}</span></button>
                </div>
            </div>
        );
    }


});

module.exports = RowControls;
