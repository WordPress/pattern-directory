/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * ID of the placeholder Navigation menu.
 *
 * Every `wp_navigation` read is mocked, and `decode_pattern_content()` strips
 * a saved `ref`, so any positive integer works.
 */
const MOCK_NAVIGATION_ID = 1;

/**
 * Inner blocks of the placeholder Navigation menu.
 *
 * Keep in sync with `provide_fallback_nav_items()` in `includes/mock-blocks.php`.
 */
const MOCK_NAVIGATION_CONTENT = [ 'Home', 'About', 'Contact' ]
	.map(
		( label ) =>
			`<!-- wp:navigation-link {"label":"${ label }","url":"#","kind":"custom","isTopLevelLink":true} /-->`
	)
	.join( '' );

/**
 * Build a `wp_navigation` record holding the placeholder menu.
 *
 * @param {number} id The menu ID.
 * @return {Object} The menu record.
 */
function getMockNavigation( id ) {
	const title = __( 'Navigation', 'wporg-patterns' );

	return {
		id,
		type: 'wp_navigation',
		status: 'publish',
		title: { raw: title, rendered: title },
		content: { raw: MOCK_NAVIGATION_CONTENT, rendered: '', protected: false },
	};
}

/**
 * Mock the menu endpoints the Navigation block reads, which regular users can't access.
 *
 * The front end always renders the placeholder menu from `includes/mock-blocks.php`,
 * so the editor shows that same menu to everyone.
 *
 * @param {string} path The endpoint path (possibly with a query string).
 * @return {Object|Array|undefined} The mocked response body, if the endpoint is mocked.
 */
function getMockNavigationResponse( path ) {
	if ( /^\/wp-block-editor\/v1\/navigation-fallback(\?|$)/.test( path ) ) {
		return {
			id: MOCK_NAVIGATION_ID,
			_embedded: { self: [ getMockNavigation( MOCK_NAVIGATION_ID ) ] },
		};
	}

	const navigation = path.match( /^\/wp\/v2\/navigation(?:\/(\d+))?(\?|$)/ );
	if ( navigation ) {
		return navigation[ 1 ]
			? getMockNavigation( Number( navigation[ 1 ] ) )
			: [ getMockNavigation( MOCK_NAVIGATION_ID ) ];
	}

	// No classic menus, so the block never offers to import one.
	if ( /^\/wp\/v2\/menus(\?|$)/.test( path ) ) {
		return [];
	}

	return undefined;
}

/**
 * Update the `context` query parameter to view for a set of endpoints.
 *
 * @param {string} path The endpoint path (possibly with a query string).
 * @return {string} The path with the updated context.
 */
function maybeUpdateContext( path ) {
	if ( ! path.includes( 'context=' ) ) {
		return path;
	}

	const endpointRegexes = [
		// All post endpoints.
		/^\/wp\/v2\/posts/,
		/^\/wp\/v2\/blocks/,
		/^\/wp\/v2\/tags/,
		/^\/wp\/v2\/categories/,
		// Category and tag taxonomies.
		/^\/wp\/v2\/taxonomies\/(category|post_tag)/,
		// All post type endpoints except `wporg-pattern`, which needs to keep the `edit` context.
		/^\/wp\/v2\/types(?!\/wporg-pattern)/,
	];

	if ( endpointRegexes.some( ( regex ) => regex.test( path ) ) ) {
		return path.replace( 'context=edit', 'context=view' );
	}

	return path;
}

// Use a middleware provider to intercept and modify API calls.
// Short-circuit POST requests, bound queries, allow media, etc.
export default async function ( options, next ) {
	if ( options.path ) {
		// Add limits to all GET queries which attempt unbound queries
		options.path = options.path.replace( 'per_page=-1', 'per_page=50' );

		options.path = maybeUpdateContext( options.path );

		const isGet = ! options.method || 'GET' === options.method.toUpperCase();
		const mock = isGet && getMockNavigationResponse( options.path );
		if ( mock ) {
			// Paginated entity queries ask for the raw `Response` to read the totals.
			if ( false === options.parse ) {
				const total = String( Array.isArray( mock ) ? mock.length : 1 );
				return new Response( JSON.stringify( mock ), {
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Total': total,
						'X-WP-TotalPages': '1',
					},
				} );
			}

			return mock;
		}
	}

	// Wait to get the API response back, so it can be filtered.
	const response = await next( options );

	if ( options.path ) {
		if ( options.path.includes( '/wp/v2/types?' ) ) {
			// Set posts to viewable, but pages and patterns are not viewable.
			// This controls whether these post-types are options in the Query
			// Loop block. Pages are disabled because a regular user trying to
			// access pages results in a `403` error. Patterns are disabled
			// because they won't exist on other sites.
			if ( response.post ) {
				response.post.viewable = true;
			}
			if ( response.page ) {
				response.page.viewable = false;
			}
			if ( response[ 'wporg-pattern' ] ) {
				response[ 'wporg-pattern' ].viewable = false;
			}
		} else if ( options.path.includes( '/wp/v2/posts' ) ) {
			response.forEach( ( post, index ) => {
				if ( post.content && ! post.content.raw ) {
					const content = post.content.rendered || '';
					post.content.raw = content;
				}
				response[ index ] = post;
			} );
		}
	}

	return response;
}
