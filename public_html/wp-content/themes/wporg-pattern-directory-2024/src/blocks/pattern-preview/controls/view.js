/**
 * WordPress dependencies
 */
import { getContext, getElement, store } from '@wordpress/interactivity';

/**
 * Module constants
 */
const THROTTLE_DELAY = 10;

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
		get isWidthWide() {
			return getContext().previewWidth >= 1200;
		},
		get isWidthMedium() {
			return getContext().previewWidth >= 800 && getContext().previewWidth < 1200;
		},
		get isWidthNarrow() {
			return getContext().previewWidth < 800;
		},
		isDrag: false,
		throttleTimeout: 0,
		prevX: 0,
		direction: '',
	},
	actions: {
		updatePreviewWidth( newWidth ) {
			const context = getContext();
			if ( newWidth > 320 && newWidth < 2400 ) {
				context.previewWidth = newWidth;
			}
		},
		onWidthChange() {
			const { ref } = getElement();
			const context = getContext();
			context.previewWidth = parseInt( ref.dataset.width, 10 );
		},
		onLeftKeyDown( event ) {
			const context = getContext();
			if ( 'ArrowLeft' === event.code ) {
				actions.updatePreviewWidth( context.previewWidth + 20 );
			} else if ( 'ArrowRight' === event.code ) {
				actions.updatePreviewWidth( context.previewWidth - 20 );
			}
		},
		onRightKeyDown( event ) {
			const context = getContext();
			if ( 'ArrowRight' === event.code ) {
				actions.updatePreviewWidth( context.previewWidth + 20 );
			} else if ( 'ArrowLeft' === event.code ) {
				actions.updatePreviewWidth( context.previewWidth - 20 );
			}
		},
		onDragStart( event ) {
			const { ref } = getElement();
			state.isDrag = true;
			state.prevX = event.x;
			state.direction = ref.dataset.direction;
		},
		onDrag( event ) {
			if ( ! state.isDrag || state.throttleTimeout > Date.now() ) {
				return;
			}

			const context = getContext();
			const delta = event.x - state.prevX;
			if ( ( delta < 0 && 'left' === state.direction ) || ( delta > 0 && 'right' === state.direction ) ) {
				actions.updatePreviewWidth( context.previewWidth + 2 * Math.abs( delta ) );
			} else {
				actions.updatePreviewWidth( context.previewWidth - 2 * Math.abs( delta ) );
			}

			state.prevX = event.x;
			state.throttleTimeout = Date.now() + THROTTLE_DELAY;
		},
		onDragEnd() {
			state.throttleTimeout = 0;
			state.isDrag = false;
			state.direction = '';
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
			context.previewHeight = 600;
		},
		handleOnResize() {
			const context = getContext();
			const { ref } = getElement();
			context.pageWidth = ref.querySelector( 'div' )?.clientWidth;
		},
	},
} );
