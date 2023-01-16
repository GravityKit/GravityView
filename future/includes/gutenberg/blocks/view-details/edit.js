import { __, _x } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Panel, PanelBody, SelectControl, Spinner } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import ViewSelector from 'shared/js/view-selector';
import './editor.scss';

export default function Edit( { attributes, setAttributes, name: blockName } ) {
	const { view_id: viewId, detail, blockPreview } = attributes;

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
							viewId={ viewId }
							onChange={ ( view_id ) => { setAttributes( { view_id, entry_id: '' } ); } }
						/>

						{ viewId && <>
							<SelectControl
								label={ __( 'Detail', 'gk-gravityview' ) }
								value={ detail }
								options={ [
									{ value: 'total_entries', label: __( 'Total Entries', 'gk-gravityview' ) },
									{ value: 'first_entry', label: __( 'First Entry', 'gk-gravityview' ) },
									{ value: 'last_entry', label: __( 'Last Entry', 'gk-gravityview' ) },
									{ value: 'page_size', label: __( 'Page Size', 'gk-gravityview' ) },
								] }
								onChange={ ( value ) => setAttributes( { detail: value } ) }
							/>
						</> }
					</PanelBody>
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
