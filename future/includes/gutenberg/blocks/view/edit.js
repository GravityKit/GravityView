import { __, _x } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { BaseControl, TextControl, SelectControl, Panel, PanelBody, ButtonGroup, Button } from '@wordpress/components';

import moment from 'moment';
import DatePicker from 'react-datepicker';

import ViewSelector from 'shared/js/view-selector';
import SortFieldSelector from 'shared/js/sort-selector';
import PostSelector from 'shared/js/post-selector';
import PreviewControl from 'shared/js/preview-control';
import PreviewAsShortcodeControl from 'shared/js/preview-as-shortcode-control';
import ServerSideRender from 'shared/js/server-side-render';
import NoViewsNotice from 'shared/js/no-views-notice';
import Disabled from 'shared/js/disabled';

import './editor.scss';

/*global gkGravityViewBlocks*/
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
		previewAsShortcode,
		showPreviewImage,
	} = attributes;

	const previewImage = gkGravityViewBlocks[ blockName ]?.previewImage && <img className="preview-image" src={ gkGravityViewBlocks[ blockName ]?.previewImage } alt={ __( 'Block preview image.', 'gk-gravityview' ) } />;

	if ( previewImage && showPreviewImage ) {
		return previewImage;
	}

	if ( !gkGravityViewBlocks?.views?.length ) {
		return <NoViewsNotice blockPreviewImage={ previewImage } newViewUrl={ gkGravityViewBlocks?.create_new_view_url } />;
	}

	const shouldPreview = ( previewBlock && viewId );

	const isStartDateValid = ( startDate || '' ).indexOf( '-' ) > 0 && moment( startDate ).isValid();

	const isEndDateValid = ( endDate || '' ).indexOf( '-' ) > 0 && moment( endDate ).isValid();

	const displayPreviewContent = ( content ) => {
		const contentEl = document.createElement( 'div' );

		contentEl.innerHTML = content;

		[...contentEl.getElementsByTagName('script')].forEach(el => el.remove());

		if ( /gv-map-container/.test( content ) ) {
			[ ...contentEl.querySelectorAll( '.gv-map-canvas' ) ].forEach( el => {
				el.innerHTML = `
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 232597 333333" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" image-rendering="optimizeQuality" fill-rule="evenodd" clip-rule="evenodd"><path d="M151444 5419C140355 1916 128560 0 116311 0 80573 0 48591 16155 27269 41534l54942 46222 69232-82338z" fill="#1a73e8"/><path d="M27244 41534C10257 61747 0 87832 0 116286c0 21876 4360 39594 11517 55472l70669-84002-54942-46222z" fill="#ea4335"/><path d="M116311 71828c24573 0 44483 19910 44483 44483 0 10938-3957 20969-10509 28706 0 0 35133-41786 69232-82313-14089-27093-38510-47936-68048-57286L82186 87756c8166-9753 20415-15928 34125-15928z" fill="#4285f4"/><path d="M116311 160769c-24573 0-44483-19910-44483-44483 0-10863 3906-20818 10358-28555l-70669 84027c12072 26791 32159 48289 52851 75381l85891-102122c-8141 9628-20339 15752-33948 15752z" fill="#fbbc04"/><path d="M148571 275014c38787-60663 84026-88210 84026-158728 0-19331-4738-37552-13080-53581L64393 247140c6578 8620 13206 17793 19683 27900 23590 36444 17037 58294 32260 58294 15172 0 8644-21876 32235-58320z" fill="#34a853"/></svg>
					<p>
						${ __( 'Map is not available in the Block preview', 'gk-gravityview' ) }
					</p>`;
			} );
		}

		if ( /gv-datatables/.test( content ) ) {
			[ ...contentEl.querySelectorAll( 'table.gv-datatables' ) ].forEach( el => {
				const tbody = document.createElement( 'tbody' );

				tbody.innerHTML = `
					<tr>
						<td colspan="${ el.querySelectorAll( 'th' ).length }">
							${ __( 'Entries from the DataTables layout are not available in the Block preview', 'gk-gravityview' ) }
						</td>
					</tr>`;

				el.querySelector( 'thead' ).appendChild( tbody );
			} );
		}

		return <div dangerouslySetInnerHTML={ { __html: contentEl.innerHTML } } />;
	};

	/**
	 * Sets the selected View from the ViewSelect object.
	 *
	 * @since 2.21.2
	 *
	 * @param {number} _viewId The View ID.
	 */
	function selectView( _viewId ) {
		const selectedView = gkGravityViewBlocks.views.find( option => option.value === _viewId );

		setAttributes( {
			viewId: _viewId,
			secret: selectedView?.secret,
			previewBlock: previewBlock && ! _viewId ? false : previewBlock,
		} );
	}

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<div className="gk-gravityview-blocks">
					<Panel>
						<PanelBody title={ __( 'Main Settings', 'gk-gravityview' ) } initialOpen={ true }>
							<ViewSelector
								viewId={ viewId }
								isSidebar={ true }
								onChange={ selectView }
							/>

							<PreviewControl
								disabled={ !viewId }
								preview={ previewBlock }
								onChange={ ( previewBlock ) => setAttributes( { previewBlock } ) }
							/>
						</PanelBody>

						<PanelBody title={ __( 'Entries Settings', 'gk-gravityview' ) } initialOpen={ false }>
							<Disabled isDisabled={ !viewId }>
								<BaseControl label={ __( 'Start Date', 'gk-gravityview' ) }>
									<ButtonGroup className="btn-group-double">
										<Button
											isSecondary={ startDateType !== 'date' }
											isPrimary={ startDateType === 'date' }
											onClick={ () => setAttributes( { startDateType: 'date' } ) }
										>
											{ __( 'Calendar Date', 'gk-gravityview' ) }
										</Button>

										<Button
											isSecondary={ startDateType !== 'relative' }
											isPrimary={ startDateType === 'relative' }
											onClick={ () => setAttributes( { startDateType: 'relative' } ) }
										>
											{ __( 'Relative Date', 'gk-gravityview' ) }
										</Button>
									</ButtonGroup>

									{ startDateType === 'date' && <>
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
											placeholder={ _x( 'now, today, +1 day', 'Examples of relative dates.', 'gk-gravityview' ) }
											value={ startDate }
											onChange={ ( startDate ) => setAttributes( { startDate } ) }
										/>
									</> }
								</BaseControl>

								<BaseControl label={ __( 'End Date', 'gk-gravityview' ) }>
									<ButtonGroup className="btn-group-double">
										<Button
											isSecondary={ endDateType !== 'date' }
											isPrimary={ endDateType === 'date' }
											onClick={ () => setAttributes( { endDateType: 'date' } ) }
										>
											{ __( 'Calendar Date', 'gk-gravityview' ) }
										</Button>

										<Button
											isSecondary={ endDateType !== 'relative' }
											isPrimary={ endDateType === 'relative' }
											onClick={ () => setAttributes( { endDateType: 'relative' } ) }
										>
											{ __( 'Relative Date', 'gk-gravityview' ) }
										</Button>
									</ButtonGroup>

									{ endDateType === 'date' && <>
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
											placeholder={ _x( 'now, today, +1 day', 'Examples of relative dates.', 'gk-gravityview' ) }
											value={ endDate }
											onChange={ ( endDate ) => setAttributes( { endDate } ) }
										/>
									</> }
								</BaseControl>
							</Disabled>
						</PanelBody>

						<PanelBody title={ __( 'Extra Settings', 'gk-gravityview' ) } initialOpen={ false }>
							<Disabled isDisabled={ !viewId }>
								<TextControl
									label={ __( 'Page Size', 'gk-gravityview' ) }
									value={ pageSize }
									type="number"
									min="0"
									onChange={ ( pageSize ) => setAttributes( { pageSize } ) }
								/>

								<SortFieldSelector
									viewId={ viewId }
									onChange={ (sortField) => setAttributes({sortField}) }
									sortField={sortField}
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
							</Disabled>

							<Disabled isDisabled={ !viewId || !searchField }>
								<div style={ { marginBottom: '24px' } }>
									<TextControl
										label={ __( 'Search Value', 'gk-gravityview' ) }
										value={ searchValue }
										onChange={ ( searchValue ) => setAttributes( { searchValue } ) }
									/>
								</div>
							</Disabled>

							<Disabled isDisabled={ !viewId || !searchField || !searchValue }>
								<div style={ { marginBottom: '24px' } }>
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
								</div>
							</Disabled>

							<Disabled isDisabled={ !viewId }>
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
									onChange={ ( postId ) => setAttributes( { postId } ) }
								/>
							</Disabled>
						</PanelBody>
					</Panel>
				</div>
			</InspectorControls>

			<PreviewAsShortcodeControl
				previewAsShortcode={ previewAsShortcode }
				disabled={ !previewBlock }
				onChange={ ( previewAsShortcode ) => setAttributes( { previewAsShortcode } ) }
			/>

			{ !shouldPreview && <>
				<div className="block-editor">
					{ previewImage }

					<ViewSelector
						viewId={ viewId }
						onChange={ selectView }
					/>

					<PreviewControl
						disabled={ !viewId }
						preview={ previewBlock }
						onChange={ ( previewBlock ) => setAttributes( { previewBlock } ) }
					/>
				</div>
			</> }

			{ shouldPreview && <>
				<div className="block-preview">
					<Disabled isDisabled={ true } toggleOpacity={ false }>
						<ServerSideRender
							block={ blockName }
							attributes={ attributes }
							dataType="json"
							loadStyles={ true }
							blockPreviewImage={ previewImage }
							onResponse={ displayPreviewContent }
						/>
					</Disabled>
				</div>
			</> }
		</div>
	);
}
