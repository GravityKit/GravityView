import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { Placeholder, Spinner } from '@wordpress/components';

import InnerHTML from 'dangerously-set-html-content';
import { debounce } from 'lodash';
import { useAtom } from 'jotai';

import globalStore from './global-store';

const API_PATH = '/wp/v2/block-renderer';
const DEBOUNCE_FETCH = 500; // Debounce the fetch so that it only happens when the block's attributes haven't changed in 500ms.

export const loadAsset = ( { asset, type, onLoad } ) => {
	const el = type === 'js'
		? document.createElement( 'script' )
		: document.createElement( 'link' );

	if ( type === 'js' ) {
		el.setAttribute( 'type', 'text/javascript' );
		el.setAttribute( 'src', asset );
		el.onload = onLoad;
	} else {
		el.setAttribute( 'rel', 'stylesheet' );
		el.setAttribute( 'type', 'text/css' );
		el.setAttribute( 'href', asset );
	}

	document.body.appendChild( el );
};

const ServerSideRender = ( props ) => {
	const {
		block,
		dataType,
		attributes,
		loadScripts,
		loadStyles
	} = props;

	const [ response, setResponse ] = useState( null );
	const [ isFetching, setIsFetching ] = useState( true );
	const [ error, setError ] = useState( null );

	const [ loadedScripts, setLoadedScripts ] = useAtom( globalStore.loadedScripts );
	const [ loadedStyles, setLoadedStyles ] = useAtom( globalStore.loadedStyles );

	useEffect( () => {
		fetch();
	}, [ attributes ] );

	const fetch = useCallback(
		debounce( () => {
			setIsFetching( true );

			const path = addQueryArgs( `${ API_PATH }/${ block }`, {
				context: 'edit',
				attributes,
			} );

			apiFetch( { path } )
				.then( ( res ) => {
					if ( dataType === 'json' ) {
						const response = JSON.parse( res.rendered );

						if ( loadStyles ) {
							Object.values( response.styles ).forEach( ( asset ) => {
								if ( loadedStyles.has( asset ) ) {
									return;
								}

								loadAsset( { asset, type: 'css' } );

								setLoadedStyles( loadedStyles.add( asset ) );
							} );
						}

						if ( loadScripts ) {
							Object.values( response.scripts ).forEach( ( asset ) => {
								if ( loadedScripts.has( asset ) ) {
									return;
								}

								loadAsset( { asset, type: 'js' } );

								setLoadedScripts( loadedScripts.add( asset ) );
							} );
						}

						setResponse( response.content );
					} else {
						setResponse( res.rendered );
					}
					setIsFetching( false );
				} )
				.catch( ( error ) => setError( error ) );
		}, DEBOUNCE_FETCH ),
		[]
	);

	if ( error ) {
		return <div>{ error.message }</div>;
	}

	// Do not clear existing response and just display the spinner; this prevents the unsightly content shift.
	if ( response && isFetching ) {
		return (
			<div className="loading-state">
				<div className="loader">
					<Spinner />
				</div>
				<InnerHTML html={ response } />
			</div>
		);
	}

	if ( isFetching ) {
		return (
			<Placeholder>
				<Spinner />
			</Placeholder>
		);
	}

	return <InnerHTML html={ response } />;
};

export default ServerSideRender;