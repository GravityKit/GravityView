import React from 'react';
import Metabox from './parts/metabox.jsx';

var SelectTemplate = React.createClass({

    render: function () {

        var currentTemplate = this.props.template,
            selectTemplateHandler = this.props.onTemplateChange;

        var templatesList = gravityview_view_settings.templates.map( function( template ) {

            var classSelected = 'gv-view-types-module';
            classSelected += currentTemplate == template.id ? ' gv-selected' : '';

            var buyOrSelectLink = '',
                previewLink = '';
            if ( template.buy_source.length ) {
                buyOrSelectLink = (
                    <p><a href={template.buy_source} className="button-primary button-buy-now">{gravityview_i18n.mb_st_buy_button}</a></p>
                );
            } else {
                buyOrSelectLink = (
                    <p><a onClick={selectTemplateHandler} className="button button-large button-primary" data-templateid={template.id}>{gravityview_i18n.mb_st_select_button}</a></p>
                );
                if ( template.preview.length ) {
                    previewLink = (
                        <a href={template.preview} rel="external" className="gv-site-preview"><i className="dashicons dashicons-admin-links" title={gravityview_i18n.mb_st_preview}></i></a>
                    );
                }
            }

            return (
                <div className="gv-grid-col-1-3">
                    <div className={classSelected} data-filter={template.type}>
                        <div className="gv-view-types-hover">
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

        });

        return(
            <Metabox key="selectTemplate" mTitle={gravityview_i18n.mb_st_title} mTitleLinks={false}>
                <div className="gv-grid">
                    {templatesList}
                </div>
            </Metabox>
        );
    }
});

export default SelectTemplate;