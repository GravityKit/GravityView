var React = require('react');

var InputHidden = React.createClass({

    propTypes: {
        args: React.PropTypes.object, // holds the input args (label, default value, classes..)
        values: React.PropTypes.object, // holds the settings values
        handleChange: React.PropTypes.func // handles the input changes
    },

    render: function() {


        var inputClass = this.props.args.class || 'widefat';

        var myValue = this.props.values[ this.props.args.id ];

        return (
            <input onChange={this.props.handleChange} id={this.props.args.id} type="hidden" value={myValue} className={inputClass} />
        );

    }

});

module.exports = InputHidden;