import { getCategoryFromPathname } from '../index';

describe( 'utils', () => {
	describe( 'getCategoryFromPathname', () => {
		it( 'should return empty string if path is empty', async () => {
			expect( getCategoryFromPathname( '' ) ).toEqual( '' );
		} );

		it( 'should return empty string if path is "/"', async () => {
			expect( getCategoryFromPathname( '/' ) ).toEqual( '' );
		} );

		it( 'should correctly return category', async () => {
			expect( getCategoryFromPathname( 'patterns-categories/header' ) ).toEqual( 'header' );
			expect( getCategoryFromPathname( '/patterns-categories/header' ) ).toEqual( 'header' );
			expect( getCategoryFromPathname( '/patterns-categories/header/' ) ).toEqual( 'header' );
			expect( getCategoryFromPathname( '/patterns-categories/subdirectory/header/' ) ).toEqual( 'header' );
		} );
	} );
} );
