/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import PatternThumbnail from '../pattern-thumbnail';
import { store as patternStore } from '../../store';

function PatternGrid() {
	const posts = useSelect( ( select ) => {
		const { getPatternsByQuery } = select( patternStore );
		return getPatternsByQuery( {} );
	} );
	// `posts` will be null while the fetch happens.
	const isLoading = ! Array.isArray( posts );

	return (
		<>
			<header
				style={ { background: 'whitesmoke', padding: '24px', textAlign: 'center', margin: '0 0 24px' } }
			>
				Section filters
			</header>

			<div className="pattern-grid">
				{ isLoading ? (
					<Spinner />
				) : (
					posts.map( ( post ) => <PatternThumbnail key={ post.id } pattern={ post } /> )
				) }
			</div>
		</>
	);
}

export default PatternGrid;
