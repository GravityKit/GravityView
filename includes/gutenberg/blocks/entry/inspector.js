const { Component, Fragment } = wp.element;
const { __ } = wp.i18n;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, ToggleControl, SelectControl, TextControl, ButtonGroup, Button } = wp.components;

export default class Inspector extends Component {
	constructor( props ) {
		super( ...arguments );

		this.state = {
			id: this.props.attributes.id || '',
			tempEntryId: '',
		};
	}

	render() {

		const { attributes, setAttributes } = this.props;
		const { id } = this.state;

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
									isPrimary={ id !== 'first' && id !== 'last' }
									onClick={ () => {
										this.setState( {
											id: this.state.tempEntryId,
										} );
										setAttributes( {
											id: this.state.tempEntryId,
										} );
									} }

								>
									{ __( 'ID', 'gravityview' ) }
								</Button>
								<Button
									isDefault
									isPrimary={ id === 'first' }
									onClick={ () => {
										this.setState( {
											id: 'first',
										} );
										setAttributes( {
											id: 'first',
										} );
									} }

								>
									{ __( 'First', 'gravityview' ) }
								</Button>
								<Button
									isDefault
									isPrimary={ id === 'last' }
									onClick={ () => {
										this.setState( {
											id: 'last',
										} );
										setAttributes( {
											id: 'last',
										} );
									} }

								>
									{ __( 'Last', 'gravityview' ) }
								</Button>
							</ButtonGroup>
							{
								id !== 'first' && id !== 'last' &&
								<Fragment>
									<TextControl
										placeholder={ __( 'Entry ID', 'gravityview' ) }
										value={ attributes.id || this.state.tempEntryId }
										type="number"
										min="0"
										onChange={ id => {
											setAttributes( {
												id,
											} );
											this.setState( {
												tempEntryId: id,
											} );
										} }
									/>
								</Fragment>
							}
							{
								attributes.view_id !== '' && attributes.view_id !== 'Select a View' && attributes.id !== '' &&
								<Fragment>
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
				</PanelBody>
			</InspectorControls>
		);
	}
}
