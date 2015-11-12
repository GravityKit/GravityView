var React = require('react');
var Tooltip = require('./tooltip.jsx');


var InputCheckbox = React.createClass({

    propTypes: {
        args: React.PropTypes.object, // holds the input args (label, default value, classes..)
        values: React.PropTypes.object, // holds the settings values
        handleChange: React.PropTypes.func // handles the input changes
    },

    render: function() {

        var leftLabel = this.props.args.left_label || null ;

        var labelClass = 'gv-label-'+this.props.args.type;

        var myValue =  this.props.values[ this.props.args.id ].toString();

        myValue = myValue === 'true' || myValue === '1';

        return (
            <label htmlFor={this.props.args.id} className={labelClass}>
                {leftLabel}
                <input onChange={this.props.handleChange} id={this.props.args.id} type="checkbox" checked={myValue} />
                {this.props.args.label}
                <Tooltip args={this.props.args} />
            </label>
        );

    }

});

module.exports = InputCheckbox;