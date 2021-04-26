import { registerPlugin } from '@wordpress/plugins';
/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
import { __experimentalMainDashboardButton as MainDashboardButton } from '@wordpress/edit-post';

registerPlugin( 'main-dashboard-button-test', {
	render: () => <MainDashboardButton>{ '←' }</MainDashboardButton>,
} );
