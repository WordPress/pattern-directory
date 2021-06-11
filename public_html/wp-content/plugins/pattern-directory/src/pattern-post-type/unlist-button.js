/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';

const UnlistButton = () => {
	const { editPost, savePost } = useDispatch( 'core/editor' );

	const onClick = () => {
		const confirmMessage = __( 'Are you sure you want to unlist this pattern?', 'wporg-patterns' );

		// eslint-disable-next-line no-alert
		if ( window.confirm( confirmMessage ) ) {
			editPost( {
				status: 'removed',
			} );
			savePost();
		}
	};

	return (
		<PluginPostStatusInfo>
			<Button
				onClick={ onClick }
				isTertiary
			>
				{ __( 'Unlist', 'wporg-patterns' ) }
			</Button>
		</PluginPostStatusInfo>
	);
};

export default UnlistButton;
