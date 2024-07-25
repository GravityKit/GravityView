import { atom } from 'jotai';

const globalStore = {
	loadedStyles: atom( new Set() ),
	loadedScripts: atom( new Set() ),
};

export default globalStore;

/* 	@wordpress/data that borrows heavily from Redux is a total overkill for us and Jotai is a much better fit for our simple global state need.
 	I am leaving the @wordpress/data and useSelect/useDispatch code since I've already coded it before the change of heart.

// Store
import { registerStore } from '@wordpress/data';

const DEFAULT_STATE = {
	loadedStyles: new Set(),
	loadedScripts: new Set(),
};

const SHARED_STORE = 'gk-gravityview-blocks/store';

const actions = {
	setLoadedScripts( scripts ) {
		return {
			type: 'UPDATE_LOADED_SCRIPTS',
			scripts,
		};
	},
	updateLoadedStyles( styles ) {
		return {
			type: 'UPDATE_LOADED_STYLES',
			styles,
		};
	},
};

const selectors = {
	getLoadedScripts( state ) {
		return state.loadedScripts;
	},

	getLoadedStyles( state ) {
		return state.loadedStyles;
	},
};

const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case 'UPDATE_LOADED_SCRIPTS': {
			return {
				...state,
				loadedScripts: action.scripts,
			};
		}
		case 'UPDATE_LOADED_STYLES': {
			return {
				...state,
				loadedStyles: state.loadedStyles.add( action.styles ),
			};
		}
		default: {
			return state;
		}
	}
};

registerStore( SHARED_STORE, {
	actions,
	selectors,
	reducer,
} );

export { SHARED_STORE };

// Component:

import { withSelect, withDispatch } from '@wordpress/data';

// ...

const { loadedScripts, loadedStyles } = useSelect( ( select ) => ( {
    loadedScripts: select( SHARED_STORE ).getLoadedScripts(),
    loadedStyles: select( SHARED_STORE ).getLoadedStyles()
} ) );

const { updateLoadedStyles, updateLoadedScripts } = useDispatch( SHARED_STORE );

 */