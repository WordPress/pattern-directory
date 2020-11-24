import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/edit-post';

registerPlugin( 'plugin-sidebar-test', {
	render: () => (
		<PluginSidebar name="wporg-pattern-preview" title="Preview">
			<p>Plugin Sidebar</p>
		</PluginSidebar>
	),
} );
