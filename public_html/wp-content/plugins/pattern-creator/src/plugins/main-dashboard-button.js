import { registerPlugin } from '@wordpress/plugins';
import { __experimentalMainDashboardButton as MainDashboardButton } from '@wordpress/interface';

registerPlugin( 'main-dashboard-button-test', {
	render: () => (
		<MainDashboardButton>Block Pattern Directory</MainDashboardButton>
	),
} );
