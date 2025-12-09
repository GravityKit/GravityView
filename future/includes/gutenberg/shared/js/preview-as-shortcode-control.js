import { __ } from '@wordpress/i18n';
import { InspectorAdvancedControls } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';

import Disabled from './disabled';

export default function PreviewControl( { previewAsShortcode, disabled = false, onChange } ) {
	return (
		<InspectorAdvancedControls>
			<div className="gravityview-blocks-preview-as-shortcode">
				<Disabled isDisabled={ disabled }>
					<ToggleControl
						label={ __( 'Preview As Shortcode', 'gk-gravityview' ) }
						checked={ previewAsShortcode }
						onChange={ ( previewAsShortcode ) => onChange( previewAsShortcode ) }
						__nextHasNoMarginBottom
					/>
				</Disabled>
			</div>
		</InspectorAdvancedControls>
	);
}
