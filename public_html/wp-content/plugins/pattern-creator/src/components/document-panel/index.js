/**
 * External dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';

/**
 * Internal dependencies
 */
import './style.css';

const DocumentPanel = ( { name, title, summary, children } ) => {
	return (
		<PluginDocumentSettingPanel name={ name } title={ title }>
			<p className="document-panel-summary">{ summary }</p>
			{ children }
		</PluginDocumentSettingPanel>
	);
};

export default DocumentPanel;
