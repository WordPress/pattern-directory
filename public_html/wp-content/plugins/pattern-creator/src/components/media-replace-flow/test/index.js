/**
 * WordPress dependencies
 */
import { createElement, createRoot } from '@wordpress/element';

// Render and `act` must share the React copy `@wordpress/element` bundles; see editor/test/index.js.
const { act } = jest.requireActual(
	require.resolve( 'react', { paths: [ require.resolve( '@wordpress/element' ) ] } )
);

const mockInsertBlocks = jest.fn();
const mockBlockContext = { name: '', clientId: 'block-1' };
const mockPicked = [
	{ id: null, url: 'https://example.org/a.jpg', alt: '', caption: 'A' },
	{ id: null, url: 'https://example.org/b.jpg', alt: '', caption: 'B' },
];

// The modal stands in for Openverse: "Open Media Library" picks `mockPicked` straight away.
jest.mock( '@wordpress/block-editor', () => ( {
	store: 'core/block-editor',
	useBlockEditContext: () => mockBlockContext,
	MediaUpload: ( { render, multiple, onSelect } ) =>
		render( { open: () => onSelect( multiple ? mockPicked : mockPicked[ 0 ] ) } ),
} ) );
jest.mock( '@wordpress/data', () => ( { useDispatch: () => ( { insertBlocks: mockInsertBlocks } ) } ) );
jest.mock( '@wordpress/blocks', () => ( {
	createBlock: ( name, attributes ) => ( { name, attributes } ),
} ) );
jest.mock( '@wordpress/components', () => {
	const { createElement: el } = jest.requireActual( '@wordpress/element' );
	return {
		// Open the menu right away, so the test can pick from it.
		Dropdown: ( { renderContent } ) => renderContent( { onClose: () => {} } ),
		MenuItem: ( { onClick, children } ) => el( 'button', { onClick }, children ),
		NavigableMenu: ( { children } ) => el( 'div', null, children ),
		ToolbarButton: ( { children } ) => el( 'button', null, children ),
	};
} );

describe( 'MediaReplaceFlow', () => {
	let MediaReplaceFlow;
	let container;
	let root;

	beforeAll( () => {
		global.IS_REACT_ACT_ENVIRONMENT = true;
		MediaReplaceFlow = require( '../index' ).default;
	} );

	beforeEach( () => {
		container = document.createElement( 'div' );
		document.body.appendChild( container );
		root = createRoot( container );
		mockInsertBlocks.mockReset();
	} );

	afterEach( () => {
		act( () => root.unmount() );
		container.remove();
	} );

	const pick = ( blockName, props ) => {
		mockBlockContext.name = blockName;
		act( () => root.render( createElement( MediaReplaceFlow, props ) ) );
		act( () => {
			[ ...container.querySelectorAll( 'button' ) ]
				.find( ( button ) => 'Open Media Library' === button.textContent )
				.click();
		} );
	};

	// Regression (#716): the gallery matches images by ID, and Openverse media have none, so it added nothing.
	it( 'appends picked images to a gallery instead of handing them to it', () => {
		const onSelect = jest.fn();
		pick( 'core/gallery', { multiple: true, onSelect } );

		expect( onSelect ).not.toHaveBeenCalled();
		expect( mockInsertBlocks ).toHaveBeenCalledWith(
			mockPicked.map( ( { url, alt, caption } ) => ( {
				name: 'core/image',
				attributes: { url, alt, caption },
			} ) ),
			undefined,
			'block-1'
		);
	} );

	it( 'hands the picked image to other blocks, which replace their own', () => {
		const onSelect = jest.fn();
		pick( 'core/cover', { onSelect } );

		expect( onSelect ).toHaveBeenCalledWith( mockPicked[ 0 ] );
		expect( mockInsertBlocks ).not.toHaveBeenCalled();
	} );
} );
