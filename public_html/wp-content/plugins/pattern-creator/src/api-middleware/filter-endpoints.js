// Use a middleware provider to intercept and modify API calls.
// Short-circuit POST requests, bound queries, allow media, etc.
export default function( options, next ) {
	// Add limits to all GET queries which attempt unbound queries
	options.path = options.path.replace( 'per_page=-1', 'per_page=10' );

	// Load images with the view context, seems to work
	if ( 0 === options.path.indexOf( '/wp/v2/media/' ) ) {
		options.path = options.path.replace( 'context=edit', 'context=view' );
	}

	return next( options );
}
