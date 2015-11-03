var React = require('react');
var ViewConfig = require('./components/view-config.jsx');

function run() {
    jQuery('#post-body-content').append( '<div id="gv-container-react"></div>' );
    React.render( <ViewConfig />, document.getElementById('gv-container-react') );
}

if ( window.addEventListener ) {
    window.addEventListener( 'DOMContentLoaded', run );
} else {
    window.attachEvent( 'onload', run );
}