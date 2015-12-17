var React = require('react');
var Tooltip = require('./tooltip.jsx');


var InputCheckbox = React.createClass({

    propTypes: {
        args: React.PropTypes.object, // holds the input args (label, default value, classes..)
        values: React.PropTypes.object, // holds the settings values
        handleChange: React.PropTypes.func // handles the input changes
    },

    render: function() {

        //var labelClass = 'gv-label-'+this.props.args.type;
        var labelClass = '';

        var myValue =  this.props.values[ this.props.args.id ].toString();

        myValue = myValue === 'true' || myValue === '1';

        return (
            <div className="gv-panel__checkboxes-group">
                <input onChange={this.props.handleChange} id={this.props.args.id} type="checkbox" checked={myValue} />
                <label htmlFor={this.props.args.id} className={labelClass}>
                    {this.props.args.label}
                    <Tooltip args={this.props.args} />
                </label>
            </div>
        );

    }

});

module.exports = InputCheckbox;