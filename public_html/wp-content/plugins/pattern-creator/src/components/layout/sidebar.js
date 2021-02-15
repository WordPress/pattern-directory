/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockInspector } from '@wordpress/block-editor';
import { closeSmall } from '@wordpress/icons';
import { Button, TabPanel } from '@wordpress/components';

/**
 * Internal dependencies
 */
import SettingPanels from '../settings/panels';

const tabs = [
	{
		name: 'settings',
		title: __( 'Pattern Settings', 'wporg-patterns' ),
	},
	{
		name: 'blocks',
		title: __( 'Blocks', 'wporg-patterns' ),
	},
];

function Sidebar( { onClose } ) {
	return (
		<>
			<div className="block-pattern-creator__sidebar-close">
				<Button
					icon={ closeSmall }
					onClick={ onClose }
					label={ __( 'Close block settings', 'wporg-patterns' ) }
				/>
			</div>
			<TabPanel className="block-pattern-creator__sidebar-tabs" tabs={ tabs }>
				{ ( selectedTab ) => ( 'blocks' === selectedTab.name ? <BlockInspector /> : <SettingPanels /> ) }
			</TabPanel>
		</>
	);
}

export default Sidebar;
