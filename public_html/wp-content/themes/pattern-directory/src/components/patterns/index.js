/**
 * Internal dependencies
 */
import PatternGrid from '../pattern-grid';
import PatternGridMenu from '../pattern-grid-menu';
import { RouteProvider } from '../../hooks';

const Patterns = () => {
	return (
		<RouteProvider>
			<PatternGridMenu />
			<PatternGrid />
		</RouteProvider>
	);
};

export default Patterns;
