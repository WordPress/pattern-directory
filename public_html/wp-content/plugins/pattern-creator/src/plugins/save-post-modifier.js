/**
 * WordPress dependencies
 */
import { Modal } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { store } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

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

			setShowModal( true );
		};
	}, [] );

	if ( showModal ) {
		// To Do: This needs to be replaced by the actual UI
		return <Modal onRequestClose={ () => setShowModal( false ) }>Replace with modal.</Modal>;
	}

	return null;
};

registerPlugin( 'save-post-modifier', {
	render: () => <SavePostModifier />,
} );
