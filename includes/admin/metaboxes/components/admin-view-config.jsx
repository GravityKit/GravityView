import React from 'react';

import DataSource from './admin-view-data-source.jsx';
import SelectTemplate from './admin-view-select-template.jsx';

var ViewConfig = React.createClass({

    getInitialState: function() {
        return {
            form: gravityview_view_settings.form_id,
            template: gravityview_view_settings.template_id
        };
    },

    handleFormChange: function(e) {
        this.setState({ form: e.target.value });
    },

    handleTemplateChange: function(e) {
        this.setState({ template: jQuery( e.target ).find('a.button-select-template').attr('data-templateid') });
    },

    render: function() {
        return(
            <div>
                <DataSource key={'ds'+this.state.form} form={this.state.form} onFormChange={this.handleFormChange} />
                <SelectTemplate key={'st'+this.state.template} template={this.state.template} onTemplateClick={this.handleTemplateChange} />
            </div>
        );
    }

});



function run() {
    jQuery('#postbox-container-2').prepend( '<div id="gv-container-react"></div>' );
    React.render( <ViewConfig />, document.getElementById('gv-container-react') );
}

if ( window.addEventListener ) {
    window.addEventListener('DOMContentLoaded', run );
} else {
    window.attachEvent( 'onload', run );
}

