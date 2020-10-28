/**
 * External dependencies
 */
import { TabPanel } from '@wordpress/components';
import { BlockInspector } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import Settings from './settings';
import './style.css';

export default function Inspector() {
	return (
		<div className="block-pattern-creator__inspector">
			<TabPanel
				className="block-pattern-creator__inspector-tab-panel"
				tabs={ [
					{
						name: 'document',
						title: 'Settings',
					},
					{
						name: 'block',
						title: 'Block',
					},
				] }
			>
				{ ( tab ) => (
					<>
						{ 'block' === tab.name && <BlockInspector /> }
						{ 'document' === tab.name && <Settings /> }
					</>
				) }
			</TabPanel>
		</div>
	);
}
