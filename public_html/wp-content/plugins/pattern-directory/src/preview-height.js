/**
 * Report the rendered height of a pattern preview to the page that frames it.
 *
 * The embedder sandboxes this frame without `allow-same-origin` and can no longer measure it. Target
 * `*`: an opaque origin has none to assert, and the parent authenticates the sending window instead.
 */

const content = document.querySelector( '.entry-content' );

if ( content && window.parent !== window ) {
	const report = () => {
		window.parent.postMessage( { type: 'wporg/patterns/preview-height', height: content.clientHeight }, '*' );
	};

	// Answering a request is what makes this reliable: an unprompted report can land before the embedder listens.
	window.addEventListener( 'message', ( event ) => {
		if ( 'wporg/patterns/preview-height-request' === event.data?.type ) {
			report();
		}
	} );

	window.addEventListener( 'load', report );
	new window.ResizeObserver( report ).observe( content );
}
