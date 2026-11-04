/**
 * WordPress dependencies
 */
import { createElement, createRoot } from '@wordpress/element';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store as patternStore } from '../../../../store';

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
 * Replace the real store with a minimal stand-in that mirrors `isFeatureActive`
 * / `toggleFeature`. This keeps `useSelect` (the unit under test) real while
 * avoiding the heavy `@wordpress/core-data` graph the real store pulls in.
 */
jest.mock( '../../../../store', () => {
	const { createReduxStore, register } = jest.requireActual( '@wordpress/data' );
	const store = createReduxStore( 'test/feature-toggle', {
		reducer: ( state = {}, action ) =>
			action.type === 'TOGGLE_FEATURE' ? { ...state, [ action.feature ]: ! state[ action.feature ] } : state,
		actions: {
			toggleFeature: ( feature ) => ( { type: 'TOGGLE_FEATURE', feature } ),
		},
		selectors: {
			isFeatureActive: ( state, feature ) => !! state[ feature ],
		},
	} );
	register( store );
	return { store };
} );

/*
 * The unit under test is the `useSelect` dependency array that derives the
 * selected state, not the `MenuItem` chrome. Stub it so the markup exposes that
 * state directly.
 */
jest.mock( '@wordpress/components', () => {
	const { createElement: el } = jest.requireActual( '@wordpress/element' );
	return {
		MenuItem: ( { isSelected, children } ) =>
			el( 'button', { type: 'button', 'aria-checked': String( !! isSelected ) }, children ),
	};
} );

describe( 'FeatureToggle', () => {
	let FeatureToggle;
	let container;
	let root;

	beforeAll( () => {
		global.IS_REACT_ACT_ENVIRONMENT = true;
		FeatureToggle = require( '../index' ).default;
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

	const render = ( feature ) => {
		act( () => {
			root.render( createElement( FeatureToggle, { feature, label: 'Toggle' } ) );
		} );
	};

	const isActive = () => container.querySelector( 'button' ).getAttribute( 'aria-checked' );

	it( 'reflects the active state of the feature it is given', () => {
		dispatch( patternStore ).toggleFeature( 'reflects-active' );

		render( 'reflects-active' );

		expect( isActive() ).toBe( 'true' );
	} );

	it( 'reports an untouched feature as inactive', () => {
		render( 'never-toggled' );

		expect( isActive() ).toBe( 'false' );
	} );

	it( 'updates when the feature prop changes', () => {
		dispatch( patternStore ).toggleFeature( 'prop-change-on' );

		render( 'prop-change-on' );
		expect( isActive() ).toBe( 'true' );

		// Re-render with a different, inactive feature. Without `feature` in the
		// `useSelect` dependency array, the stale closure keeps reporting the
		// first feature's state.
		render( 'prop-change-off' );
		expect( isActive() ).toBe( 'false' );
	} );
} );
