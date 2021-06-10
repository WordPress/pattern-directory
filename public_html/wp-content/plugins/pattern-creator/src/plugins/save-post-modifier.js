/**
 * WordPress dependencies
 */
import { dispatch, select, useSelect } from '@wordpress/data';
import { store } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import PublishModal from '../components/publish-modal';

window.gutenbergSavePost = dispatch( store ).savePost;

const SavePostModifier = () => {
	const [ showModal, setShowModal ] = useState( false );
	const { isPublished, hasSaveRequestFail, isPublishable } = useSelect( ( localSelect ) => {
		return {
			hasSaveRequestFail: localSelect( store ).didPostSaveRequestFail(),
			isPublished: localSelect( store ).isCurrentPostPublished(),
			isPublishable: localSelect( store ).isEditedPostPublishable(),
		};
	} );

	useEffect( () => {
		// We don't want the publish sidebar confirmation
		dispatch( store ).disablePublishSidebar();
		/**
		 * We currently don't have a hook to intercept Gutenberg save functionality
		 * so we override the `savePost` function.
		 *
		 * This function is still called by gutenberg for autosaving so we need to handle that case as well.
		 *
		 * @param {Object} props
		 * @param {boolean} props.isAutosave Whether autosave is calling this function.
		 */
		dispatch( store ).savePost = ( props ) => {
			if ( props && props.isAutosave ) {
				return;
			}

			// We need to wait until now to get the status otherwise we will be working with a previous context
			const postStatus = select( store ).getEditedPostAttribute( 'status' );

			// The post is set to 'publish' before 'savePost' is called,
			// we can rely on that to make sure we only show the window when necessary
			if ( 'publish' === postStatus ) {
				setShowModal( true );
			} else if ( [ 'auto-draft', 'draft' ].includes( postStatus ) ) {
				window.gutenbergSavePost();
			}

			return false;
		};
	}, [] );

	useEffect( () => {
		// If there are any save request failures, we hide the modal so the user can resolve
		if ( hasSaveRequestFail ) {
			setShowModal( false );
		}
	}, [ hasSaveRequestFail ] );

	// There isn't a great way to identify when a post has saved via user action accurately,
	// so we'll first assume the submitted state and flip when there are publishable edits.
	const isSubmitted = ! hasSaveRequestFail && isPublished && ! isPublishable;

	if ( showModal ) {
		return (
			<PublishModal
				isSubmitted={ isSubmitted }
				onSubmit={ () => window.gutenbergSavePost() }
				onClose={ () => setShowModal( false ) }
			/>
		);
	}

	return null;
};

registerPlugin( 'save-post-modifier', {
	render: () => <SavePostModifier />,
} );
