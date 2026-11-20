/**
 * @jest-environment node
 */

/* eslint jsdoc/check-tag-names: [ "error", { "definedTags": [ "jest-environment" ] } ] */

/**
 * Internal dependencies
 */
import filterEndpoints from '../filter-endpoints';

const PLACEHOLDER_CONTENT =
	'<!-- wp:navigation-link {"label":"Home","url":"#","kind":"custom","isTopLevelLink":true} /-->' +
	'<!-- wp:navigation-link {"label":"About","url":"#","kind":"custom","isTopLevelLink":true} /-->' +
	'<!-- wp:navigation-link {"label":"Contact","url":"#","kind":"custom","isTopLevelLink":true} /-->';

// No `next` handler: a request that isn't short-circuited throws.
describe( 'filterEndpoints navigation mocks', () => {
	it( 'returns the placeholder menu as the Navigation fallback', async () => {
		const response = await filterEndpoints( {
			path: '/wp-block-editor/v1/navigation-fallback?_embed=true',
		} );

		expect( response.id ).toBe( 1 );
		expect( response._embedded.self ).toHaveLength( 1 );
		expect( response._embedded.self[ 0 ] ).toMatchObject( {
			id: 1,
			status: 'publish',
			content: { raw: PLACEHOLDER_CONTENT },
		} );
	} );

	it( 'returns a raw response for paginated menu queries', async () => {
		const response = await filterEndpoints( {
			path: '/wp/v2/navigation?context=edit&per_page=100&status=publish%2Cdraft',
			parse: false,
		} );

		expect( response.headers.get( 'X-WP-Total' ) ).toBe( '1' );
		expect( response.headers.get( 'X-WP-TotalPages' ) ).toBe( '1' );
		expect( response.headers.get( 'Allow' ) ).toBe( 'GET' );
		expect( await response.json() ).toMatchObject( [ { id: 1, content: { raw: PLACEHOLDER_CONTENT } } ] );
	} );

	it( 'reports the placeholder menu as read-only to permission checks', async () => {
		const response = await filterEndpoints( {
			path: '/wp/v2/navigation/42',
			method: 'OPTIONS',
			parse: false,
		} );

		expect( response.headers.get( 'Allow' ) ).toBe( 'GET' );
	} );
} );

describe( 'filterEndpoints post content', () => {
	const posts = [
		{ id: 1, content: { rendered: '<p>First</p>', protected: false } },
		{ id: 2, content: { rendered: '<p>Second</p>', raw: 'Raw', protected: false } },
	];

	it( 'adds the raw content to a parsed list of posts', async () => {
		const response = await filterEndpoints( { path: '/wp/v2/posts?context=view' }, async () =>
			structuredClone( posts )
		);

		expect( response.map( ( post ) => post.content.raw ) ).toEqual( [ '<p>First</p>', 'Raw' ] );
	} );

	it( 'adds the raw content to a single post', async () => {
		const response = await filterEndpoints( { path: '/wp/v2/posts/1?context=view' }, async () =>
			structuredClone( posts[ 0 ] )
		);

		expect( response.content.raw ).toBe( '<p>First</p>' );
	} );

	it( 'keeps the headers of a raw response for paginated post queries', async () => {
		const response = await filterEndpoints(
			{ path: '/wp/v2/posts?context=edit&per_page=5', parse: false },
			async () =>
				new Response( JSON.stringify( posts ), {
					headers: { 'Content-Type': 'application/json', 'X-WP-Total': '2', 'X-WP-TotalPages': '1' },
				} )
		);

		expect( response ).toBeInstanceOf( Response );
		expect( response.headers.get( 'X-WP-Total' ) ).toBe( '2' );
		expect( response.headers.get( 'X-WP-TotalPages' ) ).toBe( '1' );
		const body = await response.json();
		expect( body.map( ( post ) => post.content.raw ) ).toEqual( [ '<p>First</p>', 'Raw' ] );
	} );
} );
