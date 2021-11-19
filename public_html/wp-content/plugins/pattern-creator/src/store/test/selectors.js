/**
 * Internal dependencies
 */
import { getSettings, isFeatureActive, isInserterOpened, isListViewOpened } from '../selectors';

describe( 'selectors', () => {
	const canUser = jest.fn( () => true );

	describe( 'isFeatureActive', () => {
		it( 'is tolerant to an undefined features preference', () => {
			// See: https://github.com/WordPress/gutenberg/issues/14580
			const state = {
				preferences: {},
			};

			expect( isFeatureActive( state, 'chicken' ) ).toBe( false );
		} );

		it( 'should return true if feature is active', () => {
			const state = {
				preferences: {
					features: {
						chicken: true,
					},
				},
			};

			expect( isFeatureActive( state, 'chicken' ) ).toBe( true );
		} );

		it( 'should return false if feature is not active', () => {
			const state = {
				preferences: {
					features: {
						chicken: false,
					},
				},
			};

			expect( isFeatureActive( state, 'chicken' ) ).toBe( false );
		} );

		it( 'should return false if feature is not referred', () => {
			const state = {
				preferences: {
					features: {},
				},
			};

			expect( isFeatureActive( state, 'chicken' ) ).toBe( false );
		} );
	} );

	describe( 'getSettings', () => {
		it( "returns the settings when the user can't create media", () => {
			canUser.mockReturnValueOnce( false );
			canUser.mockReturnValueOnce( false );
			const state = { settings: {}, preferences: {} };
			const setInserterOpened = () => {};
			expect( getSettings( state, setInserterOpened ) ).toEqual( {
				outlineMode: true,
				focusMode: false,
				hasFixedToolbar: false,
				__experimentalSetIsInserterOpened: setInserterOpened,
			} );
		} );

		it( 'returns the extended settings when the user can create media', () => {
			const state = {
				settings: { key: 'value' },
				preferences: {
					features: {
						focusMode: true,
						fixedToolbar: true,
					},
				},
			};
			const setInserterOpened = () => {};
			expect( getSettings( state, setInserterOpened ) ).toEqual( {
				outlineMode: true,
				key: 'value',
				focusMode: true,
				hasFixedToolbar: true,
				__experimentalSetIsInserterOpened: setInserterOpened,
			} );
		} );
	} );

	describe( 'isInserterOpened', () => {
		it( 'returns the block inserter panel isOpened state', () => {
			const state = {
				blockInserterPanel: true,
			};
			expect( isInserterOpened( state ) ).toBe( true );
			state.blockInserterPanel = false;
			expect( isInserterOpened( state ) ).toBe( false );
		} );
	} );

	describe( 'isListViewOpened', () => {
		it( 'returns the list view panel isOpened state', () => {
			const state = {
				listViewPanel: true,
			};
			expect( isListViewOpened( state ) ).toBe( true );
			state.listViewPanel = false;
			expect( isListViewOpened( state ) ).toBe( false );
		} );
	} );
} );
