import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Panel, PanelBody, Disabled } from '@wordpress/components';

import ViewSelector from 'shared/js/view-selector';
import EntrySelector from 'shared/js/entry-selector';
import PreviewControl from 'shared/js/preview-control';
import PreviewAsShortcodeControl from 'shared/js/preview-as-shortcode-control';
import ServerSideRender from 'shared/js/server-side-render';
import NoViewsNotice from 'shared/js/no-views-notice';

import './editor.scss';

export default function Edit( { attributes, setAttributes, name: blockName } ) {
	const {
		viewId,
		entryId,
		previewBlock,
		previewAsShortcode,
		showPreviewImage
	} = attributes;

	const shouldPreview = ( previewBlock && viewId && entryId );

	const previewImage = gkGravityViewBlocks[ blockName ]?.previewImage && <img className="preview-image" src={ gkGravityViewBlocks[ blockName ]?.previewImage } alt={ __( 'Block preview image.', 'gk-gravityview' ) } />;

	if ( previewImage && showPreviewImage ) {
		return previewImage;
	}

	if ( !gkGravityViewBlocks?.views?.length ) {
		return <NoViewsNotice blockPreviewImage={ previewImage } newViewUrl={ gkGravityViewBlocks?.create_new_view_url } />;
	}

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<div className="gk-gravityview-blocks">
					<Panel>
						<PanelBody title={ __( 'Main Settings', 'gk-gravityview' ) } initialOpen={ true }>
							<ViewSelector
								viewId={ viewId }
								onChange={ ( viewId ) => { setAttributes( { viewId, entryId: '' } ); } }
							/>

							<EntrySelector
								disabled={ !viewId }
								entryId={ entryId }
								onChange={ ( entryId ) => { setAttributes( { entryId } ); } }
							/>

							<PreviewControl
								disabled={ !viewId || !entryId }
								preview={ previewBlock }
								onChange={ ( previewBlock ) => { setAttributes( { previewBlock } ); } }
							/>
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

					<div>
						<ViewSelector
							viewId={ viewId }
							onChange={ ( viewId ) => { setAttributes( { viewId, entryId: '' } ); } }
						/>

						<div>
							<EntrySelector
								disabled={ !viewId }
								noButtonGroup={ true }
								entryId={ entryId }
								onChange={ ( entryId ) => { setAttributes( { entryId } ); } }
							/>
						</div>

						<PreviewControl
							disabled={ !viewId || !entryId }
							preview={ previewBlock }
							onChange={ ( previewBlock ) => { setAttributes( { previewBlock } ); } }
						/>
					</div>
				</div>
			</> }

			{ shouldPreview && <>
				<div className="block-preview">
					<Disabled>
						<ServerSideRender
							block={ blockName }
							attributes={ attributes }
						/>
					</Disabled>
				</div>
			</> }
		</div>
	);
}