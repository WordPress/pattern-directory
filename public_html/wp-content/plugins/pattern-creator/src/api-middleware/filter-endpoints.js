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
export default function ( options, next ) {
	if ( options.path ) {
		// Add limits to all GET queries which attempt unbound queries
		options.path = options.path.replace( 'per_page=-1', 'per_page=50' );

		options.path = maybeUpdateContext( options.path );
	}

	return next( options );
}
