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
	const { posts, isLoading } = useSelect( ( select ) => {
		const { getPatternsByQuery, isLoadingPatternsByQuery, getCurrentQuery } = select( patternStore );
		const query = getCurrentQuery();

		return {
			posts: getPatternsByQuery( query ),
			isLoading: isLoadingPatternsByQuery( query ),
		};
	} );

	return (
		<div className="pattern-grid">
			{ isLoading ? (
				<Spinner />
			) : (
				posts.map( ( post ) => <PatternThumbnail key={ post.id } pattern={ post } /> )
			) }
		</div>
	);
}

export default PatternGrid;
