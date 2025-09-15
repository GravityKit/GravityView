import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Panel, PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';

import ViewSelector from 'shared/js/view-selector';
import EntrySelector from 'shared/js/entry-selector';
import PostSelector from 'shared/js/post-selector';
import PreviewControl from 'shared/js/preview-control';
import PreviewAsShortcodeControl from 'shared/js/preview-as-shortcode-control';
import ServerSideRender from 'shared/js/server-side-render';
import NoViewsNotice from 'shared/js/no-views-notice';
import Disabled from 'shared/js/disabled';

import './editor.scss';

export default function Edit( { attributes, setAttributes, name: blockName } ) {
	const {
		viewId,
		entryId,
		postId,
		returnFormat,
		linkAtts,
		fieldValues,
		action,
		content,
		secret,
		previewBlock,
		previewAsShortcode,
		showPreviewImage
	} = attributes;

	// For blocks saved before the secret attribute was added, populate it from the View data.
	useEffect( () => {
		if ( viewId && !secret && gkGravityViewBlocks?.views ) {
			const selectedView = gkGravityViewBlocks.views.find( option => option.value === viewId );
			if ( selectedView && selectedView.secret ) {
				setAttributes( { secret: selectedView.secret } );
			}
		}
	}, [ viewId, secret ] );

	const previewImage = gkGravityViewBlocks[ blockName ]?.previewImage && <img className="preview-image" src={ gkGravityViewBlocks[ blockName ]?.previewImage } alt={ __( 'Block preview image.', 'gk-gravityview' ) } />;

	if ( previewImage && showPreviewImage ) {
		return previewImage;
	}

	if ( !gkGravityViewBlocks?.views?.length ) {
		return <NoViewsNotice blockPreviewImage={ previewImage } newViewUrl={ gkGravityViewBlocks?.create_new_view_url } />;
	}

	const shouldPreview = ( previewBlock && viewId && entryId );

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<div className="gk-gravityview-blocks">
					<Panel>
						<PanelBody title={ __( 'Main Settings', 'gk-gravityview' ) } initialOpen={ true }>
							<ViewSelector
								viewId={ viewId }
								isSidebar={ true }
								onChange={ ( viewId ) => {
									const selectedView = gkGravityViewBlocks.views.find( option => option.value === viewId );
									setAttributes( {
										viewId,
										secret: selectedView?.secret,
										previewBlock: false,
										entryId: ''
									} );
								} }
							/>

							<EntrySelector
								disabled={ !viewId }
								entryId={ entryId }
								onChange={ ( entryId ) => setAttributes( { entryId } ) }
							/>

							<PreviewControl
								disabled={ !viewId || !entryId }
								preview={ previewBlock }
								onChange={ ( previewBlock ) => setAttributes( { previewBlock } ) }
							/>
						</PanelBody>

						<PanelBody title={ __( 'Link Settings', 'gk-gravityview' ) } initialOpen={ false }>
							<Disabled isDisabled={ !entryId }>
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

								<SelectControl
									label={ __( 'Format', 'gk-gravityview' ) }
									value={ returnFormat }
									options={ [
										{ value: 'html', label: __( 'HTML', 'gk-gravityview' ) },
										{ value: 'url', label: __( 'URL', 'gk-gravityview' ) },
									] }
									onChange={ ( returnFormat ) => setAttributes( { returnFormat } ) }
								/>
							</Disabled>

							<Disabled isDisabled={ !entryId || returnFormat === 'url' }>
								<TextControl
									label={ __( 'Link Text', 'gk-gravityview' ) }
									value={ content }
									onChange={ ( val ) => setAttributes( { content: val } ) }
								/>

								<TextControl
									label={ __( 'Link Attributes', 'gk-gravityview' ) }
									value={ linkAtts }
									onChange={ ( linkAtts ) => setAttributes( { linkAtts } ) }
								/>
							</Disabled>
						</PanelBody>

						<PanelBody title={ __( 'Extra Settings', 'gk-gravityview' ) } initialOpen={ false }>
							<Disabled isDisabled={ !entryId }>
								<PostSelector
									postId={ postId }
									onChange={ ( postId ) => setAttributes( { postId } ) }
								/>

								<TextControl
									label={ __( 'Field Values', 'gk-gravityview' ) }
									value={ fieldValues }
									onChange={ ( fieldValues ) => setAttributes( { fieldValues } ) }
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
						onChange={ ( viewId ) => {
							const selectedView = gkGravityViewBlocks.views.find( option => option.value === viewId );
							setAttributes( {
								viewId,
								secret: selectedView?.secret,
								previewBlock: false,
								entryId: ''
							} );
						} }
					/>

					<EntrySelector
						disabled={ !viewId }
						noButtonGroup={ true }
						entryId={ entryId }
						onChange={ ( entryId ) => setAttributes( { entryId } ) }
					/>

					<PreviewControl
						disabled={ !viewId || !entryId }
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
							blockPreviewImage={ previewImage }
						/>
					</Disabled>
				</div>
			</> }
		</div>
	);
}
