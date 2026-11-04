/**
 * WordPress dependencies
 */
import { createElement, createRoot } from '@wordpress/element';

/*
 * `@wordpress/element` renders with the React copy it bundles, which is a
 * different instance than the one at the repo root (kept for forward-compat
 * work). Pull `act` from that same copy so the render and its hooks share a
 * single React dispatcher, otherwise React throws an invalid-hook-call error.
 */
const { act } = jest.requireActual(
	require.resolve( 'react', { paths: [ require.resolve( '@wordpress/element' ) ] } )
);

const mockGetEntityRecord = jest.fn();
// A stable reference: `useSelect` warns in dev if the mapping returns a new object for the same state.
const mockSettings = {};

/*
 * Replace the stores with minimal stand-ins so `useSelect` (the unit under
 * test) stays real while `@wordpress/core-data` and friends are not pulled in.
 * By default `getSettings` returns no `siteUrl`, so the editor short-circuits
 * to `null` before mounting its render tree — but the `useSelect` mapping
 * still runs, which is what reads `postId`.
 */
const mockMakeStore = ( name, selectors, actions = {} ) => {
	const { createReduxStore, register } = jest.requireActual( '@wordpress/data' );
	const store = createReduxStore( name, { reducer: ( state = {} ) => state, selectors, actions } );
	register( store );
	return store;
};

jest.mock( '../../../store', () => ( {
	POST_TYPE: 'wporg-pattern',
	store: mockMakeStore( 'test/editor-pattern', { getSettings: () => mockSettings } ),
} ) );
jest.mock( '@wordpress/core-data', () => ( {
	store: mockMakeStore( 'test/editor-core', {
		getEntityRecord: ( state, kind, postType, id ) => mockGetEntityRecord( kind, postType, id ),
	} ),
} ) );
jest.mock( '@wordpress/notices', () => ( {
	store: mockMakeStore( 'test/editor-notices', {}, { createInfoNotice: () => ( { type: 'NOOP' } ) } ),
} ) );
jest.mock( '@wordpress/interface', () => {
	const { createElement: el } = jest.requireActual( '@wordpress/element' );
	const Noop = () => null;
	return {
		store: mockMakeStore( 'test/editor-interface', { getActiveComplementaryArea: () => undefined } ),
		ComplementaryArea: Object.assign( Noop, { Slot: Noop } ),
		FullscreenMode: Noop,
		InterfaceSkeleton: ( { header, secondarySidebar, content } ) =>
			el( 'div', null, header, secondarySidebar, content ),
	};
} );

/* Stub the heavy components so importing the editor module stays cheap. */
const Noop = () => null;

/**
 * Render children unchanged, for providers and boundaries the tests don't exercise.
 *
 * @param {Object}  props
 * @param {Element} props.children Wrapped elements.
 * @return {Element} The children.
 */
const Passthrough = ( { children } ) => children;

/**
 * Register a real store for the inserter and List View state, standing in for `core/editor`.
 *
 * @return {Object} The store descriptor.
 */
const mockMakeEditorStore = () => {
	const { createReduxStore, register } = jest.requireActual( '@wordpress/data' );
	const store = createReduxStore( 'test/editor-editor', {
		reducer: ( state = { inserter: false, listView: false }, action ) => {
			switch ( action.type ) {
				case 'SET_INSERTER':
					return { inserter: action.value, listView: false };
				case 'SET_LIST_VIEW':
					return { inserter: false, listView: action.value };
			}
			return state;
		},
		selectors: {
			isInserterOpened: ( state ) => state.inserter,
			isListViewOpened: ( state ) => state.listView,
		},
		actions: {
			setIsInserterOpened: ( value ) => ( { type: 'SET_INSERTER', value } ),
			setIsListViewOpened: ( value ) => ( { type: 'SET_LIST_VIEW', value } ),
		},
	} );
	register( store );
	return store;
};
/**
 * Like the real `EditorProvider`, give children their own `core/editor` unless `useSubRegistry` is false.
 *
 * @param {Object}  props
 * @param {Element} props.children       Wrapped elements.
 * @param {boolean} props.useSubRegistry Whether to isolate the children's stores.
 * @return {Element} The children, in a sub-registry when requested.
 */
function MockEditorProvider( { children, useSubRegistry = true } ) {
	const { createRegistry, RegistryProvider, useRegistry } = jest.requireActual( '@wordpress/data' );
	const { createElement: el, useState } = jest.requireActual( '@wordpress/element' );
	const parentRegistry = useRegistry();
	const [ registry ] = useState( () => {
		if ( ! useSubRegistry ) {
			return parentRegistry;
		}
		const subRegistry = createRegistry( {}, parentRegistry );
		subRegistry.register( require( '@wordpress/editor' ).store );
		return subRegistry;
	} );
	return el( RegistryProvider, { value: registry }, children );
}
jest.mock( '@wordpress/editor', () => ( {
	store: mockMakeEditorStore(),
	EditorNotices: Noop,
	EditorProvider: MockEditorProvider,
	EditorSnackbars: Noop,
	ErrorBoundary: Passthrough,
	UnsavedChangesWarning: Noop,
} ) );
jest.mock( '@wordpress/components', () => {
	const { createElement: el } = jest.requireActual( '@wordpress/element' );
	const N = () => null;
	return {
		Notice: N,
		Popover: Object.assign( N, { Slot: N } ),
		SlotFillProvider: ( { children } ) => el( 'div', null, children ),
	};
} );
jest.mock( '@wordpress/block-editor', () => ( { BlockBreadcrumb: Noop } ) );
jest.mock( '@wordpress/keyboard-shortcuts', () => ( { ShortcutProvider: Passthrough } ) );
// "Browse all" in the quick inserter opens the panel through the setter `BlockEditor` receives.
jest.mock( '../../block-editor', () => {
	const { createElement: el } = jest.requireActual( '@wordpress/element' );
	return {
		__esModule: true,
		default: ( { setIsInserterOpen } ) =>
			el( 'button', { 'data-toggle': 'browse-all', onClick: () => setIsInserterOpen( true ) } ),
	};
} );
jest.mock( '../../header', () => {
	const { createElement: el } = jest.requireActual( '@wordpress/element' );
	const { useDispatch } = jest.requireActual( '@wordpress/data' );
	return {
		__esModule: true,
		/**
		 * Toggle the panels from inside `EditorProvider`, as the real header does.
		 *
		 * @return {Element} The toggle buttons.
		 */
		default: function MockHeader() {
			const { setIsInserterOpened, setIsListViewOpened } = useDispatch(
				require( '@wordpress/editor' ).store
			);
			return el(
				'div',
				null,
				el( 'button', { 'data-toggle': 'inserter', onClick: () => setIsInserterOpened( true ) } ),
				el( 'button', { 'data-toggle': 'list-view', onClick: () => setIsListViewOpened( true ) } )
			);
		},
	};
} );
jest.mock( '../../secondary-sidebar/inserter-sidebar', () => {
	const { createElement: el } = jest.requireActual( '@wordpress/element' );
	return { __esModule: true, default: () => el( 'div', { 'data-panel': 'inserter' } ) };
} );
jest.mock( '../../secondary-sidebar/list-view-sidebar', () => {
	const { createElement: el } = jest.requireActual( '@wordpress/element' );
	return { __esModule: true, default: () => el( 'div', { 'data-panel': 'list-view' } ) };
} );
jest.mock( '../../keyboard-shortcuts', () => ( {
	__esModule: true,
	default: Object.assign( () => null, { Register: () => null } ),
} ) );
jest.mock( '../../sidebar', () => ( { SidebarComplementaryAreaFills: Noop } ) );
jest.mock( '../../url-controller', () => ( { __esModule: true, default: Noop } ) );
jest.mock( '../../welcome-guide', () => ( { __esModule: true, default: Noop } ) );
jest.mock( '../save-sidebar', () => ( { __esModule: true, default: Noop } ) );

describe( 'Editor', () => {
	let Editor;
	let container;
	let root;

	beforeAll( () => {
		global.IS_REACT_ACT_ENVIRONMENT = true;
		global.wporgLocale = { id: 'en_US' };
		Editor = require( '../index' ).default;
	} );

	beforeEach( () => {
		container = document.createElement( 'div' );
		document.body.appendChild( container );
		root = createRoot( container );
		mockGetEntityRecord.mockReset();
	} );

	afterEach( () => {
		act( () => root.unmount() );
		container.remove();
	} );

	const render = ( postId ) => {
		act( () => {
			root.render( createElement( Editor, { postId, onError: () => {} } ) );
		} );
	};

	it( 'looks up the post for the given postId', () => {
		render( 1 );

		expect( mockGetEntityRecord ).toHaveBeenCalledWith( 'postType', 'wporg-pattern', 1 );
	} );

	it( 'looks up the new post when postId changes', () => {
		render( 1 );
		mockGetEntityRecord.mockClear();

		// Re-render with a different postId. Without `postId` in the `useSelect`
		// dependency array, the memoized selector keeps reading the first id.
		render( 2 );

		expect( mockGetEntityRecord ).toHaveBeenCalledWith( 'postType', 'wporg-pattern', 2 );
	} );

	describe( 'with the pattern loaded', () => {
		const toggle = ( name ) =>
			act( () => {
				container.querySelector( `[data-toggle="${ name }"]` ).click();
			} );
		const hasPanel = ( name ) => !! container.querySelector( `[data-panel="${ name }"]` );

		beforeEach( () => {
			mockSettings.siteUrl = 'https://example.test';
			mockGetEntityRecord.mockReturnValue( { id: 1 } );

			// The panel store lives on the shared default registry, so start every test with both closed.
			const { dispatch } = jest.requireActual( '@wordpress/data' );
			const { store: editorStore } = require( '@wordpress/editor' );
			dispatch( editorStore ).setIsInserterOpened( false );
			dispatch( editorStore ).setIsListViewOpened( false );
		} );

		afterEach( () => {
			delete mockSettings.siteUrl;
		} );

		// Regression: in a sub-registry, the header changed a `core/editor` the panels never read.
		it.each( [ 'inserter', 'list-view' ] )( 'opens the %s panel the header toggles', ( panel ) => {
			render( 1 );
			expect( hasPanel( panel ) ).toBe( false );

			toggle( panel );

			expect( hasPanel( panel ) ).toBe( true );
		} );

		it( 'opens the inserter when the block editor asks for it, as Browse all does', () => {
			render( 1 );
			expect( hasPanel( 'inserter' ) ).toBe( false );

			toggle( 'browse-all' );

			expect( hasPanel( 'inserter' ) ).toBe( true );
		} );
	} );
} );
