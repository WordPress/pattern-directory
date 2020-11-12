/**
 * External dependencies
 */
import { PanelBody, SelectControl, TextControl, TextareaControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import usePostMeta from '../../store/hooks/use-post-meta';
import usePostTaxonomy from '../../store/hooks/use-post-taxonomy';

// Map the terms from WordPress into options supported by SelectControl.
const termList = Object.entries( wporgBlockPattern.categories ).map( ( [ value, label ] ) => ( {
	value: Number( value ),
	label: label,
} ) );

export default function Settings() {
	const title = '';
	const onChange = () => {};
	const [ description, setDescription ] = usePostMeta( 'wpop_description', '' );
	const [ viewportWidth, setViewportWidth ] = usePostMeta( 'wpop_viewport_width', '' );
	// Double-destructured because the terms default to an array, but we will only have one category.
	// Note: This slug should be the "REST base", not the taxonomy slug.
	const [ [ term ], setTerms ] = usePostTaxonomy( 'pattern-categories' );

	return (
		<>
			<div className="block-pattern-creator__settings-header">
				<h2>Block Pattern Settings</h2>
				<span>Info about saving a block pattern.</span>
			</div>
			<PanelBody title="My Block Settings" initialOpen={ true }>
				<TextControl label="Pattern Name" value={ title } onChange={ onChange } />
				<TextareaControl label="Description" value={ description } onChange={ setDescription } />
				<SelectControl
					label="Pattern Categories"
					value={ term }
					options={ termList }
					onChange={ setTerms }
				/>
				<SelectControl
					label="Preview Width"
					value={ viewportWidth }
					onChange={ setViewportWidth }
					options={ [
						{ value: 800, label: 'Normal' },
						{ value: 1100, label: 'Wide' },
						{ value: 1400, label: 'Extra Wide' },
					] }
				/>
			</PanelBody>
		</>
	);
}
