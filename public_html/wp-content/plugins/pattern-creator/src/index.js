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
import { filterEndpoints, interceptUploads } from './api-middleware';
import './plugins/main-dashboard-button';
import './plugins/preview-button';
import './style.css';

// Set up API middleware.
apiFetch.use( filterEndpoints );
apiFetch.use( interceptUploads );

new Promise( ( resolve ) => {
	domReady( () => {
		resolve(
			initializeEditor( 'block-pattern-creator', 'wporg-pattern', wporgBlockPattern.postId, wporgBlockPattern.settings, {} )
		);
	} );
} ).then( () => {
	// After the editor is initialized, we can set up any block customizations.
	unregisterBlockType( 'core/shortcode' );
} );
