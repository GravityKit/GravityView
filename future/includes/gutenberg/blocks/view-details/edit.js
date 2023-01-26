import { __, _x } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Panel, PanelBody, SelectControl, Spinner, Disabled } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import ViewSelector from 'shared/js/view-selector';
import PreviewControl from 'shared/js/preview-control';

import './editor.scss';

export default function Edit( { attributes, setAttributes, name: blockName } ) {
	const {
		viewId,
		detail,
		previewBlock,
		showPreviewImage
	} = attributes;

	const previewImage = gkGravityViewBlocks[ blockName ]?.previewImage && <img className="preview-image" src={ gkGravityViewBlocks[ blockName ]?.previewImage } alt={ __( 'Block preview image.', 'gk-gravityview' ) } />;

	if ( previewImage && showPreviewImage ) {
		return previewImage;
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
					block={ blockName }
					attributes={ attributes }
				/>
			</> }
		</div>
	);
}
