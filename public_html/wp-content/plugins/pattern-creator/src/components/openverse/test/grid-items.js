/**
 * WordPress dependencies
 */
import { createElement, createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import OpenverseGridItems from '../grid-items';

// Use the React copy `@wordpress/element` renders with, see `grid.js`.
const { act } = jest.requireActual(
	require.resolve( 'react', { paths: [ require.resolve( '@wordpress/element' ) ] } )
);

// The grid still uses the unstable Composite APIs, which warn once when first used.
jest.mock( '@wordpress/deprecated', () => jest.fn() );

describe( 'OpenverseGridItems', () => {
	let container;
	let root;

	beforeAll( () => {
		global.IS_REACT_ACT_ENVIRONMENT = true;
	} );

	beforeEach( () => {
		container = document.createElement( 'div' );
		document.body.appendChild( container );
		root = createRoot( container );
	} );

	afterEach( () => {
		act( () => root.unmount() );
		container.remove();
	} );

	const item = {
		id: 'a1',
		title: 'Cat',
		url: 'https://example.com/full.jpg',
		thumbnail: 'https://example.com/thumb.jpg',
	};

	const render = async () => {
		await act( async () => {
			root.render(
				createElement( OpenverseGridItems, { items: [ item ], selected: [], onSelect: () => {} } )
			);
		} );
		return container.querySelector( 'img' );
	};

	const fail = ( img ) => act( () => img.dispatchEvent( new Event( 'error' ) ) );

	it( 'shows the thumbnail', async () => {
		const img = await render();

		expect( img.getAttribute( 'src' ) ).toBe( item.thumbnail );
	} );

	it( 'falls back to the full image when the thumbnail fails to load', async () => {
		const img = await render();
		fail( img );

		expect( img.getAttribute( 'src' ) ).toBe( item.url );
	} );

	it( 'falls back only once', async () => {
		const img = await render();
		fail( img );
		img.setAttribute( 'src', 'https://example.com/other.jpg' );
		fail( img );

		expect( img.getAttribute( 'src' ) ).toBe( 'https://example.com/other.jpg' );
	} );
} );
