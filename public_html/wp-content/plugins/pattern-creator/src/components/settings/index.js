/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, PanelBody, SelectControl, TextControl, TextareaControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import PublishHeader from './publish-header';
import SaveButton from './save-button';
import usePostMeta from '../../store/hooks/use-post-meta';
import usePostTaxonomy from '../../store/hooks/use-post-taxonomy';
import { MODULE_KEY } from '../../store/utils';
import './style.css';

// Map the terms from WordPress into options supported by SelectControl.
const termList = Object.entries( wporgBlockPattern.categories ).map( ( [ value, label ] ) => ( {
	value: Number( value ),
	label: label,
} ) );

export default function Settings( { closeSidebar } ) {
	const [ description, setDescription ] = usePostMeta( 'wpop_description', '' );
	const [ viewportWidth, setViewportWidth ] = usePostMeta( 'wpop_viewport_width', '' );
	// Double-destructured because the terms default to an array, but we will only have one category.
	// Note: This slug should be the "REST base", not the taxonomy slug.
	const [ [ term ], setTerms ] = usePostTaxonomy( 'pattern-categories' );
	// Get the post as is currently saved (not edited).
	const { hasEdits, post } = useSelect( ( select ) => {
		const { getEditingBlockPatternId, getEditedBlockPattern, hasEditsBlockPattern } = select( MODULE_KEY );
		const patternId = getEditingBlockPatternId();
		return {
			hasEdits: hasEditsBlockPattern( patternId ),
			post: getEditedBlockPattern( patternId ),
		};
	} );
	const { editBlockPattern } = useDispatch( MODULE_KEY );
	// @todo Maybe change these statii depending on any custom statuses in the process.
	// See https://github.com/WordPress/pattern-directory/issues/16#issuecomment-725845843
	const isUnpublished = [ 'publish', 'private' ].indexOf( post.status ) === -1;

	return (
		<>
			<div className="block-pattern-creator__settings-header">
				<SaveButton isUnpublished={ isUnpublished } />
				<Button isSecondary onClick={ closeSidebar }>
					{ __( 'Cancel', 'wporg-patterns' ) }
				</Button>
			</div>
			<div className="block-pattern-creator__settings-details">
				<PublishHeader hasEdits={ hasEdits } />
			</div>
			<PanelBody title={ __( 'Details', 'wporg-patterns' ) } initialOpen>
				<TextControl
					label={ __( 'Pattern Name', 'wporg-patterns' ) }
					value={ post.title || '' }
					onChange={ ( title ) => editBlockPattern( { title } ) }
					disabled={ ! isUnpublished }
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
			<PanelBody title={ __( 'Add tags', 'wporg-patterns' ) }>
				<p>{ __( 'If we want to add tags for sorting on wp.org?', 'wporg-patterns' ) }</p>
			</PanelBody>
			<PanelBody title={ __( 'Pattern preview', 'wporg-patterns' ) }>
				<p>{ __( 'Other details we could use for wp.org display…', 'wporg-patterns' ) }</p>
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
