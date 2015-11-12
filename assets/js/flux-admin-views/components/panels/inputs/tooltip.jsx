var React = require('react');

var Tooltip = React.createClass({

    propTypes: {
        args: React.PropTypes.object // holds the input args (label, default value, classes..)
    },

    render: function() {

        if( ! this.props.args.tooltip && ! this.props.args.desc ) {
            return null;
        }

        var text = this.props.args.tooltip || this.props.args.desc;
        var title = '<h6>'+this.props.args.label+'</h6><p>'+text+'</p>';

        return (
           <i data-tip={title} className='fa fa-question-circle'></i>
        );

    }

});

module.exports = Tooltip;