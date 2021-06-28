/**
 * WordPress dependencies
 */
import { dispatch, select } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { store } from '@wordpress/edit-post';

/**
 * Internal dependencies
 */
import WelcomeGuide, { GUIDE_ID } from '../components/welcome-guide';

registerPlugin( 'welcome-guide-plugin', {
	render: () => {
		// Turn off the default welcome guide
		if ( select( store ).isFeatureActive( 'welcomeGuide' ) ) {
			dispatch( store ).toggleFeature( 'welcomeGuide' );
		}

		const features = select( store ).getPreference( 'features' );

		if ( features[ GUIDE_ID ] === undefined ) {
			dispatch( store ).toggleFeature( GUIDE_ID );
		}

		return <WelcomeGuide />;
	},
} );
