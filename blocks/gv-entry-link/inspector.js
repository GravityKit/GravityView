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
				apiFetch( { path: `${ GV_GUTENBERG.home_page }/wp-json/wp/v2/posts/?per_page=-1` } ).then( ( response ) => {
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
				label: __( 'Select a View', 'gv-gutenberg' ),
			},
			...GV_GUTENBERG.view_list,
		];

		return (
			<InspectorControls>
				<PanelBody
					title={ __( 'Main Settings', 'gv-gutenberg' ) }>
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
								label={ __( 'Entry ID', 'gv-gutenberg' ) }
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
								label={ __( 'Action', 'gv-gutenberg' ) }
								value={ attributes.action }
								options={
									[
										{ value: 'read', label: __( 'View Details', 'gv-gutenberg' ) },
										{ value: 'edit', label: __( 'Edit Entry', 'gv-gutenberg' ) },
										{ value: 'delete', label: __( 'Delete Entry', 'gv-gutenberg' ) },
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
								label={ __( 'Link Text', 'gv-gutenberg' ) }
								value={ attributes.content }
								onChange={ content => {
									setAttributes( {
										content,
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
				{
					attributes.view_id !== '' && attributes.view_id !== 'Select a View' && attributes.entry_id !== '' &&
					<PanelBody
						initialOpen={ false }
						title={ __( 'More Settings', 'gv-gutenberg' ) }>
						<SelectControl
							label={ __( 'Return Format', 'gv-gutenberg' ) }
							value={ attributes.return }
							options={ [
								{ value: 'html', label: __( 'HTML', 'gv-gutenberg' ) },
								{ value: 'url', label: __( 'URL', 'gv-gutenberg' ) },
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
								label={ __( 'Post ID', 'gv-gutenberg' ) }
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
							label={ __( 'Link Attributes', 'gv-gutenberg' ) }
							value={ attributes.link_atts }
							onChange={ link_atts => {
								setAttributes( {
									link_atts,
								} );
							} }
						/>
						<hr />
						<TextControl
							label={ __( 'Field Values', 'gv-gutenberg' ) }
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
