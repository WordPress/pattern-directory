/**
 * WordPress dependencies
 */
import {
	/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
	__unstableIframe as Iframe,
} from '@wordpress/block-editor';

function Canvas( { html } ) {
	const style = {
		width: '100%',
		height: '50vh',
		minHeight: '200px',
		overflowY: 'auto',
	};
	const iframeBodyStyle =
		'<style>body{display: flex;align-items: center;justify-content: center;min-height: 100vh;}</style>';

	return (
		<Iframe style={ style } headHTML={ window.__editorStyles.html + iframeBodyStyle }>
			<div
				dangerouslySetInnerHTML={ {
					__html: html,
				} }
			/>
		</Iframe>
	);
}

export default Canvas;