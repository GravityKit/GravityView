import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { Spinner } from '@wordpress/components';

import InnerHTML from 'dangerously-set-html-content';
import { useAtom } from 'jotai';

import globalStore from './global-store';

const API_PATH = '/wp/v2/block-renderer';
const DEBOUNCE_FETCH = 500; // Used to debounce fetch request so that it only happens when the block's attributes haven't changed in 500ms.

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
		blockPreviewImage,
		dataType,
		attributes,
		loadScripts,
		loadStyles,
		onEmptyResponse,
		onError,
		onLoading,
		onResponse
	} = props;

	const [ response, setResponse ] = useState( null );
	const [ isFetching, setIsFetching ] = useState( true );
	const [ error, setError ] = useState( null );

	const [ loadedScripts, setLoadedScripts ] = useAtom( globalStore.loadedScripts );
	const [ loadedStyles, setLoadedStyles ] = useAtom( globalStore.loadedStyles );

	useEffect( () => {
		const handler = setTimeout( () => fetch(), DEBOUNCE_FETCH );

		return () => clearTimeout( handler );
	}, [ attributes ] );

	const fetch = () => {
		const path = addQueryArgs( `${ API_PATH }/${ block }`, {
			context: 'edit',
			attributes,
		} );

		setIsFetching( true );

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
							let assetToLoad = asset;

							if ( loadedScripts.has( asset ) || loadedScripts.has( asset?.src ) ) {
								return;
							}

							if ( asset?.src ) {
								assetToLoad = asset.src;
							}

							if ( asset?.data ) {
								eval( asset.data );
							}

							loadAsset( { assetToLoad, type: 'js' } );

							setLoadedScripts( loadedScripts.add( assetToLoad ) );
						} );
					}

					setTimeout( () => {
						setResponse( response.content );

						setIsFetching( false );
					}, 250 ); // Wait for scripts/styles to load.
				} else {
					setResponse( res.rendered );

					setIsFetching( false );
				}
			} )
			.catch( ( error ) => {
				setError( error );

				setIsFetching( false );
			} );
	};

	if ( error ) {
		return typeof onError === 'function'
			? onError( error )
			: (
				<div className="error-state">
					{
						_x( 'The block could not be rendered due to an error: [error]', '[error] placeholder will be replaced with an error message and is not to be translated.', 'gk-gravitykit' )
							.replace( '[error]', error.message )
					}
				</div>
			);
	}

	// If the block was previously rendered, do not clear existing response and just display the spinner; this prevents the unsightly content shift.
	if ( isFetching && response ) {
		return typeof onLoading === 'function'
			? onLoading( response )
			: (
				<div className="loading-state">
					<div className="loader">
						<Spinner />
					</div>
					<InnerHTML html={ response } />
				</div>
			);
	}

	if ( isFetching ) {
		return typeof onLoading === 'function'
			? onLoading()
			: (
				<div className="loading-state initial">
					<div className="loader">
						<Spinner />
					</div>
					{ blockPreviewImage }
				</div>
			);
	}

	if ( !response ) {
		return typeof onEmptyResponse === 'function'
			? onEmptyResponse()
			: (
				<div class="empty-response">
					<p>
						{ __( 'The block did not render any content.', 'gk-gravityview' ) }
					</p>
				</div>
			);
	}

	return typeof onResponse === 'function'
		? onResponse( response )
		: <InnerHTML html={ response } />;
};

export default ServerSideRender;
