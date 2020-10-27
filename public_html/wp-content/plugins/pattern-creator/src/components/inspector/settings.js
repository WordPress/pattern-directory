/**
 * External dependencies
 */
import { PanelBody, PanelRow, SelectControl, TextControl, TextareaControl } from '@wordpress/components';

export default function Settings() {
	const title = '';
	const description = '';
	const categories = '';
	// const keywords = '';
	// const viewportWidth = '';
	const onChange = () => {};

	return (
		<>
			<div className="block-pattern-creator__settings-header">
				<h2>Block Pattern Settings</h2>
				<span>Info about saving a block pattern.</span>
			</div>
			<PanelBody title="My Block Settings" initialOpen={ true }>
				<PanelRow>
					<TextControl label="Pattern Name" value={ title } onChange={ onChange } />
				</PanelRow>
				<PanelRow>
					<TextareaControl label="Description" value={ description } onChange={ onChange } />
				</PanelRow>
				<PanelRow>
					<SelectControl label="Categories" value={ categories } onChange={ onChange } />
				</PanelRow>
			</PanelBody>
		</>
	);
}
