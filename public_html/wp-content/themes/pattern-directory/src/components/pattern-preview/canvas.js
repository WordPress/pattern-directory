/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { SandBox } from '@wordpress/components';

const HTML_CLASS_NAME = 'pattern-wrapper';

const tt1StyleOverrides = `
body {
	background-color: white !important;
}`;

function Canvas( { html, themeSlug = 'twentytwentyone', width } ) {
	const wrapperRef = useRef();
	const [ frameScale, setFrameScale ] = useState( 1 );

	const handleOnResize = useCallback( () => {
		if ( ! wrapperRef.current ) {
			return;
		}
		if ( wrapperRef.current.clientWidth < width ) {
			const newScale = wrapperRef.current.clientWidth / width;
			setFrameScale( newScale );
		} else {
			setFrameScale( 1 );
		}
	}, [ width ] );

	// Trigger recalculation when preview `width` changes & set up a listener
	// for when the viewport width changes.
	useEffect( () => {
		handleOnResize();

		/* eslint-disable @wordpress/no-global-event-listener -- These are global events. */
		window.addEventListener( 'resize', handleOnResize );
		return () => {
			window.removeEventListener( 'resize', handleOnResize );
		};
		/* eslint-enable @wordpress/no-global-event-listener */
	}, [ width ] );

	const cssProperties = {
		'--wporg-pattern-preview--width': `${ width }px`,
		'--wporg-pattern-preview--scale': frameScale,
	};

	const styleLinkTags =
		window.__editorStyles.html +
		`<link rel="stylesheet" href="https://wp-themes.com/wp-content/themes/${ themeSlug }/style.css" media="all" />`;

	return (
		<div ref={ wrapperRef } className="pattern-preview__canvas" style={ cssProperties }>
			<SandBox
				html={ styleLinkTags + html }
				scale={ frameScale }
				styles={ [ tt1StyleOverrides ] }
				title={ __( 'Pattern preview', 'wporg-patterns' ) }
				type={ HTML_CLASS_NAME }
			/>
		</div>
	);
}

export default Canvas;
