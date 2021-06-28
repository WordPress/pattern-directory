/**
 * Internal dependencies
 */
import UnlistButton from './unlist-button';
import UnlistNotice from './unlist-notice';
import PatternDetails from './details';

const PluginWrapper = () => {
	return (
		<>
			<UnlistButton />
			<UnlistNotice />
			<PatternDetails />
		</>
	);
};

export default PluginWrapper;
