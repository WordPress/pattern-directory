/**
 * WordPress dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

// Base URL, with CC0 license pre-set.
export const PER_PAGE = 24;

/**
 * Trigger an API request to search for images from the Openverse API.
 *
 * @param {Object} args
 * @param {string} args.searchTerm
 * @param {number} args.page
 */
export async function fetchImages( { searchTerm, page = 1 } ) {
	const path = addQueryArgs( '/wporg/v1/openverse/search', {
		search: searchTerm,
		per_page: PER_PAGE,
		page: page,
	} );

	try {
		const response = await apiFetch( {
			path: path,
			parse: false,
		} );

		return {
			total: Number( response.headers?.get( 'X-WP-Total' ) || 0 ),
			totalPages: Number( response.headers?.get( 'X-WP-TotalPages' ) || 0 ),
			results: await response.json(),
		};
	} catch ( response ) {
		const error = await response.json();
		throw error;
	}
}

/**
 * Get the collapsed list of page numbers for a given range of pages, used to paginate queries.
 *
 * This will return an array of page numbers (1, 2, 3, etc) for a given length (number of pages). If there are
 * less than 5 pages (inclusive), it will return 1 through 5. If there are more, it will collapse between the
 * start and end with an ellipsis. If the current page is in the middle, it will add pages to the middle.
 *
 * See test/get-pagination-list.js for examples.
 *
 * @param {number}  length  The total number of pages.
 * @param {?number} current The current page, used to output extra pages if necessary. Default 1.
 * @return {Array.<number|string>} Array of numbers and … used to display pagination links.
 */
export function getPaginationList( length, current = 1 ) {
	const range = Array.from( { length }, ( val, i ) => i + 1 );
	const list = [];
	if ( length <= 5 ) {
		return range;
	}
	list.push( ...range.slice( 0, 2 ) );
	if ( current >= 2 && current <= length - 1 ) {
		list.push( ...range.slice( current - 2, current + 1 ) );
	}
	list.push( ...range.slice( -2 ) );

	return (
		list
			// Remove duplicates.
			.filter( ( value, i, a ) => a.indexOf( value ) === i )
			// Add in … where there's a jump larger than 1.
			.reduce( ( acc, value, i, a ) => {
				if ( i === 0 ) {
					acc.push( value );
					return acc;
				}
				const diff = Math.abs( a[ i ] - a[ i - 1 ] );
				if ( diff === 0 ) {
					return acc;
				}
				if ( diff > 1 ) {
					acc.push( '…' );
				}
				acc.push( value );
				return acc;
			}, [] )
	);
}
