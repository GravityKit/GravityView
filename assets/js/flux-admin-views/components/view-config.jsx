var React = require('react');
var ViewBuilder = require('./builder/view-builder.jsx');
var PanelRouter = require('./panels/panel-router.jsx');

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

        /*var selectTemplateMetabox = null;
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
         <DataSource
         key={'ds'+this.state.form}
         form={this.state.form}
         onFormChange={this.handleFormChange}
         onStartFresh={this.handleStartFresh}
         onSwitchViewType={this.handleSwitchTemplate}
         />
         {selectTemplateMetabox}
         */

        /* Panel placeholder */
        /*  Initial setup placeholder */
        return(
            <div className="gv-config">
                <PanelRouter />
                <ViewBuilder />
            </div>
        );
    }

});

module.exports = ViewConfig;