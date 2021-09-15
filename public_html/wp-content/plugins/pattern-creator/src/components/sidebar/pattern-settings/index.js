/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow, TextControl, TextareaControl } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PatternCategoriesControl from '../../pattern-categories-control';

function PatternSettings() {
	const { title, meta, selectedCategories } = useSelect( ( select ) => {
		const { getEditedPostAttribute } = select( editorStore );
		return {
			meta: getEditedPostAttribute( 'meta' ) || {},
			title: getEditedPostAttribute( 'title' ) || '',
			selectedCategories: getEditedPostAttribute( 'pattern-categories' ),
		};
	} );

	const { editPost } = useDispatch( editorStore );
	const setTitle = useCallback( ( value ) => {
		editPost( { title: value } );
	} );
	const setDescription = useCallback( ( value ) => {
		editPost( { meta: { ...meta, wpop_description: value } } );
	} );
	const setCategories = useCallback( ( value ) => {
		editPost( { 'pattern-categories': value } );
	} );

	return (
		<>
			<PanelBody title={ __( 'Title & Description', 'wporg-patterns' ) } initialOpen>
				<PanelRow>
					<TextControl label={ __( 'Title', 'wporg-patterns' ) } value={ title } onChange={ setTitle } />
				</PanelRow>
				<PanelRow>
					<TextareaControl
						label={ __( 'Description', 'wporg-patterns' ) }
						help={ __(
							'The description is used to help users of assistive technology understand the content of your pattern.',
							'wporg-patterns'
						) }
						value={ meta.wpop_description }
						onChange={ setDescription }
					/>
				</PanelRow>
			</PanelBody>
			<PanelBody title={ __( 'Categories', 'wporg-patterns' ) }>
				<p>
					{ __(
						'Patterns are grouped into defined categories to help people browse.',
						'wporg-patterns'
					) }
				</p>
				<PatternCategoriesControl selectedTerms={ selectedCategories } setTerms={ setCategories } />
			</PanelBody>
			<PanelBody title={ __( 'Keywords', 'wporg-patterns' ) } initialOpen={ false }>
				<p>Maybe existing component, free text?</p>
			</PanelBody>
			<PanelBody title={ __( 'Block scope', 'wporg-patterns' ) } initialOpen={ false }>
				<p>Select based on whats in use?</p>
			</PanelBody>
		</>
	);
}

export default PatternSettings;
