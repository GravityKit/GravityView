import { __ } from '@wordpress/i18n';
import { BaseControl, ButtonGroup, Button, TextControl } from '@wordpress/components';

export default function EntrySelector( { children, entryId, onChange, noButtonGroup } ) {
	const EntryInput = (
		<TextControl
			label={ __( 'Entry ID', 'gk-gravityview' ) }
			placeholder={ __( 'Entry ID', 'gk-gravityview' ) }
			value={ entryId }
			onChange={ ( entryId ) => onChange( entryId ) }
		/>
	);

	if ( noButtonGroup ) {
		return EntryInput;
	}

	return (
		<BaseControl label={ __( 'Entry Type', 'gk-gravityview' ) }>
			<ButtonGroup className="gk-gravityview-block btn-group-triple">
				<Button
					isPrimary={ entryId !== 'first' && entryId !== 'last' }
					onClick={ () => onChange( '' ) }
				>
					{ __( 'Entry ID', 'gk-gravityview' ) }
				</Button>

				<Button
					isPrimary={ entryId === 'first' }
					onClick={ () => onChange( 'first' ) }
				>
					{ __( 'First', 'gk-gravityview' ) }
				</Button>

				<Button
					isPrimary={ entryId === 'last' }
					onClick={ () => onChange( 'last' ) }
				>
					{ __( 'Last', 'gk-gravityview' ) }
				</Button>
			</ButtonGroup>

			{ entryId !== 'first' && entryId !== 'last' && <>
				{ EntryInput }
			</> }

			{ children }
		</BaseControl>
	);
}
