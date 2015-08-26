import React from 'react';
import Metabox from './metabox/metabox.jsx';

var DataSource = React.createClass({

    loadFormActionLinks: function() {
        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            dataType: 'json',
            data: {
                action: 'gv_get_form_links',
                nonce: gvGlobals.nonce,
                form: this.state.form,
                view: gvViewSettings.view_id
            },
            async: true,
            cache: false,
            success: function( response ) {
                console.log( response );
                if ( this.isMounted() && response.success ) {
                    this.setState({formActionLinks: response.data });
                }
            }.bind(this),
            error: function(xhr, status, err) {
                console.error( ajaxurl, status, err.toString());
            }.bind(this)
        });
    },

    getInitialState: function() {
        return {
            form: gvViewSettings.form_id,
            formActionLinks: []
        };
    },

    componentDidMount: function() {
        this.loadFormActionLinks();
    },

    render: function () {

        return(
            <Metabox mTitle="Data Source" mTitleLinks={this.state.formActionLinks}>
                <div>this is the content</div>
            </Metabox>
        );
    }
});

export default DataSource;