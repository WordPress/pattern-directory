/**
 * External dependencies
 */
import { PanelBody, PanelRow, SelectControl, TextControl, TextareaControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import usePostMeta from '../../store/hooks/use-post-meta';

export default function Settings() {
	const title = '';
	const onChange = () => {};
	const [ description, setDescription ] = usePostMeta( 'wpop_description', '' );
	const [ viewportWidth, setViewportWidth ] = usePostMeta( 'wpop_viewport_width', '' );

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
					<TextareaControl label="Description" value={ description } onChange={ setDescription } />
				</PanelRow>
				<PanelRow>
					<SelectControl label="Categories" value={ '' } onChange={ onChange } />
				</PanelRow>
				<PanelRow>
					<SelectControl label="Preview width" value={ viewportWidth } onChange={ setViewportWidth } />
				</PanelRow>
			</PanelBody>
		</>
	);
}
