/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import PatternGrid from '../pattern-grid';
import Header from '../header';
import PatternGridMenu from '../pattern-grid-menu';
import PatternThumbnail from '../pattern-thumbnail';
import QueryMonitor from '../query-monitor';
import { RouteProvider } from '../../hooks';
import { store as patternStore } from '../../store';

const Patterns = ( { isHome } ) => {
	const query = useSelect( ( select ) => select( patternStore ).getCurrentQuery() );

	return (
		<RouteProvider>
			<QueryMonitor />
			<Header isHome={ Boolean( isHome ) } />
			<PatternGridMenu />
			<PatternGrid query={ query }>
				{ ( post ) => <PatternThumbnail key={ post.id } pattern={ post } showAvatar /> }
			</PatternGrid>
		</RouteProvider>
	);
};

export default Patterns;
