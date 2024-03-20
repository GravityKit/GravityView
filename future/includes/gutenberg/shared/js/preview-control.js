import { __ } from '@wordpress/i18n';
import { BaseControl, ToggleControl } from '@wordpress/components';

import Disabled from './disabled';

export default function PreviewControl( { preview, disabled = false, onChange } ) {
	return (
		<Disabled isDisabled={ disabled }>
			<BaseControl className="preview-control">
				<ToggleControl
					label={ __( 'Preview', 'gk-gravityview' ) }
					checked={ preview }
					onChange={ ( preview ) => onChange( preview ) }
				/>
			</BaseControl>
		</Disabled>
	);
}
