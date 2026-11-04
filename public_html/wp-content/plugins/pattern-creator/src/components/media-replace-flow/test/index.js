/**
 * Tests for the Openverse media replace menu.
 */

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
const mockGallery = { linkTo: 'media', linkTarget: '_blank', sizeSlug: 'medium', aspectRatio: 'auto' };
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
jest.mock( '@wordpress/data', () => ( {
	useDispatch: () => ( { insertBlocks: mockInsertBlocks } ),
	useSelect: () => ( { getBlockAttributes: () => mockGallery } ),
} ) );
jest.mock( '@wordpress/a11y', () => ( { speak: () => {} } ) );
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

	/**
	 * Renders the menu inside the given block.
	 *
	 * @param {string} blockName The block's name.
	 * @param {Object} props     The menu's props.
	 */
	const render = ( blockName, props ) => {
		mockBlockContext.name = blockName;
		act( () => root.render( createElement( MediaReplaceFlow, props ) ) );
	};

	/**
	 * Labels of the menu's items.
	 *
	 * @return {string[]} The labels.
	 */
	const menuItems = () => [ ...container.querySelectorAll( 'button' ) ].map( ( button ) => button.textContent );

	/**
	 * Renders an image menu inside the given block and picks from Openverse.
	 *
	 * @param {string} blockName The block's name.
	 * @param {Object} props     The menu's props.
	 */
	const pick = ( blockName, props ) => {
		render( blockName, { allowedTypes: [ 'image' ], ...props } );
		act( () => {
			[ ...container.querySelectorAll( 'button' ) ]
				.find( ( button ) => 'Open Media Library' === button.textContent )
				.click();
		} );
	};

	/*
	 * Regression (#716): the gallery matches images by ID, and Openverse media have none, so it added nothing.
	 * Appended images still need the gallery's link and size settings, which core applies only by ID.
	 */
	it( 'appends picked images to a gallery instead of handing them to it', () => {
		const onSelect = jest.fn();
		pick( 'core/gallery', { multiple: true, onSelect } );

		expect( onSelect ).not.toHaveBeenCalled();
		expect( mockInsertBlocks ).toHaveBeenCalledWith(
			mockPicked.map( ( { url, alt, caption } ) => ( {
				name: 'core/image',
				attributes: {
					url,
					alt,
					caption,
					href: url,
					linkDestination: 'media',
					linkTarget: '_blank',
					sizeSlug: 'medium',
				},
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

	// A cover or Media & Text showing the featured image would keep showing it over the pick.
	it( 'stops using the featured image before handing over the pick', () => {
		const calls = [];
		pick( 'core/cover', {
			useFeaturedImage: true,
			onToggleFeaturedImage: () => calls.push( 'toggle' ),
			onSelect: () => calls.push( 'select' ),
		} );

		expect( calls ).toEqual( [ 'toggle', 'select' ] );
	} );

	// Openverse only returns images, which a video, audio or file block would take as its own media.
	it( 'offers only Reset to blocks that take no images', () => {
		render( 'core/video', {
			allowedTypes: [ 'video' ],
			mediaURL: 'https://example.org/a.mp4',
			onReset: () => {},
		} );

		expect( menuItems() ).toEqual( [ 'Reset' ] );
	} );
} );
