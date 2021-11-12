/**
 * Internal dependencies
 */
import { UnlistButton, UnlistNotice } from './unlist-button';
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
