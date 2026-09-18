/**
 * WordPress dependencies
 */
import { getContext, getElement, store, withScope } from '@wordpress/interactivity';

const CONTROLLED_HEIGHT = 600;

// The frame is untrusted, so the height it claims is capped well above any real pattern.
const MAX_CONTENT_HEIGHT = 5000;

const { actions, state } = store( 'wporg/patterns/preview', {
	state: {
		get scale() {
			const { pageWidth, previewWidth } = getContext();
			const scale = parseInt( pageWidth, 10 ) / previewWidth;
			return scale > 1 ? 1 : scale;
		},
		get previewHeightCSS() {
			const { contentHeight, isControlled } = getContext();
			return `${ isControlled ? CONTROLLED_HEIGHT : contentHeight * state.scale }px`;
		},
		get iframeWidthCSS() {
			return `${ getContext().previewWidth }px`;
		},
		get iframeHeightCSS() {
			const { contentHeight, isControlled } = getContext();
			return `${ isControlled ? CONTROLLED_HEIGHT / state.scale : contentHeight }px`;
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
		dragPos: 0,
		isDrag: false,
		throttleTimeout: 0,
		prevX: 0,
		direction: '',
	},
	actions: {
		updatePreviewWidth( newWidth ) {
			const context = getContext();
			if ( newWidth > 320 && newWidth < 1400 ) {
				context.previewWidth = newWidth;
			}
		},
		onWidthChange() {
			const { ref } = getElement();
			const context = getContext();
			context.previewWidth = parseInt( ref.dataset.width, 10 );
			setTimeout(
				withScope( () => actions.handleOnResize() ),
				0
			);
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
			state.dragPos = getContext().previewWidth;
		},
		onDrag( event ) {
			if ( ! state.isDrag ) {
				return;
			}

			const delta = event.x - state.prevX;
			if ( ( delta < 0 && 'left' === state.direction ) || ( delta > 0 && 'right' === state.direction ) ) {
				state.dragPos += 2 * Math.abs( delta );
				actions.updatePreviewWidth( state.dragPos );
			} else {
				state.dragPos -= 2 * Math.abs( delta );
				actions.updatePreviewWidth( state.dragPos );
			}
			actions.handleOnResize();

			state.prevX = event.x;
		},
		onDragEnd() {
			state.throttleTimeout = 0;
			state.isDrag = false;
			state.direction = '';
		},
		requestPreviewHeight() {
			const { ref } = getElement();
			const request = () =>
				ref.contentWindow?.postMessage( { type: 'wporg/patterns/preview-height-request' }, '*' );

			// Covers a frame that loaded before this ran, and one that has not loaded yet.
			request();
			ref.addEventListener( 'load', request );
		},
		onPreviewHeight( event ) {
			const { ref } = getElement();

			// Opaque origins are all "null", so only the frame this handler is bound to may set this height.
			if ( event.source !== ref.contentWindow || 'wporg/patterns/preview-height' !== event.data?.type ) {
				return;
			}

			const height = parseInt( event.data.height, 10 );
			if ( height > 0 ) {
				getContext().contentHeight = Math.min( height, MAX_CONTENT_HEIGHT );
			}
		},
		handleOnResize() {
			const context = getContext();
			const { ref } = getElement();

			// Back up to the block container, so that this works regardless
			// of which element interaction triggered it.
			const container =
				ref.closest( '.wp-block-wporg-pattern-view-control' ) ||
				ref.closest( '.wp-block-wporg-pattern-preview' );
			if ( container ) {
				const preview = container.querySelector( '.wp-block-wporg-pattern-preview__container' );
				context.pageWidth = preview?.clientWidth;
			}
		},
	},
} );
