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
				label: __( 'Select a View', 'gv-gutenberg' ),
			},
			...GV_GUTENBERG.view_list,
		];
		return (
			<InspectorControls>
				<PanelBody
					title={ __( 'View Settings', 'gv-gutenberg' ) }>
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
								label={ __( 'Details', 'gv-gutenberg' ) }
								value={ attributes.detail }
								options={ [
									{ value: 'total_entries', label: __( 'Total Entries', 'gv-gutenberg' ) },
									{ value: 'first_entry', label: __( 'First Entry', 'gv-gutenberg' ) },
									{ value: 'last_entry', label: __( 'Last Entry', 'gv-gutenberg' ) },
									{ value: 'page_size', label: __( 'Page Size', 'gv-gutenberg' ) },
								] }
								onChange={ detail => {
									setAttributes( {
										detail,
									} );
								} }
							/>
							<hr />
							<ToggleControl
								label={ __( 'Preview', 'gv-gutenberg' ) }
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
