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
 * Listens for changes to the path and reconstructs the query object base on the path
 */
const QueryMonitor = () => {
	const { path } = useRoute();

	const query = useSelect(
		( select ) => {
			const { getCategoryBySlug, getCurrentQuery } = select( patternStore );
			const categorySlug = getCategoryFromPathname( path );
			const category = getCategoryBySlug( categorySlug );

			let _query = getCurrentQuery();

			if ( category ) {
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
