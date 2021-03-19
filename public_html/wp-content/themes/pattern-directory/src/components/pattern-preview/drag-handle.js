/**
 * WordPress dependencies
 */
import { useCallback, useState } from '@wordpress/element';
/* eslint-disable-next-line -- @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
import { __experimentalUseDragging as useDragging } from '@wordpress/compose';

function DragHandle( { label, className, onDragChange } ) {
	const [ position, setPosition ] = useState( null );

	const changePosition = useCallback(
		( event ) => {
			if ( null !== position ) {
				const delta = position - event.clientX;
				onDragChange( delta );
			}

			if ( event.clientX >= 1 && event.clientX <= window.innerWidth ) {
				setPosition( event.clientX );
			}
		},
		[ onDragChange, position, setPosition ]
	);

	const { startDrag } = useDragging( {
		onDragStart: useCallback( ( event ) => setPosition( event.clientX ), [ setPosition ] ),
		onDragMove: changePosition,
		onDragEnd: useCallback( () => setPosition( null ), [ setPosition ] ),
	} );

	return (
		<div className={ `pattern-preview__drag-handle ${ className }` }>
			<button
				className="pattern-preview__drag-handle-button"
				aria-label={ label }
				onMouseDown={ startDrag }
			/>
		</div>
	);
}

export default DragHandle;
