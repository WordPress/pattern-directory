/**
 * WordPress dependencies
 */
import { dispatch } from '@wordpress/data';
import { registerCoreBlocks } from '@wordpress/block-library';
import { createRoot } from '@wordpress/element';
import { removeFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { store as patternStore } from './store';
import './hooks/media';
import Editor from './components/editor';
import './api-middleware';
import './style.scss';

/**
 * Holds the React root for the editor so it can be unmounted and re-created on
 * reboot. `createRoot` is the React 18+ API and works under React 18 and 19.
 *
 * @type {ReturnType<import('@wordpress/element').createRoot>|undefined}
 */
let root;

/**
 * Reinitializes the editor after the user chooses to reboot the editor after
 * an unhandled error occurs, replacing previously mounted editor element using
 * an initial state from prior to the crash.
 *
 * @param {Element} target          DOM node in which editor is rendered.
 * @param {Object}  settings        Editor settings.
 * @param {number}  settings.postId ID of the current post.
 */
export function reinitializeEditor( target, { postId, ...settings } ) {
	/*
	 * Tear down a previously mounted editor before re-mounting (e.g. after a
	 * reboot following an unhandled error). `createRoot()` / `root.unmount()`
	 * replace `render()` / `unmountComponentAtNode()`, which React 19 removed,
	 * and are available in both React 18 and React 19.
	 */
	if ( root ) {
		root.unmount();
		root = undefined;
	}

	// Re-include `postId` so a reboot reinitializes the same post; without it
	// the remounted editor would receive `postId === undefined` and render blank.
	const reboot = reinitializeEditor.bind( null, target, { postId, ...settings } );

	// Update the store synchronously before rendering so that we won't trigger
	// unnecessary re-renders with useEffect.
	dispatch( patternStore ).updateSettings( settings );

	root = createRoot( target );
	root.render( <Editor onError={ reboot } postId={ postId } /> );
}

/**
 * Initializes the pattern editor screen.
 *
 * @param {string} id       ID of the root element to render the screen in.
 * @param {Object} settings Editor settings.
 */
export function initialize( id, settings ) {
	const target = document.getElementById( id );

	registerCoreBlocks();
	reinitializeEditor( target, settings );
}

// The hook for the pattern editor somehow triggers a critical error.
// The pattern editor cannot use the pattern directory in the first place,
// so disables the filter itself.
removeFilter( 'editor.BlockEdit', 'core/editor/with-pattern-override-controls' );
