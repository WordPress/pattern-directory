/**
 * WordPress dependencies
 */
import { dispatch } from '@wordpress/data';
import { store } from '@wordpress/edit-post';
import { useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

const HideInspectorControls = () => {
	useEffect( () => {
		// We don't want the post-status control
		dispatch( store ).removeEditorPanel( 'post-status' );

		// We don't want permalinks
		dispatch( store ).removeEditorPanel( 'post-link' );
	}, [] );

	return null;
};

registerPlugin( 'hide-inspector-controls', {
	render: () => <HideInspectorControls />,
} );
