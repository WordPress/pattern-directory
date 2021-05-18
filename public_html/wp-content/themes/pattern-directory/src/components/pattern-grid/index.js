/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Pagination from './pagination';
import PatternThumbnail from '../pattern-thumbnail';
import { store as patternStore } from '../../store';

function PatternGrid( { query } ) {
	const { posts, isLoading } = useSelect( ( select ) => {
		const { getPatternsByQuery, isLoadingPatternsByQuery } = select( patternStore );

		return {
			posts: query ? getPatternsByQuery( query ) : [],
			isLoading: query && isLoadingPatternsByQuery( query ),
		};
	} );

	return (
		<>
			<div className="pattern-grid">
				{ isLoading ? (
					<Spinner />
				) : (
					posts.map( ( post ) => <PatternThumbnail key={ post.id } pattern={ post } /> )
				) }
			</div>
			<Pagination totalPages={ 10 } currentPage={ 5 } />
		</>
	);
}

export default PatternGrid;
