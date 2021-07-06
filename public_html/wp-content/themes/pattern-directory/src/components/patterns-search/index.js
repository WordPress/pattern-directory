/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import PatternGrid from '../pattern-grid';
import QueryMonitor from '../query-monitor';
import { RouteProvider } from '../../hooks';
import { store as patternStore } from '../../store';

const PatternsSearch = () => {
	const query = useSelect( ( select ) => select( patternStore ).getCurrentQuery() );

	return (
		<RouteProvider>
			<QueryMonitor />
			<PatternGrid query={ query } />
		</RouteProvider>
	);
};

export default PatternsSearch;
