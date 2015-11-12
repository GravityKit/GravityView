var React = require('react');
var ReactDOM = require('react-dom');
var ViewConfig = require('./components/view-config.jsx');

function run() {
    jQuery('#post-body-content').append( '<div id="gv-container-react"></div>' );
    ReactDOM.render( <ViewConfig />, document.getElementById('gv-container-react') );
}

if ( window.addEventListener ) {
    window.addEventListener( 'DOMContentLoaded', run );
} else {
    window.attachEvent( 'onload', run );
}