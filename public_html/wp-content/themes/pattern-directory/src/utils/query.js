/**
 * WordPress dependencies
 */
import { getQueryArgs } from '@wordpress/url';

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
 * Remove the first '/' from a string if it exists.
 *
 * @param {string} str Url or url part
 * @return {string}
 */
const removeLeadingSlash = ( str ) => {
	return str.replace( /^\//, '' );
};

/**
 * Removes any empty properties or empty strings.
 *
 * @param {Object} obj
 * @return {Object} Object with keys that are defined and not empty
 */
export const removeEmptyArgs = ( obj ) => {
	const cleaned = {};

	Object.keys( obj ).forEach( ( key ) => {
		const arg = obj[ key ];

		// If it's not undefined or null convert it to string and check that it is not empty.
		// It's a bit of a "trick" to handle non-strings and strings without checking type explicitly.
		if ( arg !== undefined && arg !== null && arg.toString().length > 0 ) {
			cleaned[ key ] = arg;
		}
	} );

	return cleaned;
};

/**
 * Splits a string at ? and returns the first part.
 *
 * @param {string} path Url or url part
 * @return {string} Returns string or string before ? character.
 */
export const removeQueryString = ( path ) => {
	return path.split( '?' )[ 0 ];
};

/**
 * Retrieves the category from a url path.
 * A query string will take precedence, otherwise will fall back to the category in the path.
 *
 * @param {string} path
 * @return {string} The category slug.
 */
export const getCategoryFromPath = ( path ) => {
	const query = getQueryArgs( path );
	if ( query[ 'pattern-categories' ] ) {
		return query[ 'pattern-categories' ];
	}

	const _path = removeLeadingSlash( removeTrailingSlash( removeQueryString( path ) ) );
	const parts = _path.split( '/' );
	// Find the `pattern-categories` section, if it exists. The next part of the URL is the category slug.
	const index = parts.indexOf( 'pattern-categories' );
	if ( -1 === index ) {
		return '';
	}

	return parts[ index + 1 ] || '';
};
