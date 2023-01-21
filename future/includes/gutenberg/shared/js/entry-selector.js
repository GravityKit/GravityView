import { __ } from '@wordpress/i18n';
import { BaseControl, ButtonGroup, Button, TextControl, Disabled } from '@wordpress/components';

export default function EntrySelector( { children, entryId, onChange, noButtonGroup, disabled, showInSidebar } ) {
	const EntryInput = (
		<TextControl
			label={ __( 'Entry ID', 'gk-gravityview' ) }
			placeholder={ __( 'Entry ID', 'gk-gravityview' ) }
			value={ entryId }
			onChange={ ( entryId ) => onChange( entryId ) }
		/>
	);

	const noEntryInput = ( entryId === 'first' || entryId === 'last' );

	return (
		<Disabled isDisabled={ disabled }>
			<div className={ `entry-selector ${ noEntryInput ? 'no-entry-input' : '' }` }>
				{ noButtonGroup && EntryInput }

				{ !noButtonGroup && <>
					<BaseControl label={ showInSidebar ? __( 'Entry Type', 'gk-gravityview' ) : '' }>
						<ButtonGroup className="btn-group-triple">
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

						{ !noEntryInput && EntryInput }

						{ children }
					</BaseControl>
				</> }
			</div>
		</Disabled>
	);
}
