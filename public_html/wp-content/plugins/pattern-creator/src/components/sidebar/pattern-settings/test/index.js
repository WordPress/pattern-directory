/**
 * WordPress dependencies
 */
import { createElement, createRoot } from '@wordpress/element';
import { select } from '@wordpress/data';

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
 * Stand in for `core/editor` with a store that keeps the edited meta, so the
 * component's `useSelect` and `editPost` calls stay real.
 */
jest.mock( '@wordpress/editor', () => {
	const { createReduxStore, register } = jest.requireActual( '@wordpress/data' );
	const initialMeta = { wpop_description: '', wpop_keywords: '', wpop_viewport_width: 1200 };
	const store = createReduxStore( 'test/pattern-settings-editor', {
		reducer: ( state = { meta: initialMeta }, action ) =>
			action.type === 'EDIT_POST' ? { ...state, ...action.edits } : state,
		selectors: {
			getCurrentPost: () => ( { link: '', status: 'draft' } ),
			getEditedPostAttribute: ( state, key ) => state[ key ],
		},
		actions: {
			editPost: ( edits ) => ( { type: 'EDIT_POST', edits } ),
		},
	} );
	register( store );
	return { store };
} );
jest.mock( '@wordpress/components', () => {
	const { createElement: el } = jest.requireActual( '@wordpress/element' );
	const Wrapper = ( { children } ) => el( 'div', null, children );
	return {
		ExternalLink: () => null,
		FormTokenField: () => null,
		PanelBody: Wrapper,
		PanelRow: Wrapper,
		TextareaControl: () => null,
		TextControl: ( { label, onChange, ...props } ) =>
			el( 'input', {
				...props,
				'aria-label': label,
				onChange: ( event ) => onChange( event.target.value ),
			} ),
	};
} );
jest.mock( '../../../pattern-categories-control', () => ( { __esModule: true, default: () => null } ) );
jest.mock( '../../../../store', () => ( { KEYWORD_SLUG: 'wpop_keywords' } ) );

/**
 * Type a value into an input the way React listens for it.
 *
 * @param {HTMLInputElement} input The input element.
 * @param {string}           value The new value.
 */
function typeInto( input, value ) {
	const setValue = Object.getOwnPropertyDescriptor( window.HTMLInputElement.prototype, 'value' ).set;
	setValue.call( input, value );
	input.dispatchEvent( new window.Event( 'input', { bubbles: true } ) );
}

describe( 'PatternSettings', () => {
	let container;
	let root;
	let getViewportWidth;

	beforeAll( () => {
		global.IS_REACT_ACT_ENVIRONMENT = true;
	} );

	beforeEach( () => {
		const PatternSettings = require( '../index' ).default;
		const { store } = require( '@wordpress/editor' );
		getViewportWidth = () => select( store ).getEditedPostAttribute( 'meta' ).wpop_viewport_width;
		container = document.createElement( 'div' );
		document.body.appendChild( container );
		root = createRoot( container );
		act( () => root.render( createElement( PatternSettings ) ) );
	} );

	afterEach( () => {
		act( () => root.unmount() );
		container.remove();
	} );

	it( 'shows the viewport width and stores a valid new width', () => {
		// The component's `useSelect` builds a new `keywords` array on every call, which the data package warns about in development.
		expect( console ).toHaveWarned();
		const input = container.querySelector( 'input[aria-label="Viewport width"]' );
		expect( input.value ).toBe( '1200' );

		act( () => typeInto( input, '1400' ) );
		act( () => input.dispatchEvent( new window.FocusEvent( 'focusout', { bubbles: true } ) ) );

		expect( getViewportWidth() ).toBe( 1400 );
		expect( input.value ).toBe( '1400' );
	} );

	it( 'does not store a width outside the allowed range, and restores the stored one on blur', () => {
		// The component's `useSelect` builds a new `keywords` array on every call, which the data package warns about in development.
		expect( console ).toHaveWarned();
		const input = container.querySelector( 'input[aria-label="Viewport width"]' );
		const before = getViewportWidth();

		// Typing 2400 passes through 240, which is in range on its own.
		act( () => typeInto( input, '240' ) );
		act( () => typeInto( input, '2400' ) );

		expect( input.value ).toBe( '2400' );
		expect( getViewportWidth() ).toBe( before );

		act( () => input.dispatchEvent( new window.FocusEvent( 'focusout', { bubbles: true } ) ) );

		expect( input.value ).toBe( String( before ) );
	} );
} );
