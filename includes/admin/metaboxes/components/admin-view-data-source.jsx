import React from 'react';
import Metabox from './metabox/metabox.jsx';

var DataSource = React.createClass({
    render: function () {
        // todo: replace this by script localization
        var actionLinks = [
            { href: "", label:"Edit Form", title: "Edit Form" },
            { href: "", label:"Entries", title: "Entries" }
        ];

        return(
            <Metabox mTitle="Data Source" mTitleLinks={actionLinks}>
                <div>this is the content</div>
            </Metabox>
        );
    }
});

export default DataSource;