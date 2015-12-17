var React = require('react');
var Tooltip = require('./tooltip.jsx');


var InputSelect = React.createClass({

    propTypes: {
        args: React.PropTypes.object, // holds the input args (label, default value, classes..)
        values: React.PropTypes.object, // holds the settings values
        handleChange: React.PropTypes.func // handles the input changes
    },

    renderOptions: function( item, i ) {
        return(
            <option key={item.value} id={item.value} value={item.value}>
                {item.label}
            </option>
        );
    },

    render: function() {

        //var labelClass = 'gv-label-'+this.props.args.type;
        var labelClass = '';
        var inputClass = this.props.args.class || 'widefat';

        var myValue = this.props.values[ this.props.args.id ];

        var options = this.props.args.options.map(  this.renderOptions, this );

        return (
            <div>
                <label htmlFor={this.props.args.id} className={labelClass}>
                    {this.props.args.label}
                    <Tooltip args={this.props.args} />
                </label>
                <select onChange={this.props.handleChange} id={this.props.args.id} value={myValue} className={inputClass}>
                    {options}
                </select>
            </div>
        );

    }

});

module.exports = InputSelect;