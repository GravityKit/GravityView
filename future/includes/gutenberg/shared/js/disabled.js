import { Disabled } from '@wordpress/components';

export default function EntrySelector( { isDisabled = false, toggleOpacity = true, children } ) {
	if ( !isDisabled ) {
		return children;
	}

	return (
		<div className={ isDisabled && toggleOpacity ? 'disabled' : '' }>
			<Disabled isDisabled={ isDisabled }>
				{ children }
			</Disabled>
		</div>
	);
}
