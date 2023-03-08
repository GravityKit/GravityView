import { __, _x } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Panel, PanelBody } from '@wordpress/components';

export default function NoViewsNotice( { blockPreviewImage, newViewUrl } ) {
	const notice = _x( 'You must [url]create a View[/url] before using this block.', 'Placeholders inside [] are not to be translated.', 'gk-gravityview' )
		.replace( '[url]', `<a href="${ newViewUrl }" target="_blank">` )
		.replace( '[/url]', '</a>' );

	const noticeEl = <p className="no-views-notice" dangerouslySetInnerHTML={ { __html: notice } } />;

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<div className="gk-gravityview-blocks">
					<Panel>
						<PanelBody title={ __( 'Main Settings', 'gk-gravityview' ) } initialOpen={ true }>
							{ noticeEl }
						</PanelBody>
					</Panel>
				</div>
			</InspectorControls>

			<div className="block-editor">
				{ blockPreviewImage }

				{ noticeEl }
			</div>
		</div>
	);
}
