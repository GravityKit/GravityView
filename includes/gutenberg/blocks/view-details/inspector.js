const { Component, Fragment } = wp.element;
const { __ } = wp.i18n;
const { InspectorControls } = wp.blockEditor;
const { ToggleControl, PanelBody, SelectControl } = wp.components;

export default class Inspector extends Component {
	constructor( props ) {
		super( ...arguments );
	}

	render() {

		const { attributes, setAttributes } = this.props;

		const viewLists = [
			{
				value: '',
				label: __( 'Select a View', 'gravityview' ),
			},
			...GV_BLOCKS.view_list,
		];
		return (
			<InspectorControls>
				<PanelBody
					title={ __( 'View Settings', 'gravityview' ) }>
					<SelectControl
						value={ attributes.id }
						options={ viewLists }
						onChange={ id => {
							setAttributes( {
								id,
							} );
						} }
					/>
					{
						attributes.id !== '' && attributes.id !== 'Select a View' &&
						<Fragment>
							<hr />
							<SelectControl
								label={ __( 'Details', 'gravityview' ) }
								value={ attributes.detail }
								options={ [
									{ value: 'total_entries', label: __( 'Total Entries', 'gravityview' ) },
									{ value: 'first_entry', label: __( 'First Entry', 'gravityview' ) },
									{ value: 'last_entry', label: __( 'Last Entry', 'gravityview' ) },
									{ value: 'page_size', label: __( 'Page Size', 'gravityview' ) },
								] }
								onChange={ detail => {
									setAttributes( {
										detail,
									} );
								} }
							/>
							<hr />
							<ToggleControl
								label={ __( 'Preview', 'gravityview' ) }
								checked={ attributes.preview }
								onChange={ preview => {
									setAttributes( {
										preview,
									} );
								} }
							/>
						</Fragment>
					}
				</PanelBody>
			</InspectorControls>
		);
	}
}
