/**
 * WordPress dependencies
 */
import { dispatch } from '@wordpress/data';
import { store } from '@wordpress/edit-post';
import { useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

const InspectorControlsModifier = () => {
	useEffect( () => {
		const { removeEditorPanel } = dispatch( store );

		// We don't want the post-status control
		removeEditorPanel( 'post-status' );

		// We don't want permalinks
		removeEditorPanel( 'post-link' );

		// Turn off the custom taxonomy panels and replace with our own
		removeEditorPanel( 'taxonomy-panel-wporg-pattern-category' );
		removeEditorPanel( 'taxonomy-panel-wporg-pattern-keyword' );
	}, [] );

	return null;
};

registerPlugin( 'inspector-controls-modifier', {
	render: () => <InspectorControlsModifier />,
} );
