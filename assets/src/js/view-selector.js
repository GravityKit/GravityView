import Select from 'react-select';

const { Component } = wp.element;
const { __ } = wp.i18n;

export default class SelectViewItem extends Component {

	constructor( props ) {
		super( ...arguments );
		this.state = {
			viewLists: [
				{
					value: '',
					label: __( 'Select a View', 'gv-blocks' ),
				},
				...GV_BLOCKS.view_list,
			],
		};
	}

	render() {
		const { attributes, setAttributes } = this.props;
		const selectedView = ( id ) => {
			return this.state.viewLists.filter( item => item.value === id );
		};
		return (
			<Select
				className={ `select-view-selectbox` }
				value={ selectedView( attributes.id ) }
				options={ this.state.viewLists }
				onChange={ item => {
					setAttributes( {
						id: item.value,
					} );
				} }
			/>
		);
	}
}
