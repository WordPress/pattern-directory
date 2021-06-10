/**
 * Internal dependencies
 */
import PatternDetails from './details';
import PatternStatus from './status';

const PluginWrapper = () => {
	return (
		<>
			<PatternStatus />
			<PatternDetails />
		</>
	);
};

export default PluginWrapper;
