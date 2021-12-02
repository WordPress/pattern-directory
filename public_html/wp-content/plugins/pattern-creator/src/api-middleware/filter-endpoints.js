// Use a middleware provider to intercept and modify API calls.
// Short-circuit POST requests, bound queries, allow media, etc.
export default function ( options, next ) {
	if ( options.path ) {
		// Add limits to all GET queries which attempt unbound queries
		options.path = options.path.replace( 'per_page=-1', 'per_page=50' );
	}

	return next( options );
}
