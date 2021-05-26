/**
 * WordPress dependencies
 */
import { dispatch, useSelect } from '@wordpress/data';
import { store } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

window.gutenbergSavePost = wp.data.dispatch( store ).savePost;

const SavePostModifier = () => {
	const [ showModal, setShowModal ] = useState( false );
	const postStatus = useSelect( ( select ) => select( store ).getEditedPostAttribute( 'status' ) );

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

			// The post is set to 'publish' before 'savePost' is called,
			// we can rely on that to make sure we only show the window when necessary
			if ( 'publish' === postStatus ) {
				setShowModal( true );
			} else if ( 'auto-draft' === postStatus ) {
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
