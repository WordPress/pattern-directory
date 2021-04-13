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
import { useRoute } from '../../hooks';
import { getCategoryFromPathname } from '../../utils';

function PatternGrid() {
	const { path } = useRoute();
	const { posts, isLoading } = useSelect( ( select ) => {
		const { getPatternsByQuery, isLoadingPatternsByQuery, getCategoryBySlug } = select( patternStore );
		const categorySlug = getCategoryFromPathname( path );
		const category = getCategoryBySlug( categorySlug );

		let query = {};

		if ( category ) {
			query = {
				...query,
				'pattern-categories': category.id,
			};
		}

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
