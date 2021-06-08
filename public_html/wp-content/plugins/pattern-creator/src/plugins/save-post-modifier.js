/**
 * WordPress dependencies
 */
import { dispatch, select } from '@wordpress/data';
import { store } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

window.gutenbergSavePost = dispatch( store ).savePost;

const SavePostModifier = () => {
	const [ showModal, setShowModal ] = useState( false );

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

	if ( showModal ) {
		// To Do: This needs to be replaced by the actual UI
		// Return the publish modal
	}

	return null;
};

registerPlugin( 'save-post-modifier', {
	render: () => <SavePostModifier />,
} );
