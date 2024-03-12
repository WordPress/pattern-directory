/**
 * WordPress dependencies
 */
import { getContext, getElement, store } from '@wordpress/interactivity';

const { actions, state } = store( 'wporg/patterns/preview', {
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
		get isWidth1200() {
			return 1200 === getContext().previewWidth;
		},
		get isWidth960() {
			return 960 === getContext().previewWidth;
		},
		get isWidth600() {
			return 600 === getContext().previewWidth;
		},
		get isWidth480() {
			return 480 === getContext().previewWidth;
		},
	},
	actions: {
		onWidthChange() {
			const { ref } = getElement();
			const context = getContext();
			context.previewWidth = parseInt( ref.dataset.width, 10 );
		},
		*onLoad() {
			const { ref } = getElement();

			yield new Promise( ( resolve ) => {
				ref.addEventListener( 'load', () => resolve() );
			} );

			// iframe is loaded now, so we should adjust the height.
			actions.updatePreviewHeight();
		},
		updatePreviewHeight() {
			const context = getContext();
			const { ref } = getElement();

			// Need to "use" previewWidth so that `data-wp-watch` will re-run this action when it changes.
			context.previewWidth; // eslint-disable-line no-unused-expressions

			const iframeDoc = ref.contentDocument;
			const height = iframeDoc.querySelector( '.entry-content' )?.clientHeight;
			if ( height ) {
				context.previewHeight = height * state.scale;
			}
		},
		handleOnResize() {
			const context = getContext();
			const { ref } = getElement();
			context.pageWidth = ref.clientWidth;
		},
	},
} );
