import { __ } from '@wordpress/i18n';
import Select from 'react-select';
import { BaseControl } from '@wordpress/components';

export default function ViewSelector( { viewId, isSidebar, onChange } ) {
	const labels = {
		selectView: __( 'Select a View', 'gk-gravityview' ),
		view: __( 'View', 'gk-gravityview' )
	};

	const editViewNotice = __( 'Edit View', 'gk-gravityview' );

	const views = [
		{
			value: '',
			label: labels.selectView
		},
		...gkGravityViewBlocks?.views,
	];

	const selectedView = views.filter( option => option.value === viewId ) || views[ 0 ];

	return (
		<BaseControl className={`view-selector ${viewId && isSidebar ? 'edit-view' :''}`} label={ labels.view } __nextHasNoMarginBottom>
			<Select
				aria-label={ labels.view }
				placeholder={ labels.selectView }
				menuPortalTarget={ document.body }
				styles={ { menuPortal: base => ( { ...base, zIndex: 10 } ) } } // A higher z-index is needed to ensure other editor elements don't overlap the dropdown.
				value={ selectedView }
				options={ views }
				onChange={ ( e ) => onChange( e.value ) }
				noOptionsMessage={ () => __( 'No Views found', 'gk-gravityview' ) }
			/>

			{ viewId && isSidebar && <>
				<p dangerouslySetInnerHTML={ { __html: `<a href="${ gkGravityViewBlocks?.edit_view_url.replace( '%s', viewId ) }">${ editViewNotice }</a>` } } />
			</> }
		</BaseControl>
	);
}
