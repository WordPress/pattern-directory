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

const mockFetchImages = jest.fn();
jest.mock( '../utils', () => ( {
	fetchImages: ( ...args ) => mockFetchImages( ...args ),
} ) );

// Make the search debounce synchronous so the fetch fires within `act`.
jest.mock( '@wordpress/compose', () => ( {
	useDebounce: ( fn ) => fn,
} ) );

/*
 * Stub the chrome so the test drives selection and commits directly. The unit
 * under test is `onCommitSelected`'s dependency array, not the grid layout.
 */
jest.mock( '@wordpress/components', () => {
	const { createElement: el } = jest.requireActual( '@wordpress/element' );
	return {
		Button: ( { onClick, children } ) => el( 'button', { onClick }, children ),
		Notice: ( { children } ) => el( 'div', null, children ),
		Spinner: () => el( 'span', null, 'Loading' ),
	};
} );
jest.mock( '../grid-items', () => {
	const { createElement: el } = jest.requireActual( '@wordpress/element' );
	return {
		__esModule: true,
		default: ( { items, onSelect } ) =>
			el(
				'div',
				null,
				items.map( ( item ) =>
					el( 'button', { key: item.id, onClick: () => onSelect( item ) }, `select-${ item.id }` )
				)
			),
	};
} );
jest.mock( '../grid-actions', () => {
	const { createElement: el } = jest.requireActual( '@wordpress/element' );
	return {
		__esModule: true,
		default: ( { actions } ) => el( 'div', null, actions ),
	};
} );
jest.mock( '../pagination', () => ( {
	__esModule: true,
	default: () => null,
} ) );

describe( 'OpenverseGrid', () => {
	let OpenverseGrid;
	let container;
	let root;

	beforeAll( () => {
		global.IS_REACT_ACT_ENVIRONMENT = true;
		OpenverseGrid = require( '../grid' ).default;
	} );

	beforeEach( () => {
		mockFetchImages.mockResolvedValue( {
			results: [ { id: 1, url: 'https://example.com/1.jpg', title: 'Cat' } ],
			total: 1,
			totalPages: 1,
		} );
		container = document.createElement( 'div' );
		document.body.appendChild( container );
		root = createRoot( container );
	} );

	afterEach( () => {
		act( () => root.unmount() );
		container.remove();
		mockFetchImages.mockReset();
	} );

	const renderGrid = async ( props ) => {
		await act( async () => {
			root.render( createElement( OpenverseGrid, { searchTerm: 'cat', multiple: false, ...props } ) );
		} );
		// Flush the resolved fetch and the follow-up render it triggers.
		await act( async () => {} );
	};

	const button = ( text ) =>
		[ ...container.querySelectorAll( 'button' ) ].find( ( el ) => el.textContent === text );

	const click = ( el ) => act( () => el.click() );

	it( 'commits the selected image to the current onSelect / onClose props', async () => {
		const onSelect = jest.fn();
		const onClose = jest.fn();

		await renderGrid( { onSelect, onClose } );
		click( button( 'select-1' ) );
		click( button( 'Add media' ) );

		expect( onSelect ).toHaveBeenCalledWith(
			expect.objectContaining( { url: 'https://example.com/1.jpg', caption: 'Cat' } )
		);
		expect( onClose ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'commits to the latest props after they change', async () => {
		const firstSelect = jest.fn();
		const firstClose = jest.fn();
		const latestSelect = jest.fn();
		const latestClose = jest.fn();

		await renderGrid( { onSelect: firstSelect, onClose: firstClose } );
		click( button( 'select-1' ) );

		// Swap the callbacks while a selection is held. Without `onSelect` /
		// `onClose` in the dependency array, the memoized handler keeps calling
		// the stale props.
		await act( async () => {
			root.render(
				createElement( OpenverseGrid, {
					searchTerm: 'cat',
					multiple: false,
					onSelect: latestSelect,
					onClose: latestClose,
				} )
			);
		} );
		click( button( 'Add media' ) );

		expect( latestSelect ).toHaveBeenCalledTimes( 1 );
		expect( latestClose ).toHaveBeenCalledTimes( 1 );
		expect( firstSelect ).not.toHaveBeenCalled();
		expect( firstClose ).not.toHaveBeenCalled();
	} );
} );
