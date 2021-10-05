/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';

/**
 * Internal dependencies
 */
import { blockInserterPanel, listViewPanel, preferences, settings } from '../reducer';
import { PREFERENCES_DEFAULTS } from '../defaults';

import { setIsInserterOpened, setIsListViewOpened } from '../actions';

describe( 'state', () => {
	describe( 'preferences()', () => {
		it( 'should apply all defaults', () => {
			const state = preferences( undefined, {} );

			expect( state ).toEqual( PREFERENCES_DEFAULTS );
		} );

		it( 'should toggle a feature flag', () => {
			const state = preferences( deepFreeze( { features: { chicken: true } } ), {
				type: 'TOGGLE_FEATURE',
				feature: 'chicken',
			} );

			expect( state.features ).toEqual( { chicken: false } );
		} );
	} );

	describe( 'settings()', () => {
		it( 'should apply default state', () => {
			expect( settings( undefined, {} ) ).toEqual( {} );
		} );

		it( 'should default to returning the same state', () => {
			const state = {};
			expect( settings( state, {} ) ).toBe( state );
		} );

		it( 'should update settings with a shallow merge', () => {
			expect(
				settings(
					deepFreeze( {
						setting: { key: 'value' },
						otherSetting: 'value',
					} ),
					{
						type: 'UPDATE_SETTINGS',
						settings: { setting: { newKey: 'newValue' } },
					}
				)
			).toEqual( {
				setting: { newKey: 'newValue' },
				otherSetting: 'value',
			} );
		} );
	} );

	describe( 'blockInserterPanel()', () => {
		it( 'should apply default state', () => {
			expect( blockInserterPanel( undefined, {} ) ).toEqual( false );
		} );

		it( 'should default to returning the same state', () => {
			expect( blockInserterPanel( true, {} ) ).toBe( true );
		} );

		it( 'should set the open state of the inserter panel', () => {
			expect( blockInserterPanel( false, setIsInserterOpened( true ) ) ).toBe( true );
			expect( blockInserterPanel( true, setIsInserterOpened( false ) ) ).toBe( false );
		} );

		it( 'should close the inserter when opening the list view panel', () => {
			expect( blockInserterPanel( true, setIsListViewOpened( true ) ) ).toBe( false );
		} );

		it( 'should not change the state when closing the list view panel', () => {
			expect( blockInserterPanel( true, setIsListViewOpened( false ) ) ).toBe( true );
		} );
	} );

	describe( 'listViewPanel()', () => {
		it( 'should apply default state', () => {
			expect( listViewPanel( undefined, {} ) ).toEqual( false );
		} );

		it( 'should default to returning the same state', () => {
			expect( listViewPanel( true, {} ) ).toBe( true );
		} );

		it( 'should set the open state of the list view panel', () => {
			expect( listViewPanel( false, setIsListViewOpened( true ) ) ).toBe( true );
			expect( listViewPanel( true, setIsListViewOpened( false ) ) ).toBe( false );
		} );

		it( 'should close the list view when opening the inserter panel', () => {
			expect( listViewPanel( true, setIsInserterOpened( true ) ) ).toBe( false );
		} );

		it( 'should not change the state when closing the inserter panel', () => {
			expect( listViewPanel( true, setIsInserterOpened( false ) ) ).toBe( true );
		} );
	} );
} );
