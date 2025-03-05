/**
 * GravityView Elementor Widget Handler
 * 
 * Handles the functionality of the GravityView Elementor widget in the editor.
 * 
 * @since TODO
 */
class GravityViewWidgetHandler extends elementorModules.editor.utils.Module {
	/**
	 * Get default settings for the widget handler.
	 * 
	 * @since TODO
	 * 
	 * @return {Object} Default settings object containing selectors and controls.
	 */
	getDefaultSettings() {
		return {
			selectors: {
				viewSelect: '.elementor-control-embedded_view select',
				layoutType: '.elementor-control-template_id input',
				viewsLayouts: '.elementor-control-views_layouts input',
				layoutSingle: '.elementor-control-layout_single input',
				layoutMultiple: '.elementor-control-layout_multiple input'
			},
			controls: {
				layoutSingle: 'layout_single',
				layoutMultiple: 'layout_multiple',
				viewId: 'embedded_view'
			}
		};
	}

	/**
	 * Initialize the widget handler.
	 * 
	 * @since TODO
	 */
	onInit() {
		super.onInit();
		console.log( 'GravityViewWidgetHandler initialized' );
		this.initEditorListeners();
	}

	/**
	 * Initialize editor event listeners.
	 * 
	 * @since TODO
	 */
	initEditorListeners() {
		console.log( 'Initializing editor listeners' );
		elementor.channels.editor.on( 'change', this.onControlChange.bind( this ) );
		console.log( 'Editor listeners initialized' );
	}

	/**
	 * Handle control change events in the Elementor editor.
	 * 
	 * @since TODO
	 * 
	 * @param {Object} view The Elementor view object that triggered the change.
	 */
	onControlChange( view ) {
		if ( !view || !view.model ) {
			return;
		}

		const controls = this.getDefaultSettings().controls;
		const controlName = view.model.get( 'name' );
		let controlValue = null;

		// Try to get control value from view.container if available
		if ( view.container && view.container.settings ) {
			controlValue = view.container.settings.get( controlName );
		} else {
			// Fallback: get current edited element from panel
			const currentElementView = elementor.getPanelView()?.getCurrentPageView()?.getOption( 'editedElementView' );
			if ( currentElementView ) {
				controlValue = currentElementView.getEditModel().get( 'settings' ).get( controlName );
			}
		}

		console.log( 'Control changed:', {
			controlName,
			controlValue
		} );

		// Compare against the control name from settings
		if ( controlName === controls.viewId ) {

			console.log( 'Updating layout type for View:', controlValue );

			this.updateLayoutType( controlValue );
			this.updateViewSettings( controlValue );

			// Get the current edited element so we can trigger a change event on its model.
			const elementView = elementor.getPanelView()?.getCurrentPageView()?.getOption( 'editedElementView' );
			if ( elementView && elementView.getEditModel ) {

				console.log( 'Triggering change:editSettings' );

				// Triggering 'change' on the model causes Elementor's condition engine to re-run.
				elementView.getEditModel().trigger( 'change:editSettings' );
			} else {
				console.warn( 'Edited element view not found in onControlChange.' );
			}
		} else {
			console.log( 'Control name does not match:', controlName );
		}
	}

	/**
	 * Get the layouts data from the hidden control.
	 * 
	 * @since TODO
	 * 
	 * @return {Object|null} The parsed layouts data or null if not found/invalid.
	 */
	getViewsLayoutsData() {
		const viewsLayouts = document.querySelector( this.getDefaultSettings().selectors.viewsLayouts );
		
		if ( !viewsLayouts?.value ) {
			return null;
		}

		try {
			return JSON.parse( viewsLayouts.value );
		} catch ( e ) {
			console.error( 'Error parsing layouts data:', e );
			return null;
		}
	}

	/**
	 * Update the layout type based on the selected View.
	 * 
	 * @since TODO
	 * 
	 * @param {string|number} viewId The ID of the selected view.
	 */
	updateLayoutType( viewId ) {
		if ( !viewId ) {
			console.warn( 'No view ID provided' );
			return;
		}

		console.log( 'Updating layout type for view:', viewId );

		const layouts = this.getViewsLayoutsData();
		if ( !layouts || !layouts[ viewId ] ) {
			return;
		}

		const layout = layouts[ viewId ];
		console.log( 'Fetched layouts for View:', layout );

		// Get the current edited element
		const elementView = elementor.getPanelView()?.getCurrentPageView()?.getOption( 'editedElementView' );
		if ( !elementView ) {
			console.warn( 'Edited element view not found' );
			return;
		}

		const controls = this.getDefaultSettings().controls;
		const model = elementView.getEditModel();
		const settings = model.get( 'settings' );

		// Update the model settings
		settings.set( controls.layoutSingle, layout.single );
		settings.set( controls.layoutMultiple, layout.multiple );

		// Update the control inputs and trigger change events
		const layoutSingle = document.querySelector( this.getDefaultSettings().selectors.layoutSingle );
		const layoutMultiple = document.querySelector( this.getDefaultSettings().selectors.layoutMultiple );

		// Update values
		layoutSingle.value = layout.single;
		layoutMultiple.value = layout.multiple;
	}

	/**
	 * Update view settings based on the selected view.
	 * 
	 * @since TODO
	 * 
	 * @param {string|number} viewId The ID of the selected view.
	 */
	updateViewSettings( viewId ) {
		if ( !viewId ) {
			console.warn( 'No view ID provided' );
			return;
		}

		console.log( 'Updating settings for View:', viewId );

		const layouts = this.getViewsLayoutsData();
		if ( !layouts || !layouts[ viewId ] || !layouts[ viewId ].settings ) {
			return;
		}

		const settings = layouts[ viewId ].settings;
		console.log( 'Fetched settings for View:', settings );

		// Get the current edited element
		const elementView = elementor.getPanelView()?.getCurrentPageView()?.getOption( 'editedElementView' );
		if ( !elementView ) {
			console.warn( 'Edited element view not found' );
			return;
		}

		const container = elementView.getContainer();
		const model = elementView.getEditModel();
		const modelSettings = model.get( 'settings' );

		// Batch update the settings
		Object.entries( settings ).forEach( ( [ key, value ] ) => {
			// Convert boolean values to 'yes'/'no' for Elementor compatibility
			if ( typeof value === 'boolean' ) {
				value = value ? 'yes' : 'no';
			}

			console.log( 'Preparing setting update:', key, value );

			// Update both the model and container settings
			modelSettings.set( key, value );
			container.settings.set( key, value );

			// Update the DOM input
			const input = document.querySelector( `.elementor-control-${key} input, .elementor-control-${key} select, .elementor-control-${key} textarea` );
			if ( input ) {
				if ( input.type === 'checkbox' ) {
					input.checked = value === 'yes';
				} else {
					input.value = value;
				}

				// Trigger change event to update Elementor's UI
				input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			}
		} );

		// Render just this widget
		elementView.renderOnChange();
	}
}

/**
 * Initialize the GravityView Widget Handler when Elementor is ready.
 * 
 * @since TODO
 */
window.addEventListener( 'elementor/init', () => new GravityViewWidgetHandler() );
