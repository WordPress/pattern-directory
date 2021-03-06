/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';
import { initializeEditor } from '@wordpress/edit-post';
import { unregisterBlockType } from '@wordpress/blocks';
import '@wordpress/format-library';

/**
 * Internal dependencies
 */
import { filterEndpoints } from './api-middleware';

// The order of these panels matter since Gutenberg currently doesn't have a way to order panels.
import './plugins/category-settings-panel';
import './plugins/keyword-settings-panel';
import './plugins/block-scope-settings-panel';

import './plugins/gutenberg-feature-remover';
import './plugins/main-dashboard-button';
import './plugins/save-post-modifier';
import './plugins/update-inspector-panel-text';
import './plugins/viewport-header-control';
import './plugins/welcome-guide-plugin';
import './style.css';

// Set up API middleware.
apiFetch.use( filterEndpoints );

new Promise( ( resolve ) => {
	domReady( () => {
		resolve(
			initializeEditor(
				'block-pattern-creator',
				'wporg-pattern',
				wporgBlockPattern.postId,
				wporgBlockPattern.settings,
				{}
			)
		);
	} );
} ).then( () => {
	// After the editor is initialized, we can set up any block customizations.
	unregisterBlockType( 'core/shortcode' );
} );
