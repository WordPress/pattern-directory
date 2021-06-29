/**
 * WordPress dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Iframe from '../iframe';

const VIEWPORT_WIDTH = 800;
const ASPECT_RATIO = 2 / 3;

/**
 * Returns the height of the preview window.
 *
 * @param {HTMLElement} element Html element
 */

export const getFrameHeight = ( element ) => {
	return element.clientWidth * ASPECT_RATIO;
};

function PatternThumbnail( { className, html } ) {
	const wrapperRef = useRef();
	const [ frameHeight, setFrameHeight ] = useState( '100%' );
	const [ frameScale, setFrameScale ] = useState( 0.3125 );

	useEffect( () => {
		const handleOnResize = () => {
			try {
				setFrameHeight( getFrameHeight( wrapperRef.current ) );
				setFrameScale( wrapperRef.current.clientWidth / VIEWPORT_WIDTH );
			} catch ( err ) {}
		};

		handleOnResize();

		// eslint-disable-next-line @wordpress/no-global-event-listener -- This is a global event.
		window.addEventListener( 'resize', handleOnResize );

		return () => {
			window.addEventListener( 'resize', handleOnResize ); // eslint-disable-line @wordpress/no-global-event-listener -- See above.
		};
	}, [] );

	const style = {
		border: 'none',
		width: `${ VIEWPORT_WIDTH }px`,
		maxWidth: 'none',
		height: `${ VIEWPORT_WIDTH * ASPECT_RATIO }px`,
		transform: `scale(${ frameScale })`,
		transformOrigin: 'top left',
		pointerEvents: 'none',
	};

	return (
		<div
			className={ className }
			ref={ wrapperRef }
			style={ {
				height: frameHeight,
			} }
			tabIndex="-1"
		>
			<Iframe
				className="pattern-grid__preview-iframe"
				style={ style }
				bodyStyle={ 'overflow: hidden;' }
				headHTML={ window.__editorStyles.html }
			>
				<div
					dangerouslySetInnerHTML={ {
						__html: html,
					} }
				/>
			</Iframe>
		</div>
	);
}

export default PatternThumbnail;
