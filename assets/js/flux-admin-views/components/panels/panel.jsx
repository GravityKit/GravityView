import React from 'react';

var Panel = React.createClass({
    propTypes: {
        isSubPanel: React.PropTypes.bool, // is a sub panel ?
        title:  React.PropTypes.string, // Panel title
        footerActions: React.PropTypes.shape({
            label:  React.PropTypes.string, // action button label
            handleAction:   React.PropTypes.func // action button onClick handler callback function
        })
    },

    renderBackLink: function() {
        if( ! this.props.isSubPanel ) {
            return null;
        }
        return (
            <a title="{gravityview_i18n.button_back}" class="gv-panel__go-back" data-icon="&#xe001;"><span className="gv-screen-reader-text">{gravityview_i18n.button_back}</span></a>
        );
    },

    renderPanelClass: function() {
        var panelClass = 'gv-panel';
        if( this.props.isSubPanel ) {
            panelClass += ' gv-panel__sub-panel';
        }
        if( this.props.isOpen ) {
            panelClass += ' gv-panel__is-visible';
        }
        return panelClass;
    },

    renderFooterButtons: function() {
        if( !this.props.footerActions ) {
            return null;
        }

    },

    render: function() {

        return (
            <div className={this.renderPanelClass()}>
                <header className="gv-panel__header">
                    {this.renderBackLink()}
                    <h3>{this.props.title}</h3>
                    <a title={gravityview_i18n.button_close} className="gv-panel__close" data-icon="&#xe005;"><span className="gv-screen-reader-text">{gravityview_i18n.button_close}</span></a>
                </header>
                <div className="gv-panel__content">
                    {this.props.children}
                </div>
                <footer className="gv-panel__footer">
                    <button className="gv-button gv-panel__cancel" title={gravityview_i18n.button_cancel}>{gravityview_i18n.button_cancel}</button>
                    {this.renderFooterButtons()}
                </footer>
            </div>
        );
    }


});

module.exports = Panel;