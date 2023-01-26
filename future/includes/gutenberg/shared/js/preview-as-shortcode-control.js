import { __ } from '@wordpress/i18n';
import { InspectorAdvancedControls } from '@wordpress/block-editor';
import { ToggleControl, Disabled } from '@wordpress/components';

export default function PreviewControl( { previewAsShortcode, disabled, onChange } ) {
	return (
		<InspectorAdvancedControls>
			<Disabled isDisabled={ disabled }>
				<div className="gravityview-blocks-preview-as-shortcode">
					<ToggleControl
						label={ __( 'Preview As Shortcode', 'gk-gravityview' ) }
						checked={ previewAsShortcode }
						onChange={ ( previewAsShortcode ) => onChange( previewAsShortcode ) }
					/>
				</div>
			</Disabled>
		</InspectorAdvancedControls>
	);
}
