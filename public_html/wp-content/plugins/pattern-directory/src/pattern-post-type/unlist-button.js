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
import './unlist-button.scss';

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

	const className = status === UNLISTED_STATUS ? 'wporg-patterns-unlist-notice' : 'wporg-patterns-unlist-button';

	return (
		<PluginPostStatusInfo className={ className }>
			{ status !== UNLISTED_STATUS && (
				<>
					<Button onClick={ onClick } isSecondary>
						{ __( 'Unlist', 'wporg-patterns' ) }
					</Button>
					<small>{ __( 'Remove from the pattern directory', 'wporg-patterns' ) }</small>
				</>
			) }
			{ status === UNLISTED_STATUS && (
				<>
					<h3>{ __( 'Unlisted', 'wporg-patterns' ) }</h3>
					<small>
						{ __(
							'Use the Publish button to re-list this pattern. Note: This overrides the status settings shown above.',
							'wporg-patterns'
						) }
					</small>
				</>
			) }
		</PluginPostStatusInfo>
	);
};

export default compose(
	withSelect( ( select ) => {
		const { getCurrentPostAttribute } = select( 'core/editor' );
		return {
			status: getCurrentPostAttribute( 'status' ),
		};
	} )
)( UnlistButton );
