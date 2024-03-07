/* global wporgBlockPattern */
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis */
	__experimentalMainDashboardButton as MainDashboardButton,
} from '@wordpress/edit-post';
import { Button } from '@wordpress/components';
import { close } from '@wordpress/icons';

const BackButton = () => {
	return (
		<MainDashboardButton>
			<Button
				className="edit-post-fullscreen-mode-close"
				href={ `${ wporgBlockPattern.siteUrl }/my-patterns/` }
				label={ __( 'View your patterns', 'wporg-patterns' ) }
				showTooltip
			>
				{ close }
			</Button>
		</MainDashboardButton>
	);
};

export default BackButton;
