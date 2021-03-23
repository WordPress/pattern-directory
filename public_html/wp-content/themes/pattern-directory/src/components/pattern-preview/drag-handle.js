/**
 * External dependencies
 */
import { useDrag } from 'react-use-gesture';

function DragHandle( { label, className, onDragChange, direction = 'left' } ) {
	const dragGestures = useDrag( ( { delta, dragging } ) => {
		const multiplier = direction === 'left' ? 2 : -2;
		if ( dragging ) {
			onDragChange( delta[ 0 ] * multiplier );
		}
	} );

	return (
		<div className={ `pattern-preview__drag-handle ${ className }` }>
			<button
				className="pattern-preview__drag-handle-button"
				aria-label={ label }
				{ ...dragGestures() }
			/>
		</div>
	);
}

export default DragHandle;
