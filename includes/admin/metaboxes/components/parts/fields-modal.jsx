import React from 'react';
import Tab from './tab.jsx';

var FieldsModal = React.createClass({

    getInitialState: function() {
        return {
            modalTab: 'form'
        };
    },

    renderTabs: function() {
        var tabs = [
            { 'id': 'all', 'label': gravityview_i18n.mo_tab_all },
            { 'id': 'form', 'label': gravityview_i18n.mo_tab_form },
            { 'id': 'entry', 'label': gravityview_i18n.mo_tab_entry },
            { 'id': 'custom', 'label': gravityview_i18n.mo_tab_custom }
        ];

        var tabsLinks = tabs.map( function( tab, i) {
            return (
                <Tab
                    key={tab.id}
                    id={tab.id}
                    changeTab={this.handleChangeTab}
                    label={tab.label}
                    tabClass="media-menu-item"
                    activeClass="active"
                    iconClass={false}
                    isCurrent={(tab.id === this.state.modalTab)}
                />

            );
        }, this );

        return(
            <div className="media-frame-router">
                <div className="media-router">
                    {tabsLinks}
                </div>
            </div>
        );
    },

    handleChangeTab: function( tabId ) {
        this.setState({ modalTab: tabId });
    },


    render: function () {
        if (!this.props.isOpen) return null;
        return (
            <div className="media-modal">
                <button onClick={this.props.modalClose} type="button" className="button-link media-modal-close"><span className="media-modal-icon"><span className="screen-reader-text">{gravityview_i18n.mo_close_modal}</span></span></button>
                <div className="media-modal-content">
                    <div className="media-frame hide-menu">
                        <div className="media-frame-title"><h1>{gravityview_i18n.mo_fields_title}</h1></div>
                        {this.renderTabs()}
                        <div className="media-frame-content"></div>
                        <div className="media-frame-toolbar"><div className="media-toolbar"><div className="media-toolbar-secondary"></div><div className="media-toolbar-primary search-form"><button type="button" className="button media-button button-primary button-large media-button-select" disabled="disabled">Add Fields</button></div></div></div>
                    </div>
                </div>
            </div>
        );
    }
});

export default FieldsModal;
