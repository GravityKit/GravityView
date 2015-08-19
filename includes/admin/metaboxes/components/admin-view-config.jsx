import React from 'react';

import HelloMessage from './admin-view-data-source.jsx';


function run() {
    React.render(<HelloMessage message="World2" />, document.getElementById('gravityview_select_form') );
}

if ( window.addEventListener ) {
    window.addEventListener('DOMContentLoaded', run );
} else {
    window.attachEvent( 'onload', run );
}

