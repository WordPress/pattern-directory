/**
 * WordPress dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

store( 'wporg/patterns/delete-button', {
	actions: {
		*triggerDelete() {
			const { postId, message, redirectUrl } = getContext();
			const approved = yield window.confirm( message ); // eslint-disable-line no-alert
			if ( approved ) {
				try {
					yield wp.apiFetch( {
						path: `/wp/v2/wporg-pattern/${ postId }/`,
						method: 'DELETE',
					} );
				} catch ( error ) {
					return;
				}
				window.location = redirectUrl;
			}
		},
	},
} );
