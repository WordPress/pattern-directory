/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { registerPlugin } from '@wordpress/plugins';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editPostStore } from '@wordpress/edit-post';
import { store as editorStore } from '@wordpress/editor';
import { store as coreStore } from '@wordpress/core-data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import BackButton from './back-button';
import { NAMESPACE } from './settings';
import PatternDetails from './pattern-details';
import { UnlistButton, UnlistNotice } from './unlist-button';
import MediaPlaceholder from './media-placeholder';
import OpenverseGallery from './openverse';

const PluginWrapper = () => {
	const { canModeratePatterns, isDetailsPanelOpen, isFullscreenMode } = useSelect( ( select ) => {
		return {
			// If a user can create posts, they have a role on the site (moderator).
			canModeratePatterns: select( coreStore ).canUser( 'create', 'posts' ),
			isDetailsPanelOpen: select( editorStore ).isEditorPanelOpened( NAMESPACE + '/pattern-details' ),
			isFullscreenMode: select( editPostStore ).isFeatureActive( 'fullscreenMode' ),
		};
	}, [] );
	const { toggleFeature } = useDispatch( editPostStore );
	const { removeEditorPanel, toggleEditorPanelOpened } = useDispatch( editorStore );

	useEffect( () => {
		if ( ! isFullscreenMode ) {
			toggleFeature( 'fullscreenMode' );
		}
		if ( false === canModeratePatterns ) {
			removeEditorPanel( 'post-status' );
		}
		if ( ! isDetailsPanelOpen ) {
			toggleEditorPanelOpened( NAMESPACE + '/pattern-details' );
		}
	}, [
		isFullscreenMode,
		toggleFeature,
		canModeratePatterns,
		removeEditorPanel,
		isDetailsPanelOpen,
		toggleEditorPanelOpened,
	] );

	return (
		<>
			{ canModeratePatterns && <UnlistButton /> }
			<UnlistNotice />
			<PatternDetails />
			<BackButton />
		</>
	);
};

registerPlugin( NAMESPACE, {
	render: PluginWrapper,
} );

addFilter( 'editor.MediaPlaceholder', 'wporg/patterns/components/media-upload', () => MediaPlaceholder, 100 );
addFilter( 'editor.MediaUpload', 'wporg-patterns/openverse-media-upload', () => OpenverseGallery, 100 );
