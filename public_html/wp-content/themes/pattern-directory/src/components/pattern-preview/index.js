/**
 * Internal dependencies
 */
import Canvas from './canvas';

/* eslint-disable jsx-a11y/anchor-is-valid -- These are just placeholders. */

function PatternPreview( { blockContent } ) {
	return (
		<>
			<div>
				<button>Left</button>
				<Canvas html={ blockContent } />
				<button>Right</button>
			</div>
			<div>
				Categories: <a href="#">Ecommerce,</a> <a href="#">Columns,</a> <a href="#">Marketing</a>
			</div>
		</>
	);
}

export default PatternPreview;
