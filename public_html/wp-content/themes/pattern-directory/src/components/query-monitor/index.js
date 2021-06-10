/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { getQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { store as patternStore } from '../../store';
import { useRoute } from '../../hooks';
import { getCategoryFromPath, getPageFromPath, getValueFromPath } from '../../utils';

/**
 * Listens for changes to the path and reconstructs the query object based on the path
 */
const QueryMonitor = () => {
	const { path } = useRoute();

	const query = useSelect(
		( select ) => {
			const queryStrings = getQueryArgs( path );
			let _query = queryStrings;

			const categorySlug = getCategoryFromPath( path );
			if ( categorySlug ) {
				const { getCategoryBySlug, hasLoadedCategories } = select( patternStore );
				if ( ! hasLoadedCategories() ) {
					return;
				}

				const category = getCategoryBySlug( categorySlug );
				if ( category && category.id !== -1 ) {
					_query = {
						..._query,
						'pattern-categories': category.id,
					};
				}
			}

			const page = getPageFromPath( path );
			if ( page > 1 ) {
				_query.page = page;
			}

			const myPatternStatus = getValueFromPath( path, 'my-patterns' );
			if ( myPatternStatus && 'page' !== myPatternStatus ) {
				_query.status = myPatternStatus;
			}

			return _query;
		},
		[ path ]
	);

	const { setCurrentQuery } = useDispatch( patternStore );

	useEffect( () => {
		setCurrentQuery( query );
	}, [ query ] );

	return null;
};

export default QueryMonitor;
