import React from 'react';
import Metabox from './parts/metabox.jsx';
import Tabs from './parts/tabs.jsx';
import FieldsModal from './parts/fields-modal.jsx';

var ViewConfiguration = React.createClass({

    getInitialState: function() {
        return {
            currentTab: 'directory',
            openFieldsModal: false
        };
    },

    handleChangeTab: function( tabId ) {
        this.setState({ currentTab: tabId });
    },


    handleOpenModal: function(e) {
        e.preventDefault();
        this.setState({ openFieldsModal: true });

    },

    handleModalClose: function(e) {
        e.preventDefault();
        this.setState({ openFieldsModal: false });
    },



    render: function () {
        var tabs = [
            { 'id': 'directory', 'label': gravityview_i18n.mb_vc_tab_multiple, 'icon': 'admin-page' },
            { 'id': 'single', 'label': gravityview_i18n.mb_vc_tab_single, 'icon': 'media-default' },
            { 'id': 'edit', 'label': gravityview_i18n.mb_vc_tab_edit, 'icon': 'welcome-write-blog' }
        ];

        return(
            <Metabox mTitle={gravityview_i18n.mb_vc_title} mTitleLinks={false}>
                <Tabs tabList={tabs} changeTab={this.handleChangeTab} currentTab={this.state.currentTab} />
                <button onClick={this.handleOpenModal} className="button">Add Field</button>
                <FieldsModal
                    modalClose={this.handleModalClose}
                    isOpen={this.state.openFieldsModal} />
            </Metabox>
        );
    }


});

export default ViewConfiguration;