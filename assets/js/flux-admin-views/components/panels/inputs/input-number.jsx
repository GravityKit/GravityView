var React = require('react');
var Tooltip = require('./tooltip.jsx');


var InputNumber = React.createClass({

    propTypes: {
        args: React.PropTypes.object, // holds the input args (label, default value, classes..)
        values: React.PropTypes.object, // holds the settings values
        handleChange: React.PropTypes.func // handles the input changes
    },

    render: function() {

        var labelClass = 'gv-label-'+this.props.args.type;
        var inputClass = this.props.args.class || 'widefat';

        var myValue = this.props.values[ this.props.args.id ];

        return (
            <div>
                <label htmlFor={this.props.args.id} className={labelClass}>
                    <span className="gv-label">
                        {this.props.args.label}
                    </span>
                    <Tooltip args={this.props.args} />
                </label>
                <input onChange={this.props.handleChange} id={this.props.args.id} type="number" value={myValue} className={inputClass} />
            </div>
        );

    }

});

module.exports = InputNumber;