/**
 * WordPress dependencies
 */
import { dispatch, select, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import PublishModal from '../components/publish-modal';

window.gutenbergSavePost = dispatch( editorStore ).savePost;

const SavePostModifier = () => {
	const [ showModal, setShowModal ] = useState( false );
	const [ isSubmitted, setIsSubmitted ] = useState( false );
	const hasSaveError = useSelect( ( localSelect ) => localSelect( editorStore ).didPostSaveRequestFail() );

	useEffect( () => {
		// We don't want the publish sidebar confirmation.
		dispatch( editorStore ).disablePublishSidebar();

		/**
		 * We currently don't have a hook to intercept Gutenberg save functionality
		 * so we override the `savePost` function.
		 *
		 * This function is still called by gutenberg for autosaving so we need to handle that case as well.
		 *
		 * @param {Object} options
		 * @param {boolean} options.isAutosave Whether autosave is calling this function.
		 */
		dispatch( editorStore ).savePost = ( options ) => {
			if ( options && options.isAutosave ) {
				return;
			}

			// We need to wait until now to get the status otherwise we will be working with a previous context
			const postStatus = select( editorStore ).getEditedPostAttribute( 'status' );
			setIsSubmitted( false );

			// The post is set to 'publish' before 'savePost' is called, so we need to update the status
			// to pending before continuing.
			if ( 'publish' === postStatus ) {
				dispatch( editorStore ).editPost( { status: 'pending' } );
			}

			if ( [ 'auto-draft', 'draft' ].includes( postStatus ) ) {
				window.gutenbergSavePost( options );
			} else {
				setShowModal( true );
			}

			return false;
		};
	}, [] );

	useEffect( () => {
		// If there are any save request failures, we hide the modal so the user can resolve
		if ( hasSaveError ) {
			setShowModal( false );
		}
	}, [ hasSaveError ] );

	if ( showModal ) {
		return (
			<PublishModal
				isSubmitted={ isSubmitted }
				onSubmit={ () => {
					window.gutenbergSavePost();
					setIsSubmitted( true );
				} }
				onClose={ () => setShowModal( false ) }
			/>
		);
	}

	return null;
};

registerPlugin( 'save-post-modifier', {
	render: () => <SavePostModifier />,
} );
