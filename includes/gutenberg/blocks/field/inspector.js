const { Component, Fragment } = wp.element;
const { __ } = wp.i18n;
const { InspectorControls } = wp.editor;
const { Button, PanelBody, ToggleControl, SelectControl, TextControl, ButtonGroup } = wp.components;

export default class Inspector extends Component {
	constructor( props ) {
		super( ...arguments );

		this.state = {
			entry_id: this.props.attributes.entry_id || '',
			tempEntryId: '',
		};
	}

	render() {

		const { attributes, setAttributes } = this.props;
		const { entry_id } = this.state;

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
					title={ __( 'Main Settings', 'gravityview' ) }>
					<SelectControl
						value={ attributes.view_id }
						options={ viewLists }
						onChange={ view_id => {
							setAttributes( {
								view_id,
							} );
						} }
					/>
					{
						attributes.view_id !== '' && attributes.view_id !== 'Select a View' &&
						<Fragment>
							<hr />
							<h3>{ __( 'Entry ID', 'gravityview' ) }</h3>
							<ButtonGroup
								className="btn-group-triple">
								<Button
									isDefault
									isPrimary={ entry_id !== 'first' && entry_id !== 'last' }
									onClick={ () => {
										this.setState( {
											entry_id: this.state.tempEntryId,
										} );
										setAttributes( {
											entry_id: this.state.tempEntryId,
										} );
									} }

								>
									{ __( 'ID', 'gravityview' ) }
								</Button>
								<Button
									isDefault
									isPrimary={ entry_id === 'first' }
									onClick={ () => {
										this.setState( {
											entry_id: 'first',
										} );
										setAttributes( {
											entry_id: 'first',
										} );
									} }

								>
									{ __( 'First', 'gravityview' ) }
								</Button>
								<Button
									isDefault
									isPrimary={ entry_id === 'last' }
									onClick={ () => {
										this.setState( {
											entry_id: 'last',
										} );
										setAttributes( {
											entry_id: 'last',
										} );
									} }

								>
									{ __( 'Last', 'gravityview' ) }
								</Button>
							</ButtonGroup>
							{
								entry_id !== 'first' && entry_id !== 'last' &&
								<Fragment>
									<TextControl
										placeholder={ __( 'Entry ID', 'gravityview' ) }
										value={ attributes.entry_id || this.state.tempEntryId }
										type="number"
										min="0"
										onChange={ entry_id => {
											setAttributes( {
												entry_id,
											} );
											this.setState( {
												tempEntryId: entry_id,
											} );
										} }
									/>
								</Fragment>
							}
							{
								attributes.entry_id !== '' &&
								<Fragment>
									<hr />
									<TextControl
										label={ __( 'Field ID', 'gravityview' ) }
										value={ attributes.field_id }
										type="number"
										min="0"
										onChange={ field_id => {
											setAttributes( {
												field_id,
											} );
										} }
									/>
									{
										attributes.field_id !== '' &&
										<Fragment>
											<hr />
											<TextControl
												label={ __( 'Custom Label', 'gravityview' ) }
												value={ attributes.custom_label }
												onChange={ custom_label => {
													setAttributes( {
														custom_label,
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
								</Fragment>
							}
						</Fragment>
					}
				</PanelBody>
			</InspectorControls>
		);
	}
}
