const { Component, Fragment } = wp.element;
const { __ } = wp.i18n;
const { InspectorControls } = wp.editor;
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
				label: __( 'Select a View', 'gv-blocks' ),
			},
			...GV_BLOCKS.view_list,
		];
		return (
			<InspectorControls>
				<PanelBody
					title={ __( 'View Settings', 'gv-blocks' ) }>
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
								label={ __( 'Details', 'gv-blocks' ) }
								value={ attributes.detail }
								options={ [
									{ value: 'total_entries', label: __( 'Total Entries', 'gv-blocks' ) },
									{ value: 'first_entry', label: __( 'First Entry', 'gv-blocks' ) },
									{ value: 'last_entry', label: __( 'Last Entry', 'gv-blocks' ) },
									{ value: 'page_size', label: __( 'Page Size', 'gv-blocks' ) },
								] }
								onChange={ detail => {
									setAttributes( {
										detail,
									} );
								} }
							/>
							<hr />
							<ToggleControl
								label={ __( 'Preview', 'gv-blocks' ) }
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
