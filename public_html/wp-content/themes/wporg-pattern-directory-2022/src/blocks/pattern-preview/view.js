/**
 * WordPress dependencies
 */
import { __, isRTL } from '@wordpress/i18n';
import { createRoot, useCallback, useEffect, useRef, useState } from '@wordpress/element';

const VIEWPORT_WIDTH = 1200;

const getIframeContentHeight = ( iframeDoc ) => {
	if ( ! iframeDoc ) {
		return;
	}
	return iframeDoc.querySelector( '.entry-content' )?.clientHeight;
};

function ResizableCanvas( { url } ) {
	const wrapperRef = useRef();
	const iframeRef = useRef();
	const [ pageWidth, setPageWidth ] = useState( VIEWPORT_WIDTH );
	const [ previewWidth, setPreviewWidth ] = useState( VIEWPORT_WIDTH );
	const [ previewHeight, setPreviewHeight ] = useState( 500 );
	const [ frameScale, setFrameScale ] = useState( 1 );

	const updatePreviewHeight = useCallback( () => {
		const height = getIframeContentHeight( iframeRef.current?.contentWindow.document );
		if ( height ) {
			setPreviewHeight( height * frameScale );
		}
	}, [ frameScale, iframeRef ] );

	useEffect( () => {
		const handleOnResize = () => {
			setPageWidth( wrapperRef.current?.clientWidth );
		};

		handleOnResize();
		window.addEventListener( 'resize', handleOnResize );

		return () => {
			window.removeEventListener( 'resize', handleOnResize );
		};
	}, [ wrapperRef ] );

	useEffect( () => {
		const scale = pageWidth / previewWidth;
		setFrameScale( scale > 1 ? 1 : scale );
	}, [ pageWidth, previewWidth ] );

	useEffect( () => {
		updatePreviewHeight();
	}, [ previewWidth, frameScale ] );

	const onWidthChange = useCallback( ( { target } ) => {
		setPreviewWidth( target.value * 1 );
	} );

	return (
		<>
			<div className="wp-block-wporg-pattern-preview__controls">
				<select onChange={ onWidthChange } value={ previewWidth }>
					<option value={ 1200 }>{ __( 'Full (1200px)', 'wporg-patterns' ) }</option>
					<option value={ 960 }>{ __( 'Default (960px)', 'wporg-patterns' ) }</option>
					<option value={ 600 }>{ __( 'Medium (600px)', 'wporg-patterns' ) }</option>
					<option value={ 480 }>{ __( 'Narrow (480px)', 'wporg-patterns' ) }</option>
				</select>
			</div>
			<div
				className="wp-block-wporg-pattern-preview__frame"
				ref={ wrapperRef }
				style={ {
					overflow: 'hidden',
					height: `${ previewHeight }px`,
				} }
				tabIndex="-1"
			>
				<iframe
					ref={ iframeRef }
					title={ __( 'Pattern Preview', 'wporg-patterns' ) }
					tabIndex="-1"
					style={ {
						border: 'none',
						width: `${ previewWidth }px`,
						maxWidth: 'none',
						height: `${ previewHeight / frameScale }px`,
						transform: `scale(${ frameScale })`,
						transformOrigin: isRTL() ? 'top right' : 'top left',
						pointerEvents: 'none',
					} }
					src={ url }
					onLoad={ updatePreviewHeight }
				/>
			</div>
		</>
	);
}

const init = () => {
	const containers = document.querySelectorAll( '.wp-block-wporg-pattern-preview' );
	if ( containers ) {
		containers.forEach( ( element ) => {
			const root = createRoot( element );
			root.render( <ResizableCanvas url={ element.dataset.url } /> );
		} );
	}
};

window.addEventListener( 'load', init );
