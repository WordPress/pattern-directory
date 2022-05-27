/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import filterEndpoints from './filter-endpoints';
import mockSiteData from './mock-site-data';

// Set up API middleware.
apiFetch.use( filterEndpoints );
apiFetch.use( mockSiteData );
