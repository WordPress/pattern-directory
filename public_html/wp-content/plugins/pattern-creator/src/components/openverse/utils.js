/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

// Base URL, with CC0 license pre-set.
const API_URL = 'https://api.openverse.engineering/v1/images?format=json&license=cc0';
const PER_PAGE = 24;

export function fetchImages( searchTerm, { onSuccess, onError } ) {
	/* eslint-disable-next-line id-length -- q is the API parameter. */
	const url = addQueryArgs( API_URL, { q: searchTerm, page_size: PER_PAGE } );
	window
		.fetch( url, { mode: 'cors' } )
		.then( ( response ) => {
			const invalidJsonError = {
				code: 'invalid_json',
				message: __( 'The response is not a valid JSON response.', 'wporg-patterns' ),
			};

			if ( ! response || ! response.json ) {
				throw invalidJsonError;
			}

			return response.json().catch( () => {
				throw invalidJsonError;
			} );
		} )
		.then( ( data ) => {
			const invalidDataError = {
				code: 'invalid_data',
				message: __( 'The response is malformed.', 'wporg-patterns' ),
			};

			if ( 'undefined' === typeof data.results ) {
				throw invalidDataError;
			}

			if ( 'function' === typeof onSuccess ) {
				onSuccess( data );
			}
		} )
		.catch( () => {
			if ( 'function' === typeof onError ) {
				// @todo do something with the error.
				onError();
			}
		} );
}
