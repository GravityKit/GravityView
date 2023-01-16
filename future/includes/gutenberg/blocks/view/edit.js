import { __, _x } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { BaseControl, TextControl, SelectControl, Panel, PanelBody, ButtonGroup, Button, Spinner } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import moment from 'moment';
import DatePicker from 'react-datepicker';

import ViewSelector from 'shared/js/view-selector';
import PostSelector from 'shared/js/post-selector';

import './editor.scss';

export default function Edit( { attributes, setAttributes, name: blockName } ) {
	const {
		view_id: viewId,
		post_id: postId,
		start_date: startDate,
		start_date_type: startDateType,
		end_date: endDate,
		end_date_type: endDateType,
		page_size: pageSize,
		sort_field: sortField,
		sort_direction: sortDirection,
		search_field: searchField,
		search_value: searchValue,
		search_operator: searchOperator,
		class_value: classValue,
		offset,
		single_title: singleTitle,
		back_link_label: backLinkLabel,
		blockPreview,
	} = attributes;

	const selectFromBlockControlsLabel = _x( 'Please select [control] from the block controls.', '[control] placeholder should not be translated and will be replaced with "an Entry ID" or "a View ID" text.', 'gk-gravityview' );

	const previewImage = gkGravityViewBlocks[ blockName ]?.previewImage;

	const showBlockPreviewImage = () => <img className="gk-gravityview-block block-preview" src={ previewImage } alt={ __( 'Block preview image.', 'gk-gravityview' ) } />;

	if ( blockPreview && previewImage ) {
		return showBlockPreviewImage();
	}

	const isStartDateValid = ( startDate || '' ).indexOf( '-' ) > 0 && moment( startDate ).isValid();

	const isEndDateValid = ( endDate || '' ).indexOf( '-' ) > 0 && moment( endDate ).isValid();

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<Panel>
					<PanelBody title={ __( 'Main Settings', 'gk-gravityview' ) } initialOpen={ true }>
						<ViewSelector
							viewId={ viewId }
							onChange={ ( view_id ) => { setAttributes( { view_id, entry_id: '' } ); } }
						/>

						{ viewId && <>
							<BaseControl label={ __( 'Start Date', 'gk-gravityview' ) }>
								<ButtonGroup className="gk-gravityview-block btn-group-double">
									<Button
										isSecondary={ startDateType !== 'static' }
										isPrimary={ startDateType === 'static' }
										onClick={ () => setAttributes( { start_date_type: 'static' } ) }
									>
										{ __( 'Static (Calendar)', 'gk-gravityview' ) }
									</Button>

									<Button
										isSecondary={ startDateType !== 'relative' }
										isPrimary={ startDateType === 'relative' }
										onClick={ () => setAttributes( { start_date_type: 'relative' } ) }
									>
										{ __( 'Relative', 'gk-gravityview' ) }
									</Button>
								</ButtonGroup>

								{ startDateType === 'static' && <>
									<BaseControl label={ __( 'Date', 'gk-gravityview' ) }>
										<DatePicker
											dateFormat="yyyy-MM-dd"
											selected={ isStartDateValid ? moment( startDate ).toDate() : '' }
											onChange={ ( start_date ) => setAttributes( { start_date: moment( start_date ).format( 'YYYY-MM-DD' ) } ) }
										/>
									</BaseControl>
								</> }

								{ startDateType === 'relative' && <>
									<TextControl
										label={ __( 'Relative Date', 'gk-gravityview' ) }
										placeholder={ __( 'Relative Date', 'gk-gravityview' ) }
										value={ startDate }
										onChange={ ( start_date ) => setAttributes( { start_date } ) }
									/>
								</> }
							</BaseControl>

							<BaseControl label={ __( 'End Date', 'gk-gravityview' ) }>
								<ButtonGroup className="gk-gravityview-block btn-group-double">
									<Button
										isSecondary={ endDateType !== 'static' }
										isPrimary={ endDateType === 'static' }
										onClick={ () => setAttributes( { end_date_type: 'static' } ) }
									>
										{ __( 'Static (Calendar)', 'gk-gravityview' ) }
									</Button>

									<Button
										isSecondary={ endDateType !== 'relative' }
										isPrimary={ endDateType === 'relative' }
										onClick={ () => setAttributes( { end_date_type: 'relative' } ) }
									>
										{ __( 'Relative', 'gk-gravityview' ) }
									</Button>
								</ButtonGroup>

								{ endDateType === 'static' && <>
									<BaseControl label={ __( 'Date', 'gk-gravityview' ) }>
										<DatePicker
											dateFormat="yyyy-MM-dd"
											selected={ isEndDateValid ? moment( endDate ).toDate() : '' }
											onChange={ ( end_date ) => setAttributes( { end_date: moment( end_date ).format( 'YYYY-MM-DD' ) } ) }
										/>
									</BaseControl>
								</> }

								{ endDateType === 'relative' && <>
									<TextControl
										label={ __( 'Relative Date', 'gk-gravityview' ) }
										placeholder={ __( 'Relative Date', 'gk-gravityview' ) }
										value={ endDate }
										onChange={ ( end_date ) => setAttributes( { end_date } ) }
									/>
								</> }
							</BaseControl>
						</> }
					</PanelBody>

					{ viewId && <>
						<PanelBody title={ __( 'Extra Settings', 'gk-gravityview' ) } initialOpen={ false }>
							<TextControl
								label={ __( 'Page Size', 'gk-gravityview' ) }
								value={ pageSize }
								type="number"
								min="0"
								onChange={ ( page_size ) => setAttributes( { page_size } ) }
							/>

							<TextControl
								label={ __( 'Sort Field', 'gk-gravityview' ) }
								value={ sortField }
								type="number"
								min="1"
								onChange={ ( sort_field ) => setAttributes( { sort_field } ) }
							/>

							<SelectControl
								label={ __( 'Sort Direction', 'gk-gravityview' ) }
								value={ sortDirection }
								options={ [
									{ value: 'ASC', label: __( 'Ascending', 'gk-gravityview' ) },
									{ value: 'DESC', label: __( 'Descending', 'gk-gravityview' ) },
								] }
								onChange={ ( sort_direction ) => setAttributes( { sort_direction } ) }
							/>

							<TextControl
								label={ __( 'Search Field', 'gk-gravityview' ) }
								value={ searchField }
								onChange={ ( search_field ) => setAttributes( { search_field } ) }
							/>

							<TextControl
								label={ __( 'Search Value', 'gk-gravityview' ) }
								value={ searchValue }
								onChange={ ( search_value ) => setAttributes( { search_value } ) }
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
								onChange={ ( search_operator ) => setAttributes( { search_operator } ) }
							/>

							<TextControl
								label={ _x( 'Class', 'Denotes CSS class', 'gk-gravityview' ) }
								value={ classValue }
								onChange={ ( class_value ) => setAttributes( { class_value } ) }
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
								onChange={ ( single_title ) => setAttributes( { single_title } ) }
							/>

							<TextControl
								label={ __( 'Back Link Label', 'gk-gravityview' ) }
								value={ backLinkLabel }
								onChange={ ( back_link_label ) => setAttributes( { back_link_label } ) }
							/>

							<PostSelector postId={ postId } onChange={ ( post_id ) => { setAttributes( { post_id } );} } />
						</PanelBody>
					</> }
				</Panel>
			</InspectorControls>

			{ !viewId && <>
				<div className="gk-gravityview-block shortcode-preview">
					{ previewImage && showBlockPreviewImage() }

					<div className="field-container">
						<p>{ selectFromBlockControlsLabel.replace( '[control]', __( 'a View ID', 'gk-gravityview' ) ) }</p>
					</div>
				</div>
			</> }

			{ viewId && <>
				<ServerSideRender
					LoadingResponsePlaceholder={ () => <>
						{ __( 'Rendering preview...', 'gk-gravityview' ) }
						<Spinner />
					</> }
					block={ blockName }
					attributes={ attributes }
				/>
			</> }
		</div>
	);
}
