import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { TextControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

export default function PostSelector( { postId, onChange } ) {
	const [ showPostSuggestions, setShowPostSuggestions ] = useState( false );

	const [ posts, setPosts ] = useState( [] );

	useEffect( () => {
		apiFetch( { path: `/wp/v2/posts/?per_page=-1` } ).then( ( response ) => setPosts( response ) );
	}, [] );

	const suggestPosts = () => {
		if ( !posts.length || !postId || !showPostSuggestions ) {
			return null;
		}

		const suggestedPosts = posts.filter( item => ( item.id ).toString().indexOf( postId ) >= 0 ).map( item => {
			const { id, title: { rendered: title } } = item;

			return (
				<li
					key={ id }
					onClick={ () => {
						setShowPostSuggestions( false );
						onChange(  id  );
					} }
					dangerouslySetInnerHTML={ { __html: `ID : ${ id } => ${ title }` } }
				>
				</li>
			);
		} );

		if ( suggestedPosts.length === 0 ) {
			return null;
		}

		return (
			<ul>
				{ suggestedPosts }
			</ul>
		);
	};

	return (
		<div className="post-selector">
			<TextControl
				label={ __( 'Post ID', 'gk-gravityview' ) }
				value={ postId }
				type="number"
				min="1"
				onChange={ ( post_id ) => {
					onChange( post_id );

					setShowPostSuggestions( true );
				} }
			/>

			<div className="gk-gravityview-block suggestion-list">
				{ suggestPosts() }
			</div>
		</div>
	);
}
