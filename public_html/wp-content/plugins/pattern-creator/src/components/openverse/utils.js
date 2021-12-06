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
