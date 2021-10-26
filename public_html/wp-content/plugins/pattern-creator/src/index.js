/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
import { __experimentalFetchLinkSuggestions as fetchLinkSuggestions } from '@wordpress/core-data';
import { registerCoreBlocks } from '@wordpress/block-library';
import { render, unmountComponentAtNode } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './store';
import './hooks/media';
import Editor from './components/editor';
import { filterEndpoints } from './api-middleware';
import './style.scss';

// Set up API middleware.
apiFetch.use( filterEndpoints );

/**
 * Reinitializes the editor after the user chooses to reboot the editor after
 * an unhandled error occurs, replacing previously mounted editor element using
 * an initial state from prior to the crash.
 *
 * @param {Element} target   DOM node in which editor is rendered.
 * @param {?Object} settings Editor settings object.
 */
export function reinitializeEditor( target, settings ) {
	unmountComponentAtNode( target );
	const reboot = reinitializeEditor.bind( null, target, settings );
	render( <Editor initialSettings={ settings } onError={ reboot } />, target );
}

/**
 * Initializes the pattern editor screen.
 *
 * @param {string} id              ID of the root element to render the screen in.
 * @param {Object} settings        Editor settings.
 * @param {number} settings.postId ID of the current post.
 */
export function initialize( id, { postId, ...settings } ) {
	settings.__experimentalFetchLinkSuggestions = ( search, searchOptions ) =>
		fetchLinkSuggestions( search, searchOptions, settings );

	const target = document.getElementById( id );
	const reboot = reinitializeEditor.bind( null, target, settings );

	registerCoreBlocks();

	render( <Editor initialSettings={ settings } onError={ reboot } postId={ postId } />, target );
}
