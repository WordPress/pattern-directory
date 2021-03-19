/**
 * WordPress dependencies
 */
import { useCallback, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Canvas from './canvas';
import DragHandle from './drag-handle';

/* eslint-disable jsx-a11y/anchor-is-valid -- These are just placeholders. */

function PatternPreview( { blockContent } ) {
	const [ width, setWidth ] = useState( 800 );
	const onLeftDragChange = useCallback(
		( delta ) => {
			setWidth( ( value ) => value + ( delta * 2 ) ); // prettier-ignore
		},
		[ setWidth ]
	);
	const onRightDragChange = useCallback(
		( delta ) => {
			setWidth( ( value ) => value - ( delta * 2 ) ); // prettier-ignore
		},
		[ setWidth ]
	);
	useEffect( () => {
		if ( width > window.innerWidth ) {
			setWidth( window.innerWidth );
		} else if ( width < 320 ) {
			setWidth( 320 );
		}
	}, [ width ] );

	return (
		<>
			<div className="pattern-preview__viewport" style={ { width } }>
				<DragHandle label="Left" className="is-left" onDragChange={ onLeftDragChange } />
				<Canvas html={ blockContent } />
				<DragHandle label="Right" className="is-right" onDragChange={ onRightDragChange } />
			</div>
			<div className="pattern-preview__meta">
				<div className="pattern-preview__categories">
					Categories: <a href="#">Ecommerce,</a> <a href="#">Columns,</a> <a href="#">Marketing</a>
				</div>
				<div className="pattern-preview__report">
					<button className="button">Report this pattern</button>
				</div>
			</div>
		</>
	);
}

export default PatternPreview;
