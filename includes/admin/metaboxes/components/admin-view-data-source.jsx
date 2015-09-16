import React from 'react';
import Metabox from './parts/metabox.jsx';
import AlertDialog from './parts/alert-dialog.jsx';

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
            formActionLinks: [],
            formNewSelectedValue: this.props.form
        };
    },

    componentDidMount: function() {
        this.loadFormActionLinks();
    },

    cancelDialogAction: function( e ) {
        e.preventDefault();
        this.setState({ formNewSelectedValue: this.props.form });
    },

    handleFormChange: function( e ) {
        this.setState({ formNewSelectedValue: e.target.value });
    },

    render: function () {

        var startFreshButton = '',
            orSelectForm = '',
            formSelect = '',
            switchView = '';

        // check if the alert message needs to be rendered
        var showAlert = this.props.form != this.state.formNewSelectedValue;

        if ( this.props.form <= 0 ) {
            startFreshButton = (
                <a onClick={this.props.onStartFresh} className="button button-primary" title={gravityview_i18n.mb_ds_start_button}>{gravityview_i18n.mb_ds_start_button}</a>
            );

            if( gravityview_view_settings.forms.length > 0 ) {

                orSelectForm =  (
                    <span>&nbsp;{gravityview_i18n.mb_ds_or_existing}&nbsp;</span>
                );

            }
        } else {
            switchView = (
                <a className="button button-primary gv-button-left-margin" title={gravityview_i18n.mb_ds_switch_view} disabled={showAlert}>{gravityview_i18n.mb_ds_switch_view}</a>
            );
        }

        // Form select dropdown
        if( gravityview_view_settings.forms.length > 0 ) {
            var formSelectOptions = gravityview_view_settings.forms.map( function ( form ) {
                return (
                    <option key={form.id} value={form.id}>{form.title}</option>
                );
            });

            formSelect = (
                <select value={ showAlert? this.state.formNewSelectedValue : this.props.form } name="" onChange={this.handleFormChange} disabled={showAlert}>
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
                <AlertDialog
                    isOpen={showAlert}
                    message={gravityview_i18n.mb_ds_change_form}
                    cancelAction={this.cancelDialogAction}
                    continueAction={this.props.onFormChange}
                    changedValue={this.state.formNewSelectedValue}
                />
            </Metabox>
        );
    }
});

export default DataSource;