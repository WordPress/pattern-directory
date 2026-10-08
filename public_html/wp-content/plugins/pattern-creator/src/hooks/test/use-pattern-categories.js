/**
 * Internal dependencies
 */
import { filterSelectableCategories } from '../use-pattern-categories';

describe( 'filterSelectableCategories', () => {
	const terms = [
		{ id: 47, name: 'About', slug: 'about' },
		{ id: 3, name: 'Columns', slug: 'columns' },
		{ id: 26, name: 'Featured', slug: 'featured' },
		{ id: 38, name: 'Posts', slug: 'query' },
		{ id: 6, name: 'Text', slug: 'text' },
	];

	it( 'keeps only terms with an allowed slug, in their original order', () => {
		expect( filterSelectableCategories( terms, [ 'text', 'query', 'about' ] ) ).toEqual( [
			{ id: 47, name: 'About', slug: 'about' },
			{ id: 38, name: 'Posts', slug: 'query' },
			{ id: 6, name: 'Text', slug: 'text' },
		] );
	} );

	it( 'ignores allowed slugs that have no matching term', () => {
		expect( filterSelectableCategories( terms, [ 'video', 'about' ] ) ).toEqual( [
			{ id: 47, name: 'About', slug: 'about' },
		] );
	} );

	it( 'returns nothing when no slugs are allowed', () => {
		expect( filterSelectableCategories( terms, [] ) ).toEqual( [] );
	} );
} );
