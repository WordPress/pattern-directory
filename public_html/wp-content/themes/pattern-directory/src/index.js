/**
 * WordPress dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PatternPreview from './components/pattern-preview';

const container = document.getElementById( 'wporg-pattern-container' );
if ( container ) {
	const blockContent = JSON.parse( decodeURIComponent( container.innerText ) );
	// Use `wp.blocks.parse` to convert HTML to block objects (for use in editor), if needed.

	render( <PatternPreview blockContent={ blockContent } />, container, () => {
		container.hidden = false;
	} );
}
