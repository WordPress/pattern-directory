/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';
import { store as noticesStore } from '@wordpress/notices';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal Dependencies
 */
import { UNLISTED_STATUS } from '../settings';

const UnlistNotice = () => {
	const status = useSelect( ( select ) => select( editorStore ).getCurrentPostAttribute( 'status' ), [] );
	const { createNotice, removeNotice } = useDispatch( noticesStore );
	const NOTICE_ID = 'unlisted-pattern-notice';

	useEffect( () => {
		if ( status === UNLISTED_STATUS ) {
			createNotice(
				'warning',
				__(
					'This pattern is unlisted. It will not appear in the public pattern directory.',
					'wporg-patterns'
				),
				{
					id: NOTICE_ID,
					isDismissible: false,
				}
			);
		} else {
			removeNotice( NOTICE_ID );
		}
	}, [ status ] );

	return null;
};

export default UnlistNotice;
