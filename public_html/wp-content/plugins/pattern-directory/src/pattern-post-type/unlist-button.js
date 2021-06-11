/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { Button } from '@wordpress/components';
import { useDispatch, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';

/**
 * Internal Dependencies
 */
import { UNLISTED_STATUS } from './settings';

const UnlistButton = ( { status } ) => {
	const { editPost, savePost } = useDispatch( 'core/editor' );

	const onClick = () => {
		const confirmMessage = __( 'Are you sure you want to unlist this pattern?', 'wporg-patterns' );

		// eslint-disable-next-line no-alert
		if ( window.confirm( confirmMessage ) ) {
			editPost( {
				status: UNLISTED_STATUS,
			} );
			savePost();
		}
	};

	return (
		<>
			{ status !== UNLISTED_STATUS &&
				<PluginPostStatusInfo>
					<Button
						onClick={ onClick }
						isTertiary
					>
						{ __( 'Unlist', 'wporg-patterns' ) }
					</Button>
				</PluginPostStatusInfo>
			}
		</>
	);
};

export default compose(
	withSelect( ( select ) => {
		const { getCurrentPostAttribute } = select( 'core/editor' );
		return {
			status: getCurrentPostAttribute( 'status' ),
		};
	} ),
)( UnlistButton );
