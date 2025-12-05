import { __, _x } from '@wordpress/i18n';
import { BaseControl, ButtonGroup, Button, TextControl } from '@wordpress/components';

import Disabled from './disabled';

export default function EntrySelector( { children, entryId, onChange, minimalBottomMargin, noButtonGroup, disabled = false, showInSidebar } ) {
	const EntryInput = (
		<TextControl
			label={ __( 'Entry ID', 'gk-gravityview' ) }
			placeholder={ __( 'Entry ID', 'gk-gravityview' ) }
			value={ entryId }
			onChange={ ( entryId ) => onChange( entryId ) }
		/>
	);

	const noEntryInput = ( entryId === 'first' || entryId === 'last' );

	const entryDisplayNotice = _x( 'Field data will be shown for the [position] entry in the View.', '[position] will be replaced with "first" or "last" and not to be translated.', 'gk-gravityview' )
		.replace( '[position]', entryId === 'first'
			? _x( 'first', 'Used to indicate "first entry"', 'gk_gravityview' )
			: _x( 'last', 'Used to indicate "last entry"', 'gk_gravityview' )
		);

	return (
		<Disabled isDisabled={ disabled }>
			<div className={ `entry-selector ${ minimalBottomMargin || noEntryInput ? 'minimal-bottom-margin' : '' }` }>
				{ noButtonGroup && EntryInput }

				{ !noButtonGroup && <>
					<BaseControl label={ showInSidebar ? __( 'Entry Type', 'gk-gravityview' ) : '' } __nextHasNoMarginBottom>
						<ButtonGroup className="btn-group-triple">
							<Button
								isPrimary={ ![ 'first', 'last' ].includes( entryId ) }
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

						{ [ 'first', 'last' ].includes( entryId ) && <p className='first-last-entry-id-notice'>{ entryDisplayNotice }</p> }

						{ !noEntryInput && EntryInput }

						{ children }
					</BaseControl>
				</> }
			</div>
		</Disabled>
	);
}
