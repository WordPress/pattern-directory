/**
 * WordPress dependencies
 */
import { getContext, getElement, store } from '@wordpress/interactivity';

const { state } = store( 'wporg/patterns/thumbnail', {
	state: {
		get scale() {
			const { pageWidth, previewWidth } = getContext();
			const scale = parseInt( pageWidth, 10 ) / previewWidth;
			return scale > 1 ? 1 : scale;
		},
		get previewHeightCSS() {
			return `${ getContext().previewHeight }px`;
		},
		get iframeWidthCSS() {
			return `${ getContext().previewWidth }px`;
		},
		get iframeHeightCSS() {
			return `${ getContext().previewHeight / state.scale }px`;
		},
		get transformCSS() {
			return `scale(${ state.scale })`;
		},
	},
	actions: {
		updatePreviewHeight: () => {
			const context = getContext();
			const { ref } = getElement();

			const iframeDoc = ref.contentWindow.document;
			const height = iframeDoc.querySelector( '.entry-content' )?.clientHeight;
			if ( height ) {
				context.previewHeight = height * state.scale;
			}
		},
		handleOnResize: () => {
			const context = getContext();
			const { ref } = getElement();
			context.pageWidth = ref.clientWidth;
		},
	},
} );
