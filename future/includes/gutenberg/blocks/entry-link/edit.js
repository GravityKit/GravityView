import { __, _x } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Panel, PanelBody, SelectControl, TextControl, Spinner } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import ViewSelector from 'shared/js/view-selector';
import PostSelector from 'shared/js/post-selector';

import './editor.scss';

export default function Edit( { attributes, setAttributes, name: blockName } ) {
	const {
		view_id: viewId,
		entry_id: entryId,
		post_id: postId,
		return_format: returnFormat,
		link_atts: linkAtts,
		field_values: fieldValues,
		action,
		content,
		blockPreview
	} = attributes;

	const shouldPreview = ( viewId && entryId );

	const selectFromBlockControlsLabel = _x( 'Please select [control] from the block controls.', '[control] placeholder should not be translated and will be replaced with "an Entry ID" or "a View ID" text.', 'gk-gravityview' );

	const previewImage = gkGravityViewBlocks[ blockName ]?.previewImage;

	const showBlockPreviewImage = () => <img className="gk-gravityview-block block-preview" src={ previewImage } alt={ __( 'Block preview image.', 'gk-gravityview' ) } />;

	if ( blockPreview && previewImage ) {
		return showBlockPreviewImage();
	}

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<Panel>
					<PanelBody title={ __( 'Main Settings', 'gk-gravityview' ) } initialOpen={ true }>
						<ViewSelector
							id="gk-gravityview-block-view-selector"
							onChange={ ( view_id ) => { setAttributes( { view_id, entry_id: '' } ); } }
						/>

						{ viewId !== '' && <>
							<TextControl
								label={ __( 'Entry ID', 'gk-gravityview' ) }
								value={ entryId }
								type="number"
								min="1"
								onChange={ ( entry_id ) => setAttributes( { entry_id } ) }
							/>
						</> }
					</PanelBody>

					{ entryId !== '' && <>
						<PanelBody title={ __( 'Link Settings', 'gk-gravityview' ) } initialOpen={ false }>
							<SelectControl
								label={ __( 'Link Action', 'gk-gravityview' ) }
								value={ action }
								options={
									[
										{ value: 'read', label: __( 'View Details', 'gk-gravityview' ) },
										{ value: 'edit', label: __( 'Edit Entry', 'gk-gravityview' ) },
										{ value: 'delete', label: __( 'Delete Entry', 'gk-gravityview' ) },
									]
								}
								onChange={ ( val ) => setAttributes( { action: val } ) }
							/>

							<TextControl
								label={ __( 'Link Text', 'gk-gravityview' ) }
								value={ content }
								onChange={ ( val ) => setAttributes( { content: val } ) }
							/>
						</PanelBody>

						<PanelBody title={ __( 'Extra Settings', 'gk-gravityview' ) } initialOpen={ false }>
							<SelectControl
								label={ __( 'Return Format', 'gk-gravityview' ) }
								value={ returnFormat }
								options={ [
									{ value: 'html', label: __( 'HTML', 'gk-gravityview' ) },
									{ value: 'url', label: __( 'URL', 'gk-gravityview' ) },
								] }
								onChange={ ( return_format ) => setAttributes( { return_format } ) }
							/>

							<PostSelector postId={ postId } onChange={ ( post_id ) => { setAttributes( { post_id } );} } />

							<TextControl
								label={ __( 'Link Attributes', 'gk-gravityview' ) }
								value={ linkAtts }
								onChange={ ( link_atts ) => setAttributes( { link_atts } ) }
							/>

							<TextControl
								label={ __( 'Field Values', 'gk-gravityview' ) }
								value={ fieldValues }
								onChange={ ( field_values ) => setAttributes( { field_values } ) }
							/>
						</PanelBody>
					</> }
				</Panel>
			</InspectorControls>

			{ !shouldPreview && <>
				<div className="gk-gravityview-block shortcode-preview">
					{ previewImage && showBlockPreviewImage() }

					<div className="field-container">
						{ !viewId && <p>{ selectFromBlockControlsLabel.replace( '[control]', __( 'a View ID', 'gk-gravityview' ) ) }</p> }
						{ ( viewId && !entryId ) && <p>{ selectFromBlockControlsLabel.replace( '[control]', __( 'an Entry ID', 'gk-gravityview' ) ) }</p> }
					</div>
				</div>
			</> }

			{ shouldPreview && <>
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
