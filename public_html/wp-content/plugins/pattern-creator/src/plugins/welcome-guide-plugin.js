/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { store } from '@wordpress/edit-post';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import WelcomeGuide, { GUIDE_ID } from '../components/welcome-guide';

function WelcomeGuidePlugin() {
	const isCoreActive = useSelect( ( select ) => select( store ).isFeatureActive( 'welcomeGuide' ) );
	const { toggleFeature } = useDispatch( store );
	useEffect( () => {
		if ( isCoreActive ) {
			toggleFeature( 'welcomeGuide' );
		}
		toggleFeature( GUIDE_ID );
	}, [ isCoreActive ] );

	return <WelcomeGuide />;
}

registerPlugin( 'welcome-guide-plugin', {
	render: () => {
		return <WelcomeGuidePlugin />;
	},
} );
