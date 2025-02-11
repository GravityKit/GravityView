console.log( 'elementor-widget.js loaded' );

class GravityViewWidgetHandler extends elementorModules.editor.utils.Module {
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

	onInit() {
		super.onInit();
		console.log( 'GravityViewWidgetHandler initialized' );
		this.initEditorListeners();
	}

	initEditorListeners() {
		console.log( 'Initializing editor listeners' );
		elementor.channels.editor.on( 'change', this.onControlChange.bind( this ) );
		console.log( 'Editor listeners initialized' );
	}

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

			console.log( 'Updating layout type for view:', controlValue );

			this.updateLayoutType( controlValue );

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

	updateLayoutType( viewId ) {
		if ( !viewId ) {
			console.warn( 'No view ID provided' );
			return;
		}

		console.log( 'Updating layout type for view:', viewId );

		// Get layouts data from hidden control
		const viewsLayouts = document.querySelector( this.getDefaultSettings().selectors.viewsLayouts );
		console.log( viewsLayouts );
		if ( !viewsLayouts?.value ) {
			return;
		}


		try {
			const layouts = JSON.parse( viewsLayouts.value );
			if ( !layouts[ viewId ] ) {
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

		} catch ( e ) {
			console.error( 'Error updating layout:', e );
		}
	}
}

// Use vanilla JS for initialization
window.addEventListener( 'elementor/init', () => new GravityViewWidgetHandler() );
