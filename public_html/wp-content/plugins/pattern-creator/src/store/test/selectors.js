/**
 * Internal dependencies
 */
import { getSettings, isFeatureActive, isInserterOpened, isListViewOpened, isPatternSaveable } from '../selectors';

describe( 'selectors', () => {
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
		it( 'returns the default settings', () => {
			const state = { settings: {}, preferences: {} };
			const setInserterOpened = () => {};
			expect( getSettings( state, setInserterOpened ) ).toEqual( {
				outlineMode: true,
				focusMode: false,
				fullscreenMode: true,
				hasFixedToolbar: true,
				hasReducedUI: false,
				__experimentalSetIsInserterOpened: setInserterOpened,
				__experimentalLocalAutosaveInterval: 30,
			} );
		} );

		it( 'returns the merged settings', () => {
			const state = {
				settings: { key: 'value' },
				preferences: {
					features: {
						focusMode: true,
						fixedToolbar: true,
						reducedUI: true,
					},
				},
			};
			const setInserterOpened = () => {};
			expect( getSettings( state, setInserterOpened ) ).toEqual( {
				key: 'value',
				outlineMode: true,
				focusMode: true,
				fullscreenMode: true,
				hasFixedToolbar: true,
				hasReducedUI: true,
				__experimentalSetIsInserterOpened: setInserterOpened,
				__experimentalLocalAutosaveInterval: 30,
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

	it( 'should return false if post has no blocks', () => {
		isPatternSaveable.registry = {
			select: jest.fn( () => ( {
				getEditedEntityRecord: () => {
					return {
						blocks: [],
					};
				},
			} ) ),
		};

		expect( isPatternSaveable( {} ) ).toBe( false );
	} );

	describe( 'isPatternSaveable', () => {
		it( 'should return true if post has a block', () => {
			isPatternSaveable.registry = {
				select: jest.fn( () => ( {
					getEditedEntityRecord: () => {
						return {
							blocks: [
								{
									attributes: { content: 'w', dropCap: false },
									clientId: 'wordpress',
									innerBlocks: [],
									isValid: true,
									name: 'core/paragraph',
								},
							],
						};
					},
				} ) ),
			};

			expect( isPatternSaveable( {} ) ).toBe( true );
		} );
	} );
} );
