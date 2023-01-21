import { __ } from '@wordpress/i18n';
import { BaseControl, ToggleControl } from '@wordpress/components';

export default function PreviewControl( { preview, disabled, onChange } ) {
	return (
		<BaseControl className="preview-control">
			<ToggleControl
				disabled={ disabled }
				label={ __( 'Preview', 'gk-gravityview' ) }
				checked={ preview }
				onChange={ ( preview ) => onChange( preview ) }
			/>
		</BaseControl>
	);
}
