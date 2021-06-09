/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import WelcomeGuide from '../components/welcome-guide';

registerPlugin( 'welcome-guide-plugin', {
	render: () => <WelcomeGuide />,
} );
