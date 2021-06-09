/**
 * Internal dependencies
 */
import PatternDetails from './details';
import PatternStatus from './status';

const PluginWrapper = () => {
	return (
		<>
			<PatternDetails />
			<PatternStatus />
		</>
	);
};

export default PluginWrapper;
