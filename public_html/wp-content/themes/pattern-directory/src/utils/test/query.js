import { getCategoryFromPath, removeEmptyArgs, removeQueryString } from '../index';

describe( 'utils', () => {
	describe( 'getCategoryFromPath', () => {
		it( 'should return empty string if path is empty', async () => {
			expect( getCategoryFromPath( '' ) ).toEqual( '' );
		} );

		it( 'should return empty string if path is "/"', async () => {
			expect( getCategoryFromPath( '/' ) ).toEqual( '' );
		} );

		it( 'should return empty string if the path is malformed', async () => {
			expect( getCategoryFromPath( '/pattern-categories' ) ).toEqual( '' );
			expect( getCategoryFromPath( '/nothing' ) ).toEqual( '' );
			expect( getCategoryFromPath( '/1/2/3/' ) ).toEqual( '' );
		} );

		it( 'should correctly return the category from a path', async () => {
			expect( getCategoryFromPath( 'pattern-categories/header' ) ).toEqual( 'header' );
			expect( getCategoryFromPath( '/pattern-categories/header' ) ).toEqual( 'header' );
			expect( getCategoryFromPath( '/pattern-categories/header/page/3/' ) ).toEqual( 'header' );
			expect( getCategoryFromPath( '/pattern-categories/header/?search=test' ) ).toEqual( 'header' );
			expect( getCategoryFromPath( '/pattern-categories/words-and-123/' ) ).toEqual( 'words-and-123' );
		} );

		it( 'should correctly return the category from a query string', async () => {
			expect( getCategoryFromPath( '/?pattern-categories=header' ) ).toEqual( 'header' );
			expect( getCategoryFromPath( '/?pattern-categories=header&page=2' ) ).toEqual( 'header' );
			expect( getCategoryFromPath( '/?search=test' ) ).toEqual( '' );
			expect( getCategoryFromPath( '/?search=test&pattern-categories=header' ) ).toEqual( 'header' );
		} );

		it( 'should correctly return the category from a combined path & query string', async () => {
			expect( getCategoryFromPath( '/pattern-categories/abc/?pattern-categories=def' ) ).toEqual( 'def' );
		} );
	} );

	describe( 'removeQueryString', () => {
		it( 'should return the path if there is no query string', async () => {
			const path = '/path/without/string';

			expect( removeQueryString( path ) ).toEqual( path );
		} );

		it( 'should return the path if there is a query string', async () => {
			const base = '/path/without/string';
			const path = `${ base }?search=keyword`;

			expect( removeQueryString( path ) ).toEqual( base );
		} );
	} );

	describe( 'removeEmptyArgs', () => {
		it( 'should remove empty properties', async () => {
			const shouldReturn = {
				string_1: 'first value',
				string_2: 'second value',
				boolean: false,
			};

			const obj = {
				...shouldReturn,
				empty_string: '',
				undefined: undefined,
				null: null,
			};

			expect( removeEmptyArgs( {} ) ).toMatchObject( {} );
			expect( removeEmptyArgs( obj ) ).toMatchObject( shouldReturn );
		} );
	} );
} );
