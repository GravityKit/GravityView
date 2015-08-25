import React from 'react';

import DataSource from './admin-view-data-source.jsx';


function run() {
    jQuery('#postbox-container-2').prepend( '<div id="gv-container-react"></div>' );
    React.render(<DataSource />, document.getElementById('gv-container-react') );
}

if ( window.addEventListener ) {
    window.addEventListener('DOMContentLoaded', run );
} else {
    window.attachEvent( 'onload', run );
}

