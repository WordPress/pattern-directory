/**
 * WordPress Dependencies
 */
import { __ } from '@wordpress/i18n';
import { dispatch, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';

/**
 * Internal Dependencies
 */
import { UNLISTED_STATUS } from './settings';

const UnlistNotice = ( { status } ) => {
	const NOTICE_ID = 'unlisted-pattern-notice';

	if ( status === UNLISTED_STATUS ) {
		dispatch( 'core/notices' ).createNotice(
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
		dispatch( 'core/notices' ).removeNotice( NOTICE_ID );
	}

	return null;
};

export default compose(
	withSelect( ( select ) => {
		const { getCurrentPostAttribute } = select( 'core/editor' );
		return {
			status: getCurrentPostAttribute( 'status' ),
		};
	} )
)( UnlistNotice );
