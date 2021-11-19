/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

const noop = () => {};

export const getUnlistedReasons = ( { onSuccess = noop, onFailure = noop } ) => {
	apiFetch( {
		path: '/wp/v2/wporg-pattern-flag-reason',
	} )
		.then( ( data ) => {
			const reasonList = data
				.sort( ( a, b ) => {
					// Using the slug allows us to set a custom order for the terms through the admin UI.
					switch ( true ) {
						case a.slug < b.slug:
							return -1;
						case a.slug > b.slug:
							return 1;
						default:
							return 0;
					}
				} )
				.map( ( i ) => ( { label: i.name, value: i.slug } ) );
			onSuccess( reasonList );
		} )
		.catch( onFailure );
};

export const sendUnlistedNote = ( { url, note, onSuccess = noop, onFailure = noop } ) => {
	apiFetch( {
		path: url,
		method: 'POST',
		data: {
			excerpt: note,
		},
	} )
		.then( onSuccess )
		.catch( onFailure );
};
