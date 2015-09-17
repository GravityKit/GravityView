import React from 'react';

import DataSource from './admin-view-data-source.jsx';
import SelectTemplate from './admin-view-select-template.jsx';
import ViewConfiguration from './admin-view-configuration.jsx';

var ViewConfig = React.createClass({

    getInitialState: function() {
        return {
            startFresh: false, // Start Fresh context
            showTemplates: false, // Type of templates to show: 'preset' or 'custom'
            form: gravityview_view_settings.form_id, // Holds the selected form ID
            template: gravityview_view_settings.template_id // Holds the selected Template ID
        };
    },

    handleStartFresh: function() {
        this.setState({ startFresh: true });
        this.setState({ showTemplates: 'preset' });
    },

    handleFormChange: function(e) {
        this.setState({ form: e.target.getAttribute('data-change-value') });
        this.setState({ showTemplates: 'custom' });
    },

    handleTemplateChange: function(e) {
        e.preventDefault();
        this.setState({ template: e.target.getAttribute('data-change-value') });

        if( !this.state.showTemplates ) {
            this.setState({ showTemplates: 'custom' });
        } else {
            this.setState({ showTemplates: false });
        }

    },

    handleSwitchTemplate: function(e) {
        e.preventDefault();
        this.setState({ showTemplates: 'custom' });
    },

    render: function() {

        var selectTemplateMetabox = null;
        if( this.state.showTemplates ) {

            selectTemplateMetabox = (
                <SelectTemplate
                    key={'st'+this.state.template+this.state.showTemplates}
                    template={this.state.template}
                    startFresh={this.state.startFresh}
                    filter={this.state.showTemplates}
                    onTemplateClick={this.handleTemplateChange}
                />
            );
        }

        return(
            <div>
                <DataSource
                    key={'ds'+this.state.form}
                    form={this.state.form}
                    onFormChange={this.handleFormChange}
                    onStartFresh={this.handleStartFresh}
                    onSwitchViewType={this.handleSwitchTemplate}
                />
                {selectTemplateMetabox}
                <ViewConfiguration />
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

