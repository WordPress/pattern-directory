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
		expect( await response.json() ).toMatchObject( [ { id: 1, content: { raw: PLACEHOLDER_CONTENT } } ] );
	} );
} );
