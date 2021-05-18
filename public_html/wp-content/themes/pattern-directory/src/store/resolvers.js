/**
 * WordPress dependencies
 */
import { addQueryArgs } from '@wordpress/url';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __unstableAwaitPromise, apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import {
	fetchCategories,
	fetchPatternFlagReasons,
	fetchPatterns,
	loadCategories,
	loadFavorites,
	loadPatternFlagReasons,
	loadPatterns,
} from './actions';
import { PER_PAGE, getQueryString } from './utils';

async function parseResponse( response ) {
	try {
		return {
			total: response.headers?.get( 'X-WP-Total' ),
			totalPages: response.headers?.get( 'X-WP-TotalPages' ),
			results: await response.json(),
		};
	} catch ( error ) {
		return {};
	}
}

export function* getPatternsByQuery( query ) {
	const queryString = getQueryString( query );
	try {
		yield fetchPatterns( queryString );
		const response = yield apiFetch( {
			path: addQueryArgs( '/wp/v2/wporg-pattern', { ...query, per_page: PER_PAGE } ),
			parse: false,
		} );
		const { total, totalPages, results } = yield __unstableAwaitPromise( parseResponse( response ) );
		yield loadPatterns( queryString, {
			page: query.page || 1,
			patterns: results,
			total: Number( total ),
			totalPages: Number( totalPages ),
		} );
	} catch ( error ) {}
}

export function* getCategories() {
	try {
		yield fetchCategories();
		const results = yield apiFetch( {
			path: addQueryArgs( '/wp/v2/pattern-categories' ),
		} );
		yield loadCategories( results );
	} catch ( error ) {}
}

export function* getPatternFlagReasons() {
	try {
		yield fetchPatternFlagReasons();

		const results = yield apiFetch( {
			path: addQueryArgs( '/wp/v2/wporg-pattern-flag-reason' ),
		} );
		yield loadPatternFlagReasons( results );
	} catch ( error ) {}
}

export function* getFavorites() {
	try {
		const results = yield apiFetch( {
			path: '/wporg/v1/pattern-favorites',
		} );
		yield loadFavorites( results );
	} catch ( error ) {}
}
