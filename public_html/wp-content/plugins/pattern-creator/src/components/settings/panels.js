/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, TextControl, TextareaControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import usePostMeta from '../../store/hooks/use-post-meta';
import usePostTaxonomy from '../../store/hooks/use-post-taxonomy';
import { MODULE_KEY } from '../../store/utils';

// Map the terms from WordPress into options supported by SelectControl.
const termList = Object.entries( wporgBlockPattern.categories ).map( ( [ value, label ] ) => ( {
	value: Number( value ),
	label: label,
} ) );

export default function SettingPanels( {} ) {
	const [ description, setDescription ] = usePostMeta( 'wpop_description', '' );
	const [ viewportWidth, setViewportWidth ] = usePostMeta( 'wpop_viewport_width', '' );
	// Double-destructured because the terms default to an array, but we will only have one category.
	// Note: This slug should be the "REST base", not the taxonomy slug.
	const [ [ term ], setTerms ] = usePostTaxonomy( 'pattern-categories' );
	// Get the post as is currently saved (not edited).
	const post = useSelect( ( select ) => {
		const { getEditingBlockPatternId, getEditedBlockPattern } = select( MODULE_KEY );
		const patternId = getEditingBlockPatternId();
		return getEditedBlockPattern( patternId );
	} );
	const { editBlockPattern } = useDispatch( MODULE_KEY );

	return (
		<>
			<PanelBody title={ __( 'Details', 'wporg-patterns' ) } initialOpen>
				<TextControl
					label={ __( 'Pattern Name', 'wporg-patterns' ) }
					value={ post.title || '' }
					onChange={ ( title ) => editBlockPattern( { title } ) }
				/>
				<TextareaControl
					label={ __( 'Description', 'wporg-patterns' ) }
					help={ __( 'What is this pattern for?', 'wporg-patterns' ) }
					value={ description }
					onChange={ setDescription }
				/>
				<SelectControl
					label={ __( 'Pattern Category', 'wporg-patterns' ) }
					value={ term }
					options={ termList }
					onChange={ setTerms }
				/>
			</PanelBody>
			<PanelBody title={ __( 'Pattern preview', 'wporg-patterns' ) }>
				<SelectControl
					label={ __( 'Preview Width', 'wporg-patterns' ) }
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
