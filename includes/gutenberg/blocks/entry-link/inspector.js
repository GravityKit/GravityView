import apiFetch from '@wordpress/api-fetch';

const { Component, Fragment } = wp.element;
const { __ } = wp.i18n;
const { InspectorControls } = wp.editor;
const { PanelBody, SelectControl, ToggleControl, TextControl, Spinner, Popover } = wp.components;

export default class Inspector extends Component {
	constructor( props ) {
		super( ...arguments );
		this.state = {
			urlAutoCompleteStatus: false,
			urlAutoCompleteLoading: false,
			urlAutoComplete: [],
		};
	}

	render() {

		const { attributes, setAttributes } = this.props;

		const urlAutocompleter = ( post_id ) => {
			this.setState( {
				urlAutoCompleteLoading: true,
			} );
			if ( this.state.urlAutoComplete.length === 0 ) {
				apiFetch( { path: `${ GV_BLOCKS.home_page }/wp-json/wp/v2/posts/?per_page=-1` } ).then( ( response ) => {
					this.setState( {
						urlAutoComplete: response,
					} );
				} );
			}
			this.setState( {
				urlAutoCompleteStatus: true,
				urlAutoCompleteLoading: false,
			} );

			setAttributes( {
				post_id,
			} );
		};

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
							<TextControl
								label={ __( 'Entry ID', 'gravityview' ) }
								value={ attributes.entry_id }
								type="number"
								min="0"
								onChange={ entry_id => {
									setAttributes( {
										entry_id,
									} );
								} }
							/>
						</Fragment>
					}
					{
						attributes.view_id !== '' && attributes.view_id !== 'Select a View' && attributes.entry_id !== '' &&
						<Fragment>
							<hr />
							<SelectControl
								label={ __( 'Action', 'gravityview' ) }
								value={ attributes.action }
								options={
									[
										{ value: 'read', label: __( 'View Details', 'gravityview' ) },
										{ value: 'edit', label: __( 'Edit Entry', 'gravityview' ) },
										{ value: 'delete', label: __( 'Delete Entry', 'gravityview' ) },
									]
								}
								onChange={ action => {
									setAttributes( {
										action,
									} );
								} }
							/>
							<hr />
							<TextControl
								label={ __( 'Link Text', 'gravityview' ) }
								value={ attributes.content }
								onChange={ content => {
									setAttributes( {
										content,
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
				{
					attributes.view_id !== '' && attributes.view_id !== 'Select a View' && attributes.entry_id !== '' &&
					<PanelBody
						initialOpen={ false }
						title={ __( 'More Settings', 'gravityview' ) }>
						<SelectControl
							label={ __( 'Return Format', 'gravityview' ) }
							value={ attributes.return }
							options={ [
								{ value: 'html', label: __( 'HTML', 'gravityview' ) },
								{ value: 'url', label: __( 'URL', 'gravityview' ) },
							] }
							onChange={ returnVal => {
								setAttributes( {
									return: returnVal,
								} );
							} }
						/>
						<hr />
						<div className="autocomplete-box">
							<TextControl
								label={ __( 'Post ID', 'gravityview' ) }
								value={ attributes.post_id }
								type="number"
								min="0"
								onChange={ post_id => urlAutocompleter( post_id ) }
							/>
							{
								this.state.urlAutoCompleteLoading && <Spinner />
							}
							{
								( attributes.post_id && this.state.urlAutoCompleteStatus && this.state.urlAutoComplete.length > 0 ) &&
								<ul>
									{
										this.state.urlAutoComplete.filter( item => ( item.id ).toString().indexOf( attributes.post_id ) >= 0 ).map( item => {
											return (
												<li
													onClick={ () => {
														this.setState( {
															urlAutoCompleteStatus: false,
														} );
														setAttributes( {
															post_id: item.id,
														} );
													} }
													dangerouslySetInnerHTML={ { __html: `ID : ${ item.id } => ${ item.title.rendered }` } }
												>
												</li>
											);
										} )
									}
								</ul>
							}
						</div>
						<hr />
						<TextControl
							label={ __( 'Link Attributes', 'gravityview' ) }
							value={ attributes.link_atts }
							onChange={ link_atts => {
								setAttributes( {
									link_atts,
								} );
							} }
						/>
						<hr />
						<TextControl
							label={ __( 'Field Values', 'gravityview' ) }
							value={ attributes.field_values }
							onChange={ field_values => {
								setAttributes( {
									field_values,
								} );
							} }
						/>
					</PanelBody>
				}
			</InspectorControls>
		);
	}
}
