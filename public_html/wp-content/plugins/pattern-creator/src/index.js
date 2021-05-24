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
import './plugins/hide-inspector-controls';
import './plugins/main-dashboard-button';
import './plugins/update-inspector-panel-text';
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
