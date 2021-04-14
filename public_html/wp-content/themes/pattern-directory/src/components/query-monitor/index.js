/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store as patternStore } from '../../store';
import { useRoute } from '../../hooks';
import { getCategoryFromPathname } from '../../utils';

/**
 * Listens for changes to the path and reconstructs the query object based on the path
 */
const QueryMonitor = () => {
	const { path } = useRoute();

	const query = useSelect(
		( select ) => {
			const { getCategoryBySlug, hasLoadedCategories } = select( patternStore );
			const categorySlug = getCategoryFromPathname( path );
			const category = getCategoryBySlug( categorySlug );

			// We need categories loaded before building the query
			if ( ! hasLoadedCategories() ) {
				return;
			}

			// We rebuild the query on every path update
			let _query = {};

			// If we have a category and it's not the default category
			if ( category && category.id !== -1 ) {
				_query = {
					..._query,
					'pattern-categories': category.id,
				};
			}

			return _query;
		},
		[ path ]
	);

	const { setCurrentView } = useDispatch( patternStore );

	useEffect( () => {
		setCurrentView( query );
	}, [ query ] );

	return null;
};

export default QueryMonitor;
