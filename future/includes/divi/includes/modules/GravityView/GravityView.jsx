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
	 * Render the module content.
	 *
	 * @return {JSX.Element} The rendered component.
	 */
	render() {
		const viewContent = this.props.__view_content;

		// If no content available, show placeholder
		if ( ! viewContent ) {
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

		// Render the View HTML content
		return (
			<div
				className="gk-gravityview-divi-module"
				dangerouslySetInnerHTML={{ __html: viewContent }}
			/>
		);
	}
}

// Register module with Divi's Visual Builder API
jQuery( window ).on( 'et_builder_api_ready', function( event, api ) {
	if ( api && api.registerModules ) {
		api.registerModules( [ GravityView ] );
	}
} );

export default GravityView;
