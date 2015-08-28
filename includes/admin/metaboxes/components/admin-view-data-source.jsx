import React from 'react';
import Metabox from './parts/metabox.jsx';

var DataSource = React.createClass({

    loadFormActionLinks: function() {
        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            dataType: 'json',
            data: {
                action: 'gv_get_form_links',
                nonce: gvGlobals.nonce,
                form: this.props.form,
                view: gravityview_view_settings.view_id
            },
            async: true,
            cache: false,
            success: function( response ) {
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
            formActionLinks: []
        };
    },

    componentDidMount: function() {
        this.loadFormActionLinks();
    },

    render: function () {

        var startFreshButton = '',
            orSelectForm = '',
            formSelect = '',
            switchView = '';


        if ( this.props.form <= 0 ) {
            startFreshButton = (
                <a className="button button-primary" title={gravityview_i18n.mb_ds_start_button}>{gravityview_i18n.mb_ds_start_button}</a>
            );

            if( gravityview_view_settings.forms.length > 0 ) {

                orSelectForm =  (
                    <span>&nbsp;{gravityview_i18n.mb_ds_or_existing}&nbsp;</span>
                );

            }
        } else {
            switchView = (
                <a className="button button-primary" title={gravityview_i18n.mb_ds_switch_view}>{gravityview_i18n.mb_ds_switch_view}</a>
            );
        }

        if( gravityview_view_settings.forms.length > 0 ) {
            var formSelectOptions = gravityview_view_settings.forms.map( function ( form ) {
                return (
                    <option key={form.id} value={form.id}>{form.title}</option>
                );
            });

            formSelect = (
                <select value={this.props.form} name="" onChange={this.props.onFormChange}>
                    <option value="">&mdash; {gravityview_i18n.mb_ds_list_forms} &mdash;</option>
                    {formSelectOptions}
                </select>
            );
        }

        return(
            <Metabox mTitle={gravityview_i18n.mb_ds_title} mTitleLinks={this.state.formActionLinks}>
                <label>{gravityview_i18n.mb_ds_subtitle}</label>
                <p>
                    {startFreshButton}
                    {orSelectForm}
                    {formSelect}
                    {switchView}
                </p>
            </Metabox>
        );
    }
});

export default DataSource;