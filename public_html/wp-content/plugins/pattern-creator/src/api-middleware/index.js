/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import filterEndpoints from './filter-endpoints';

// Set up API middleware.
apiFetch.use( filterEndpoints );
