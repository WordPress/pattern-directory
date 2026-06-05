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

/*
 * Mock the data and store layers so the test can drive the `post` value
 * directly. `useSelect` is replaced, but `useEffect` (from `@wordpress/element`)
 * is left intact so the test exercises the real effect dependency array.
 */
let mockPost;
jest.mock( '@wordpress/data', () => ( {
	useSelect: () => mockPost,
} ) );
jest.mock( '@wordpress/core-data', () => ( {
	store: 'core',
} ) );
jest.mock( '../../../store', () => ( {
	POST_TYPE: 'wporg-pattern',
} ) );

describe( 'UrlController', () => {
	let UrlController;
	let container;
	let root;

	beforeAll( () => {
		global.IS_REACT_ACT_ENVIRONMENT = true;
		global.wporgBlockPattern = { siteUrl: 'https://example.com' };
		// `BASE_URL` is read at module load, so require after the global is set.
		UrlController = require( '../index' ).default;
	} );

	beforeEach( () => {
		container = document.createElement( 'div' );
		document.body.appendChild( container );
		root = createRoot( container );
		jest.spyOn( window.history, 'replaceState' ).mockImplementation( () => {} );
	} );

	afterEach( () => {
		act( () => root.unmount() );
		container.remove();
		jest.restoreAllMocks();
	} );

	const render = ( postId ) => {
		act( () => {
			root.render( createElement( UrlController, { postId } ) );
		} );
	};

	it( 'updates the URL once the post is no longer an auto-draft', () => {
		mockPost = { status: 'publish' };

		render( 1 );

		expect( window.history.replaceState ).toHaveBeenCalledWith(
			{},
			'',
			'https://example.com/pattern/1/edit/'
		);
	} );

	it( 'leaves the URL alone while the post is still an auto-draft', () => {
		mockPost = { status: 'auto-draft' };

		render( 1 );

		expect( window.history.replaceState ).not.toHaveBeenCalled();
	} );

	it( 'updates the URL when only the postId changes', () => {
		mockPost = { status: 'publish' };

		render( 1 );
		window.history.replaceState.mockClear();

		// The status is unchanged; only `postId` changes. Without `postId` in the
		// effect's dependency array the URL would keep the stale id.
		render( 2 );

		expect( window.history.replaceState ).toHaveBeenCalledWith(
			{},
			'',
			'https://example.com/pattern/2/edit/'
		);
	} );
} );
