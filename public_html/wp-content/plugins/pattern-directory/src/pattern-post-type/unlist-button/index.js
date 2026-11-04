/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { PluginPostStatusInfo, store as editorStore } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal Dependencies
 */
import { UNLISTED_STATUS } from '../settings';
import UnlistModal from './modal';
import './unlist.scss';

export const UnlistButton = () => {
	const status = useSelect( ( select ) => {
		const _post = select( editorStore ).getCurrentPost();
		return _post.status;
	} );
	const { didPostSaveRequestFail } = useSelect( editorStore );
	const { editPost, savePost } = useDispatch( editorStore );
	const [ showModal, setShowModal ] = useState( false );

	const onSubmit = async ( reasonId, details = '' ) => {
		editPost( {
			status: UNLISTED_STATUS,
			'wporg-pattern-flag-reason': [ reasonId ],
			meta: { _wporg_unlist_reason_detail: details },
		} );
		// `savePost` resolves even when the request fails, so check the result.
		await savePost();
		if ( didPostSaveRequestFail() ) {
			throw new Error( __( 'The pattern could not be unlisted. Please try again.', 'wporg-patterns' ) );
		}
	};

	const className = status === UNLISTED_STATUS ? 'wporg-patterns-unlist-notice' : 'wporg-patterns-unlist-button';

	return (
		<PluginPostStatusInfo className={ className }>
			{ status !== UNLISTED_STATUS ? (
				<>
					<Button onClick={ () => setShowModal( true ) } isSecondary>
						{ __( 'Unlist', 'wporg-patterns' ) }
					</Button>
					<small>{ __( 'Remove from the pattern directory', 'wporg-patterns' ) }</small>
				</>
			) : (
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
			{ showModal && <UnlistModal onSubmit={ onSubmit } onClose={ () => setShowModal( false ) } /> }
		</PluginPostStatusInfo>
	);
};

export { default as UnlistNotice } from './notice';
