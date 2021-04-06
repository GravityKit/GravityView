import moment from 'moment';
import DatePicker from 'react-datepicker';
import apiFetch from '@wordpress/api-fetch';
import SelectViewItem from 'AssetSources/js/view-selector';

const { Component, Fragment } = wp.element;
const { __ } = wp.i18n;
const { InspectorControls } = wp.blockEditor;
const { ToggleControl, PanelBody, SelectControl, TextControl, ButtonGroup, Button, Spinner } = wp.components;

export default class Inspector extends Component {
	constructor( props ) {
		super( ...arguments );

		this.state = {
			today: moment().format( 'YYYY-MM-DD' ),
			urlAutoCompleteStatus: false,
			urlAutoCompleteLoading: false,
			urlAutoComplete: [],
		};
	}

	render() {

		const { attributes, setAttributes } = this.props;
		const isStartDateValid = attributes.start_date && attributes.start_date.indexOf( '-' ) > 0 && moment( attributes.start_date ).isValid();
		const isEndDateValid = attributes.end_date && attributes.start_date.indexOf( '-' ) > 0 && moment( attributes.end_date ).isValid();

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

		return (
			<InspectorControls>
				<PanelBody
					title={ __( 'View Settings', 'gravityview' ) }>
					<SelectViewItem  { ...{ setAttributes, ...this.props } } />
					{
						attributes.id !== '' && attributes.id !== 'Select a View' &&
						<Fragment>
							<hr />
							<div style={ { position: 'relative' } }>
								<label style={ {
									marginBottom: 10,
									display: 'block',
								} }>{ __( 'Start Date', 'gravityview' ) }</label>
								<ButtonGroup
									className="btn-group-double">
									<Button
										isDefault
										isPrimary={ attributes.start_date_type === 'static' }
										onClick={ () => {
											setAttributes( {
												start_date_type: 'static',
											} );
										} }

									>
										{ __( 'Static (Calendar)', 'gravityview' ) }
									</Button>
									<Button
										isDefault
										isPrimary={ attributes.start_date_type === 'relative' }
										onClick={ () => {
											setAttributes( {
												start_date_type: 'relative',
											} );
										} }

									>
										{ __( 'Relative', 'gravityview' ) }
									</Button>
								</ButtonGroup>
								{
									attributes.start_date_type === 'static' &&
									<DatePicker
										dateFormat="yyyy-MM-dd"
										selected={ isStartDateValid ? moment( attributes.start_date ).toDate() : '' }
										onChange={ start_date => {
											setAttributes( {
												start_date: moment( start_date ).format( 'YYYY-MM-DD' ),
											} );
										} } />
								}
								{
									attributes.start_date_type === 'relative' &&
									<TextControl
										placeholder={ __( 'Relative Date', 'gravityview' ) }
										value={ attributes.start_date }
										onChange={ start_date => {
											setAttributes( {
												start_date,
											} );
										} }
									/>
								}

							</div>
							<hr />
							<div style={ { position: 'relative' } }>
								<label style={ {
									marginBottom: 10,
									display: 'block',
								} }>{ __( 'End Date', 'gravityview' ) }</label>
								<ButtonGroup
									className="btn-group-double">
									<Button
										isDefault
										isPrimary={ attributes.end_date_type === 'static' }
										onClick={ () => {
											setAttributes( {
												end_date_type: 'static',
											} );
										} }

									>
										{ __( 'Static (Calendar)', 'gravityview' ) }
									</Button>
									<Button
										isDefault
										isPrimary={ attributes.end_date_type === 'relative' }
										onClick={ () => {
											setAttributes( {
												end_date_type: 'relative',
											} );
										} }

									>
										{ __( 'Relative', 'gravityview' ) }
									</Button>
								</ButtonGroup>
								{
									attributes.end_date_type === 'static' &&
									<DatePicker
										dateFormat="yyyy-MM-dd"
										selected={ isEndDateValid ? moment( attributes.end_date ).toDate() : '' }
										onChange={ end_date => {
											setAttributes( {
												end_date: moment( end_date ).format( 'YYYY-MM-DD' ),
											} );
										} } />
								}
								{
									attributes.end_date_type === 'relative' &&
									<TextControl
										placeholder={ __( 'Relative Date', 'gravityview' ) }
										value={ attributes.end_date }
										onChange={ end_date => {
											setAttributes( {
												end_date,
											} );
										} }
									/>
								}

							</div>
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
					attributes.id !== '' && attributes.id !== 'Select a View' &&
					<PanelBody
						initialOpen={ false }
						title={ __( 'More Settings', 'gravityview' ) }>
						<TextControl
							label={ __( 'Page Size', 'gravityview' ) }
							value={ attributes.page_size }
							type="number"
							min="0"
							onChange={ page_size => {
								setAttributes( {
									page_size,
								} );
							} }
						/>
						<hr />
						<TextControl
							label={ __( 'Sort Field', 'gravityview' ) }
							value={ attributes.sort_field }
							onChange={ sort_field => {
								setAttributes( {
									sort_field,
								} );
							} }
						/>
						<hr />
						<SelectControl
							label={ __( 'Sort Direction', 'gravityview' ) }
							value={ attributes.sort_direction }
							options={ [
								{
									value: 'ASC',
									label: __( 'Ascending', 'gravityview', 'gravityview' ),
								},
								{
									value: 'DESC',
									label: __( 'Descending', 'gravityview', 'gravityview' ),
								},
							] }
							onChange={ sort_direction => {
								setAttributes( {
									sort_direction,
								} );
							} }
						/>
						<hr />
						<TextControl
							label={ __( 'Search Field', 'gravityview' ) }
							value={ attributes.search_field }
							onChange={ search_field => {
								setAttributes( {
									search_field,
								} );
							} }
						/>
						<hr />
						<TextControl
							label={ __( 'Search Value', 'gravityview' ) }
							value={ attributes.search_value }
							onChange={ search_value => {
								setAttributes( {
									search_value,
								} );
							} }
						/>
						<hr />
						<SelectControl
							label={ __( 'Search Operator', 'gravityview' ) }
							value={ attributes.search_operator }
							options={ [
								{ value: 'is', label: 'is' },
								{ value: 'isnot', label: 'isnot' },
								{ value: '<>', label: '<>' },
								{ value: 'not in', label: 'not in' },
								{ value: 'in', label: 'in' },
								{ value: '>', label: '>' },
								{ value: '<', label: '<' },
								{ value: 'contains', label: 'contains' },
								{ value: 'starts_with', label: 'starts_with' },
								{ value: 'ends_with', label: 'ends_with' },
								{ value: 'like', label: 'like' },
								{ value: '>=', label: '>=' },
								{ value: '<=', label: '<=' },
							] }
							onChange={ search_operator => {
								setAttributes( {
									search_operator,
								} );
							} }
						/>
						<hr />
						<TextControl
							label={ __( 'Class', 'gravityview' ) }
							value={ attributes.class }
							onChange={ classVal => {
								setAttributes( {
									class: classVal,
								} );
							} }
						/>
						<hr />
						<TextControl
							label={ __( 'Offset', 'gravityview' ) }
							value={ attributes.offset }
							type="number"
							min="0"
							onChange={ offset => {
								setAttributes( {
									offset,
								} );
							} }
						/>
						<hr />
						<TextControl
							label={ __( 'Single Title', 'gravityview' ) }
							value={ attributes.single_title }
							onChange={ single_title => {
								setAttributes( {
									single_title,
								} );
							} }
						/>
						<hr />
						<TextControl
							label={ __( 'Back Link Label', 'gravityview' ) }
							value={ attributes.back_link_label }
							onChange={ back_link_label => {
								setAttributes( {
									back_link_label,
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
					</PanelBody>
				}
			</InspectorControls>
		);
	}
}
