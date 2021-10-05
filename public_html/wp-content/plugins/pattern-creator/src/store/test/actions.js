/**
 * Internal dependencies
 */
import { setIsListViewOpened, toggleFeature } from '../actions';

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
