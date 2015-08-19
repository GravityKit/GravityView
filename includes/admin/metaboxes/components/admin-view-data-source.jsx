import React from 'react';

var HelloMessage = React.createClass({
    render: function () {
        return <h1>Hello {this.props.message}!</h1>;
    }
});

export default HelloMessage;