import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Panel, PanelBody, SelectControl, Disabled } from '@wordpress/components';

import ViewSelector from 'shared/js/view-selector';
import PreviewControl from 'shared/js/preview-control';
import PreviewAsShortcodeControl from 'shared/js/preview-as-shortcode-control';
import ServerSideRender from 'shared/js/server-side-render';
import NoViewsNotice from 'shared/js/no-views-notice';

import './editor.scss';

export default function Edit( { attributes, setAttributes, name: blockName } ) {
	const {
		viewId,
		detail,
		previewBlock,
		previewAsShortcode,
		showPreviewImage
	} = attributes;

	const previewImage = gkGravityViewBlocks[ blockName ]?.previewImage && <img className="preview-image" src={ gkGravityViewBlocks[ blockName ]?.previewImage } alt={ __( 'Block preview image.', 'gk-gravityview' ) } />;

	if ( previewImage && showPreviewImage ) {
		return previewImage;
	}

	if ( !gkGravityViewBlocks?.views?.length ) {
		return <NoViewsNotice blockPreviewImage={ previewImage } newViewUrl={ gkGravityViewBlocks?.create_new_view_url } />;
	}

	const shouldPreview = ( previewBlock && viewId );

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<div className="gk-gravityview-blocks">
					<Panel>
						<PanelBody title={ __( 'Main Settings', 'gk-gravityview' ) } initialOpen={ true }>
							<ViewSelector
								viewId={ viewId }
								isSidebar={ true }
								onChange={ ( viewId ) => { setAttributes( { viewId } ); } }
							/>

							<Disabled isDisabled={ !viewId }>
								<SelectControl
									label={ __( 'Detail', 'gk-gravityview' ) }
									value={ detail }
									options={ [
										{ value: 'total_entries', label: __( 'Total Entries', 'gk-gravityview' ) },
										{ value: 'first_entry', label: __( 'First Entry', 'gk-gravityview' ) },
										{ value: 'last_entry', label: __( 'Last Entry', 'gk-gravityview' ) },
										{ value: 'pageSize', label: __( 'Page Size', 'gk-gravityview' ) },
									] }
									onChange={ ( value ) => setAttributes( { detail: value } ) }
								/>

								<PreviewControl
									disabled={ !viewId }
									preview={ previewBlock }
									onChange={ ( previewBlock ) => { setAttributes( { previewBlock } ); } }
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
						onChange={ ( viewId ) => { setAttributes( { viewId } ); } }
					/>

					<PreviewControl
						disabled={ !viewId }
						preview={ previewBlock }
						onChange={ ( previewBlock ) => { setAttributes( { previewBlock } ); } }
					/>
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
