/**
 * WordPress dependencies
 */
import { dispatch } from '@wordpress/data';
import { store } from '@wordpress/edit-post';
import { useEffect } from '@wordpress/element';
import { getPlugin, registerPlugin, unregisterPlugin } from '@wordpress/plugins';

/**
 * Module constants
 */
const TOOLS_MORE_MENU_GROUP_PLUGIN_ID = 'edit-post';

const GutenbergEditorModifier = () => {
	const removeDocumentPanelComponents = () => {
		const { removeEditorPanel } = dispatch( store );

		// We don't want the post-status control
		removeEditorPanel( 'post-status' );

		// We don't want permalinks
		removeEditorPanel( 'post-link' );

		// Turn off the custom taxonomy panels and replace with our own
		removeEditorPanel( 'taxonomy-panel-wporg-pattern-category' );
		removeEditorPanel( 'taxonomy-panel-wporg-pattern-keyword' );
	};

	const removeGutenbergPlugins = () => {
		// Turn off the more tools section that appear in the more-menu.
		// See https://github.com/WordPress/gutenberg/blob/trunk/packages/edit-post/src/plugins/index.js
		if ( getPlugin( TOOLS_MORE_MENU_GROUP_PLUGIN_ID ) ) {
			unregisterPlugin( TOOLS_MORE_MENU_GROUP_PLUGIN_ID );
		}
	};

	useEffect( () => {
		removeDocumentPanelComponents();
		removeGutenbergPlugins();
	}, [] );

	return null;
};

registerPlugin( 'gutenberg-editor-modifier', {
	render: () => <GutenbergEditorModifier />,
} );
