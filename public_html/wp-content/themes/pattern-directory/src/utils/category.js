/**
 * Remove the last '/' from a string if it exists.
 *
 * @param {string} str Url or url part
 * @return {string}
 */
const removeTrailingSlash = ( str ) => {
	return str.replace( /\/$/, '' );
};

/**
 * Retrieves the category from a url pathname without query string params.
 *
 * @param {string} path
 * @return {string} The category slug.
 */
export const getCategoryFromPathname = ( path ) => {
	const _path = removeTrailingSlash( path );
	return _path.substring( _path.lastIndexOf( '/' ) + 1 );
};
