/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

import { POST_TYPE } from '../../store';

const BASE_URL = wporgBlockPattern.siteUrl;

// Update page URL when the post's status changes, but only if the new status is not auto-draft.
export default function UrlController( { postId } ) {
	const post = useSelect( ( select ) => select( coreStore ).getEntityRecord( 'postType', POST_TYPE, postId ) );
	useEffect( () => {
		if ( 'auto-draft' !== post.status ) {
			const newUrl = `${ BASE_URL }/pattern/${ postId }/edit/`;
			window.history.replaceState( {}, '', newUrl );
		}
	}, [ post.status ] );

	return null;
}
