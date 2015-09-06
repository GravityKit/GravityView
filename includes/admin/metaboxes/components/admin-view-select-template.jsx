import React from 'react';
import Metabox from './parts/metabox.jsx';


var SelectTemplate = React.createClass({

    filterTemplateType: function( template, i ) {
        if( template.type === this.props.filter ) {
            return true;
        }
    },

    renderTemplatesList: function( template, i ) {
        var classSelected = 'gv-view-types-module';
        classSelected += this.props.template == template.id ? ' gv-selected' : '';

        var buyOrSelectLink = '',
            previewLink = '',
            linkClass = 'button-primary',
            linkText = '';

        if ( template.buy_source.length ) {
            linkClass += ' button-buy-now';
            linkText = gravityview_i18n.mb_st_buy_button;
            buyOrSelectLink = (
                <p><a href={template.buy_source} className={linkClass}>{linkText}</a></p>
            );

        } else {
            linkClass += ' button button-large';
            linkText = gravityview_i18n.mb_st_select_button;

            buyOrSelectLink = (
                <p><a className={linkClass}>{linkText}</a></p>
            );

            if ( template.preview.length ) {
                previewLink = (
                    <a href={template.preview} rel="external" className="gv-site-preview"><i className="dashicons dashicons-admin-links" title={gravityview_i18n.mb_st_preview}></i></a>
                );
            }
        }

        return (
            <div key={template.id} className="gv-grid-col-1-3">
                <div className={classSelected} data-filter={template.type} data-templateid={template.id}>
                    <div className="gv-view-types-hover" onClick={this.props.onTemplateClick} >
                        <div>
                            {buyOrSelectLink}
                            {previewLink}
                        </div>
                    </div>
                    <div className="gv-view-types-normal">
                        <img src={template.logo} alt={template.label} />
                        <h5>{template.label}</h5>
                        <p className="description">{template.description}</p>
                    </div>
                </div>
            </div>
        );
    },

    render: function () {

        var templatesList = gravityview_view_settings.templates.filter( this.filterTemplateType, this ).map( this.renderTemplatesList, this );

        return(
            <Metabox mTitle={gravityview_i18n.mb_st_title} mTitleLinks={false}>
                <div className="gv-grid">
                    {templatesList}
                </div>
            </Metabox>
        );
    }
});

export default SelectTemplate;