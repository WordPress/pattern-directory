/**
 * Internal dependencies
 */
import { setIsListViewOpened, setPage, toggleFeature } from '../actions';

describe( 'actions', () => {
	describe( 'toggleFeature', () => {
		it( 'should return TOGGLE_FEATURE action', () => {
			const feature = 'name';
			expect( toggleFeature( feature ) ).toEqual( {
				type: 'TOGGLE_FEATURE',
				feature: feature,
			} );
		} );
	} );

	describe( 'setPage', () => {
		// eslint-disable-next-line jest/no-disabled-tests
		it.skip( 'should yield the FIND_TEMPLATE control and return the SET_PAGE action', () => {
			const page = { path: '/' };

			// eslint-disable-next-line id-length
			const it = setPage( page );
			expect( it.next().value ).toEqual( {
				type: '@@data/RESOLVE_SELECT',
				storeKey: 'core',
				selectorName: '__experimentalGetTemplateForLink',
				args: [ page.path ],
			} );
			expect( it.next( { id: 'tt1-blocks//single' } ).value ).toEqual( {
				type: 'SET_PAGE',
				page: page,
				templateId: 'tt1-blocks//single',
			} );
			expect( it.next().done ).toBe( true );
		} );
	} );

	describe( 'setIsListViewOpened', () => {
		it( 'should return the SET_IS_LIST_VIEW_OPENED action', () => {
			expect( setIsListViewOpened( true ) ).toEqual( {
				type: 'SET_IS_LIST_VIEW_OPENED',
				isOpen: true,
			} );
			expect( setIsListViewOpened( false ) ).toEqual( {
				type: 'SET_IS_LIST_VIEW_OPENED',
				isOpen: false,
			} );
		} );
	} );
} );
