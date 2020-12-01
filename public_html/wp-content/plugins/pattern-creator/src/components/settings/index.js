/**
 * External dependencies
 */
import { Button, PanelBody, SelectControl, TextControl, TextareaControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import SaveButton from './save-button';
import usePostMeta from '../../store/hooks/use-post-meta';
import usePostTaxonomy from '../../store/hooks/use-post-taxonomy';
import './style.css';

// Map the terms from WordPress into options supported by SelectControl.
const termList = Object.entries( wporgBlockPattern.categories ).map( ( [ value, label ] ) => ( {
	value: Number( value ),
	label: label,
} ) );

export default function Settings( { closeSidebar } ) {
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
				<SaveButton />
				<Button isSecondary onClick={ closeSidebar }>
					Cancel
				</Button>
			</div>
			<div className="block-pattern-creator__settings-details">
				<p>
					<strong>Are you ready to publish?</strong>
				</p>
				<p>Name your pattern and write a short description before submitting.</p>
			</div>
			<PanelBody title="Details" initialOpen>
				<TextControl label="Pattern Name" value={ title } onChange={ onChange } />
				<TextareaControl
					label="Description"
					help="What is this pattern for?"
					value={ description }
					onChange={ setDescription }
				/>
			</PanelBody>
			<PanelBody title="Add tags">
				<SelectControl
					label="Pattern Categories"
					value={ term }
					options={ termList }
					onChange={ setTerms }
				/>
			</PanelBody>
			<PanelBody title="Pattern preview">
				<p>Other details we could use for wp.org display…</p>
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
