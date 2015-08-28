import React from 'react';

var Metabox = React.createClass({

    render: function () {

        var actionLinks = '';

        if( this.props.mTitleLinks.length ) {
            actionLinks = this.props.mTitleLinks.map( function ( action, i ) {
                return (
                    <span key={i}><a href={action.href} title={action.title}>{action.label}</a></span>
                );
            });

            actionLinks = (
                <span className="alignright gv-form-links">
                    <div className="row-actions">
                        {actionLinks}
                    </div>
                </span>
            );
        }

        return (
            <div className="postbox">
                <div className="handlediv" title="Click to toggle">
                    <br/>
                </div>
                <h3 className="hndle ui-sortable-handle">
                    <span>
                        {this.props.mTitle}
                        {actionLinks}
                    </span>
                </h3>
                <div className="inside">
                    {this.props.children}
                </div>
            </div>
        );
    }
});

export default Metabox;


