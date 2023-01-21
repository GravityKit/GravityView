import { __, _x } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { BaseControl, TextControl, SelectControl, Panel, PanelBody, ButtonGroup, Button, Spinner, Disabled } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import moment from 'moment';
import DatePicker from 'react-datepicker';

import ViewSelector from 'shared/js/view-selector';
import PostSelector from 'shared/js/post-selector';
import PreviewControl from 'shared/js/preview-control';

import './editor.scss';

export default function Edit( { attributes, setAttributes, name: blockName } ) {
	const {
		viewId,
		postId,
		startDate,
		startDateType,
		endDate,
		endDateType,
		pageSize,
		sortField,
		sortDirection,
		searchField,
		searchValue,
		searchOperator,
		classValue,
		offset,
		singleTitle,
		backLinkLabel,
		previewBlock,
		showPreviewImage
	} = attributes;

	const previewImage = gkGravityViewBlocks[ blockName ]?.previewImage && <img className="preview-image" src={ gkGravityViewBlocks[ blockName ]?.previewImage } alt={ __( 'Block preview image.', 'gk-gravityview' ) } />;

	if ( previewImage && showPreviewImage ) {
		return previewImage;
	}

	const shouldPreview = ( previewBlock && viewId );

	const isStartDateValid = ( startDate || '' ).indexOf( '-' ) > 0 && moment( startDate ).isValid();

	const isEndDateValid = ( endDate || '' ).indexOf( '-' ) > 0 && moment( endDate ).isValid();

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<div className="gk-gravityview-blocks">
					<Panel>
						<PanelBody title={ __( 'Main Settings', 'gk-gravityview' ) } initialOpen={ true }>
							<ViewSelector
								viewId={ viewId }
								onChange={ ( viewId ) => { setAttributes( { viewId } ); } }
							/>

							<Disabled isDisabled={ !viewId }>
								<PreviewControl
									disabled={ !viewId }
									preview={ previewBlock }
									onChange={ ( previewBlock ) => { setAttributes( { previewBlock } ); } }
								/>
							</Disabled>
						</PanelBody>

						<Disabled isDisabled={ !viewId }>
							<PanelBody title={ __( 'Entries Settings', 'gk-gravityview' ) } initialOpen={ false }>
								<Disabled isDisabled={ !viewId }>
									<BaseControl label={ __( 'Start Date', 'gk-gravityview' ) }>
										<ButtonGroup className="btn-group-double">
											<Button
												isSecondary={ startDateType !== 'static' }
												isPrimary={ startDateType === 'static' }
												onClick={ () => setAttributes( { startDateType: 'static' } ) }
											>
												{ __( 'Static (Calendar)', 'gk-gravityview' ) }
											</Button>

											<Button
												isSecondary={ startDateType !== 'relative' }
												isPrimary={ startDateType === 'relative' }
												onClick={ () => setAttributes( { startDateType: 'relative' } ) }
											>
												{ __( 'Relative', 'gk-gravityview' ) }
											</Button>
										</ButtonGroup>

										{ startDateType === 'static' && <>
											<BaseControl label={ __( 'Date', 'gk-gravityview' ) }>
												<DatePicker
													dateFormat="yyyy-MM-dd"
													selected={ isStartDateValid ? moment( startDate ).toDate() : '' }
													onChange={ ( startDate ) => setAttributes( { startDate: moment( startDate ).format( 'YYYY-MM-DD' ) } ) }
												/>
											</BaseControl>
										</> }

										{ startDateType === 'relative' && <>
											<TextControl
												label={ __( 'Relative Date', 'gk-gravityview' ) }
												placeholder={ __( 'Relative Date', 'gk-gravityview' ) }
												value={ startDate }
												onChange={ ( startDate ) => setAttributes( { startDate } ) }
											/>
										</> }
									</BaseControl>

									<BaseControl label={ __( 'End Date', 'gk-gravityview' ) }>
										<ButtonGroup className="btn-group-double">
											<Button
												isSecondary={ endDateType !== 'static' }
												isPrimary={ endDateType === 'static' }
												onClick={ () => setAttributes( { endDateType: 'static' } ) }
											>
												{ __( 'Static (Calendar)', 'gk-gravityview' ) }
											</Button>

											<Button
												isSecondary={ endDateType !== 'relative' }
												isPrimary={ endDateType === 'relative' }
												onClick={ () => setAttributes( { endDateType: 'relative' } ) }
											>
												{ __( 'Relative', 'gk-gravityview' ) }
											</Button>
										</ButtonGroup>

										{ endDateType === 'static' && <>
											<BaseControl label={ __( 'Date', 'gk-gravityview' ) }>
												<DatePicker
													dateFormat="yyyy-MM-dd"
													selected={ isEndDateValid ? moment( endDate ).toDate() : '' }
													onChange={ ( endDate ) => setAttributes( { endDate: moment( endDate ).format( 'YYYY-MM-DD' ) } ) }
												/>
											</BaseControl>
										</> }

										{ endDateType === 'relative' && <>
											<TextControl
												label={ __( 'Relative Date', 'gk-gravityview' ) }
												placeholder={ __( 'Relative Date', 'gk-gravityview' ) }
												value={ endDate }
												onChange={ ( endDate ) => setAttributes( { endDate } ) }
											/>
										</> }
									</BaseControl>
								</Disabled>
							</PanelBody>

							<PanelBody title={ __( 'Extra Settings', 'gk-gravityview' ) } initialOpen={ false }>
								<TextControl
									label={ __( 'Page Size', 'gk-gravityview' ) }
									value={ pageSize }
									type="number"
									min="0"
									onChange={ ( pageSize ) => setAttributes( { pageSize } ) }
								/>

								<TextControl
									label={ __( 'Sort Field', 'gk-gravityview' ) }
									value={ sortField }
									type="number"
									min="1"
									onChange={ ( sortField ) => setAttributes( { sortField } ) }
								/>

								<SelectControl
									label={ __( 'Sort Direction', 'gk-gravityview' ) }
									value={ sortDirection }
									options={ [
										{ value: 'ASC', label: __( 'Ascending', 'gk-gravityview' ) },
										{ value: 'DESC', label: __( 'Descending', 'gk-gravityview' ) },
									] }
									onChange={ ( sortDirection ) => setAttributes( { sortDirection } ) }
								/>

								<TextControl
									label={ __( 'Search Field', 'gk-gravityview' ) }
									value={ searchField }
									onChange={ ( searchField ) => setAttributes( { searchField } ) }
								/>

								<TextControl
									label={ __( 'Search Value', 'gk-gravityview' ) }
									value={ searchValue }
									onChange={ ( searchValue ) => setAttributes( { searchValue } ) }
								/>

								<SelectControl
									label={ __( 'Search Operator', 'gk-gravityview' ) }
									value={ searchOperator }
									options={ [
										{ value: 'is', label: _x( 'Is', 'Denotes search operator "is".', 'gk-gravityview' ) },
										{ value: 'isnot', label: _x( 'Is Not', 'Denotes search operator "isnot".', 'gk-gravityview' ) },
										{ value: '<>', label: _x( 'Not Equal', 'Denotes search operator "<>".', 'gk-gravityview' ) },
										{ value: 'not in', label: _x( 'Not In', 'Denotes search operator "not in".', 'gk-gravityview' ) },
										{ value: 'in', label: _x( 'In', 'Denotes search operator "in".', 'gk-gravityview' ) },
										{ value: '>', label: _x( 'Greater', 'Denotes search operator ">".', 'gk-gravityview' ) },
										{ value: '<', label: _x( 'Lesser', 'Denotes search operator "<".', 'gk-gravityview' ) },
										{ value: 'contains', label: _x( 'Contains', 'Denotes search operator "contains".', 'gk-gravityview' ) },
										{ value: 'starts_with', label: _x( 'Starts With', 'Denotes search operator "starts_with".', 'gk-gravityview' ) },
										{ value: 'ends_with', label: _x( 'Ends With', 'Denotes search operator "ends_with".', 'gk-gravityview' ) },
										{ value: 'like', label: _x( 'Like', 'Denotes search operator "like".', 'gk-gravityview' ) },
										{ value: '>=', label: _x( 'Greater Or Equal', 'Denotes search operator ">=".', 'gk-gravityview' ) },
										{ value: '<=', label: _x( 'Lesser Or Equal', 'Denotes search operator "<=".', 'gk-gravityview' ) },
									] }
									onChange={ ( searchOperator ) => setAttributes( { searchOperator } ) }
								/>

								<TextControl
									label={ _x( 'Class', 'Denotes CSS class', 'gk-gravityview' ) }
									value={ classValue }
									onChange={ ( classValue ) => setAttributes( { classValue } ) }
								/>

								<TextControl
									label={ __( 'Offset', 'gk-gravityview' ) }
									value={ offset }
									type="number"
									min="0"
									onChange={ ( val ) => setAttributes( { offset: val } ) }
								/>

								<TextControl
									label={ __( 'Single Title', 'gk-gravityview' ) }
									value={ singleTitle }
									onChange={ ( singleTitle ) => setAttributes( { singleTitle } ) }
								/>

								<TextControl
									label={ __( 'Back Link Label', 'gk-gravityview' ) }
									value={ backLinkLabel }
									onChange={ ( backLinkLabel ) => setAttributes( { backLinkLabel } ) }
								/>

								<PostSelector
									postId={ postId }
									onChange={ ( postId ) => { setAttributes( { postId } );} }
								/>
							</PanelBody>
						</Disabled>
					</Panel>
				</div>
			</InspectorControls>

			{ !shouldPreview && <>
				<div className="block-editor">
					{ previewImage }

					<div>
						<ViewSelector
							viewId={ viewId }
							onChange={ ( viewId ) => { setAttributes( { viewId } ); } }
						/>

						<PreviewControl
							disabled={ !viewId }
							preview={ previewBlock }
							onChange={ ( previewBlock ) => { setAttributes( { previewBlock } ); } }
						/>
					</div>
				</div>
			</> }

			{ shouldPreview && <>
				<ServerSideRender
					className="block-preview"
					LoadingResponsePlaceholder={ () => <>
						{ __( 'Previewing...', 'gk-gravityview' ) }
						<Spinner />
					</> }
					block={ blockName }
					attributes={ attributes }
				/>
			</> }
		</div>
	);
}
