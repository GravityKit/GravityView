var React = require('react');
var ViewActions = require('../../actions/view-actions.js');

var Panel = React.createClass({
    propTypes: {
        isVisible: React.PropTypes.bool, // is panel visible
        returnPanel: React.PropTypes.string, // holds the panel ID when going back
        title:  React.PropTypes.string, // Panel title
        handleClosePanel: React.PropTypes.func, // Callback to close the panel
        footerActions: React.PropTypes.shape({
            label:  React.PropTypes.string, // action button label
            handleAction:   React.PropTypes.func // action button onClick handler callback function
        })
    },

    componentDidMount: function() {
        jQuery( document.body ).on( 'keyup', this.handleKeyUp );
    },

    componentWillUnmount: function() {
        jQuery( document.body ).off( 'keyup', this.handleKeyUp );
    },

    handleKeyUp: function(e) {
        // 27 = ESCAPE
        if( e.keyCode == 27 ) {
            ViewActions.closePanel();
        }
    },

    handleClosePanel: function(e) {
        e.preventDefault();
        e.stopPropagation();
        ViewActions.closePanel();
    },

    handleBackPanel: function(e) {
        e.preventDefault();
        e.stopPropagation();
        ViewActions.openPanel( this.props.returnPanel, '' );
    },

    renderBackLink: function() {
        if( ! this.props.returnPanel ) {
            return null;
        }
        return (
            <a onMouseDown={this.handleBackPanel} title={gravityview_i18n.button_back} className="gv-panel__go-back" data-icon="&#xe001;"><span className="gv-screen-reader-text">{gravityview_i18n.button_back}</span></a>
        );
    },

    renderPanelClass: function() {
        var panelClass = 'gv-panel';
        if( this.props.returnPanel ) {
            panelClass += ' gv-panel__sub-panel';
        }
        if( this.props.isVisible ) {
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
        //todo: onMouseDown needs to be replaced by onClick - there is a conflict with WordPress core scripts..
        return (
            <div className={this.renderPanelClass()}>
                <header className="gv-panel__header">
                    {this.renderBackLink()}
                    <h3>{this.props.title}</h3>
                    <a onMouseDown={this.handleClosePanel} title={gravityview_i18n.button_close} className="gv-panel__close" data-icon="&#xe005;"><span className="gv-screen-reader-text">{gravityview_i18n.button_close}</span></a>
                </header>
                <div className="gv-panel__content">
                    {this.props.children}
                </div>
                <footer className="gv-panel__footer">
                    <a onMouseDown={this.handleClosePanel} className="gv-button gv-panel__cancel" title={gravityview_i18n.button_cancel}>{gravityview_i18n.button_cancel}</a>
                    {this.renderFooterButtons()}
                </footer>
            </div>
        );
    }


});

module.exports = Panel;