/**
 * GravityView Divi Visual Builder Component
 *
 * This React component renders GravityView content in the Divi Visual Builder.
 * It receives the computed __view_content prop from the PHP module and renders it.
 *
 * @package GravityKit\GravityView\Extensions\Divi
 * @since TODO
 */

import React, { Component } from 'react';

// Track loaded styles globally to prevent duplicate loading.
const loadedStyles = new Set();

/**
 * Load a stylesheet by creating a link element.
 *
 * @param {string} url The stylesheet URL to load.
 */
const loadStylesheet = ( url ) => {
	if ( loadedStyles.has( url ) ) {
		return;
	}

	const link = document.createElement( 'link' );
	link.rel = 'stylesheet';
	link.type = 'text/css';
	link.href = url;
	document.head.appendChild( link );

	loadedStyles.add( url );
};

/**
 * GravityView module component for Divi Visual Builder.
 */
class GravityView extends Component {
	/**
	 * Module slug - must match the PHP module slug.
	 *
	 * @type {string}
	 */
	static slug = 'gk_gravityview';

	/**
	 * Load styles when component mounts or updates.
	 */
	componentDidMount() {
		this.loadStyles();
	}

	/**
	 * Load styles when props change.
	 */
	componentDidUpdate( prevProps ) {
		if ( prevProps.__view_content !== this.props.__view_content ) {
			this.loadStyles();
		}
	}

	/**
	 * Parse the view content and load any styles.
	 */
	loadStyles() {
		const viewContent = this.props.__view_content;

		if ( ! viewContent ) {
			return;
		}

		// Try to parse as JSON (contains content + styles).
		try {
			const data = JSON.parse( viewContent );

			if ( data.styles && Array.isArray( data.styles ) ) {
				data.styles.forEach( ( style ) => {
					// Handle both string URLs and objects with src property.
					const url = typeof style === 'string' ? style : style?.src;
					if ( url ) {
						loadStylesheet( url );
					}
				} );
			}
		} catch ( e ) {
			// Content is not JSON, styles may be inline in HTML.
		}
	}

	/**
	 * Get the HTML content to render.
	 *
	 * @return {string|null} The HTML content.
	 */
	getContent() {
		const viewContent = this.props.__view_content;

		if ( ! viewContent ) {
			return null;
		}

		// Try to parse as JSON.
		try {
			const data = JSON.parse( viewContent );
			return data.content || null;
		} catch ( e ) {
			// Not JSON, return as-is.
			return viewContent;
		}
	}

	/**
	 * Render the module content.
	 *
	 * @return {JSX.Element} The rendered component.
	 */
	render() {
		const content = this.getContent();

		// If no content available, show placeholder.
		if ( ! content ) {
			return (
				<div className="gk-gravityview-placeholder" style={{
					textAlign: 'center',
					padding: '40px 20px',
					border: '2px dashed #ccc',
					borderRadius: '4px',
					backgroundColor: '#f9f9f9',
					color: '#666',
				}}>
					<div style={{
						fontSize: '16px',
						fontWeight: '500',
						marginBottom: '8px',
					}}>
						GravityView
					</div>
					<div style={{
						fontSize: '14px',
					}}>
						Please select a View from the module settings.
					</div>
				</div>
			);
		}

		// Render the View HTML content.
		return (
			<div
				className="gk-gravityview-divi-module"
				dangerouslySetInnerHTML={{ __html: content }}
			/>
		);
	}
}

// Register module with Divi's Visual Builder API.
jQuery( window ).on( 'et_builder_api_ready', function( event, api ) {
	if ( api && api.registerModules ) {
		api.registerModules( [ GravityView ] );
	}
} );

export default GravityView;
