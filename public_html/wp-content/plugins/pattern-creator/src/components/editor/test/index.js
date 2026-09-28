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
// A stable reference: `useSelect` warns in dev if the mapping returns a new
// object for the same state, and the editor short-circuits when `siteUrl` is
// absent regardless.
const mockSettings = {};

/*
 * Replace the stores with minimal stand-ins so `useSelect` (the unit under
 * test) stays real while `@wordpress/core-data` and friends are not pulled in.
 * `getSettings` returns no `siteUrl`, so the editor short-circuits to `null`
 * before mounting its heavy render tree — but the `useSelect` mapping still
 * runs, which is what reads `postId`.
 */
const mockMakeStore = ( name, selectors, actions = {} ) => {
	const { createReduxStore, register } = jest.requireActual( '@wordpress/data' );
	const store = createReduxStore( name, { reducer: ( state = {} ) => state, selectors, actions } );
	register( store );
	return store;
};

jest.mock( '../../../store', () => ( {
	POST_TYPE: 'wporg-pattern',
	store: mockMakeStore(
		'test/editor-pattern',
		{
			isInserterOpened: () => false,
			isListViewOpened: () => false,
			getSettings: () => mockSettings,
		},
		{ setIsInserterOpened: () => ( { type: 'NOOP' } ) }
	),
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
		InterfaceSkeleton: () => el( 'div', null ),
	};
} );

/* Stub the heavy components so importing the editor module stays cheap. */
const Noop = () => null;
jest.mock( '@wordpress/editor', () => ( {
	EditorNotices: Noop,
	EditorProvider: Noop,
	EditorSnackbars: Noop,
	ErrorBoundary: Noop,
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
jest.mock( '@wordpress/keyboard-shortcuts', () => ( { ShortcutProvider: () => null } ) );
jest.mock( '../../block-editor', () => ( { __esModule: true, default: Noop } ) );
jest.mock( '../../header', () => ( { __esModule: true, default: Noop } ) );
jest.mock( '../../secondary-sidebar/inserter-sidebar', () => ( { __esModule: true, default: Noop } ) );
jest.mock( '../../secondary-sidebar/list-view-sidebar', () => ( { __esModule: true, default: Noop } ) );
jest.mock( '../../keyboard-shortcuts', () => ( { __esModule: true, default: Noop } ) );
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
} );
