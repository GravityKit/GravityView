import { __ } from '@wordpress/i18n';
import Select from 'react-select';
import { BaseControl } from '@wordpress/components';
import { useEffect, useState } from 'react';

export default function SortFieldSelector( {
	viewId,
	onChange,
	sortField,
} ) {
	const labels = {
		selectSortField: __( 'Select a Sort Field', 'gk-gravityview' ),
		sort: __( 'Sort Field', 'gk-gravityview' ),
	};

	const defaultOption = { value: '', label: labels.selectSortField };

	const [ options, setOptions ] = useState( [ defaultOption ] );
	const [ selectedSortField, setSelectedSortField ] = useState( defaultOption );

	const fetchData = async ( viewId ) => {
		try {
			const response = await fetch( gkGravityViewBlocks.ajax_url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: 'gv_sortable_fields',
					nonce: gkGravityViewBlocks.nonce,
					viewid: viewId,
				} ),
			} );

			if ( response.status === 200 ) {
				const text = await response.text();
				const parser = new DOMParser();
				const doc = parser.parseFromString( text, 'text/html' );
				const optionElements = doc.querySelectorAll( 'option' );
				const newOptions = Array.from( optionElements ).map( ( option ) => ( {
					value: option.value,
					label: option.textContent,
				} ) );

				setOptions( [ defaultOption, ...newOptions ] );
				const selectedOption =
					newOptions.find( ( option ) => option.value === sortField ) ||
					defaultOption;
				setSelectedSortField( selectedOption );
			} else {
				console.error( 'Error:', response );
			}
		} catch ( error ) {
			console.error( 'Fetch error:', error );
		}
	};

	useEffect( () => {
		fetchData( viewId );
	}, [ viewId ] );

	return (
		<BaseControl className="sort-field-selector" label={ labels.sort } __nextHasNoMarginBottom>
			<Select
				aria-label={ labels.sort }
				placeholder={ labels.selectSortField }
				menuPortalTarget={ document.body }
				styles={ { menuPortal: ( base ) => ( { ...base, zIndex: 10 } ) } }
				options={ options }
				value={ selectedSortField }
				onChange={ ( e ) => {
					onChange( e.value );
					setSelectedSortField( e );
				} }
				noOptionsMessage={ () => __( 'No Sorting Fields found', 'gk-gravityview' ) }
			/>
		</BaseControl>
	);
}
