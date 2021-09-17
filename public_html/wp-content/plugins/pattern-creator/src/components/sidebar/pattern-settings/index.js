/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormTokenField, PanelBody, PanelRow, TextControl, TextareaControl } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PatternCategoriesControl from '../../pattern-categories-control';
import { KEYWORD_SLUG } from '../../../store';

const DESCRIPTION_SLUG = 'wpop_description';

function PatternSettings() {
	const { description, keywords, meta, title, selectedCategories } = useSelect( ( select ) => {
		const { getEditedPostAttribute } = select( editorStore );
		const _meta = getEditedPostAttribute( 'meta' ) || {};
		return {
			meta: _meta,
			description: _meta[ DESCRIPTION_SLUG ],
			keywords: _meta[ KEYWORD_SLUG ].split( ', ' ).filter( ( item ) => item.length ),
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
	const setKeywords = useCallback( ( value ) => {
		const keywordsString = value.join( ', ' );
		editPost( { meta: { ...meta, [ KEYWORD_SLUG ]: keywordsString } } );
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
						value={ description }
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
			<PanelBody title={ __( 'Keywords', 'wporg-patterns' ) }>
				<p>
					{ __(
						'Adding keywords will help people find your pattern when searching and browsing.',
						'wporg-patterns'
					) }
				</p>
				<FormTokenField
					title={ __( 'Keywords', 'wporg-patterns' ) }
					value={ keywords || [] }
					onChange={ setKeywords }
					tokenizeOnSpace={ false }
				/>
			</PanelBody>
			<PanelBody title={ __( 'Block scope', 'wporg-patterns' ) } initialOpen={ false }>
				<p>Coming Soon…</p>
			</PanelBody>
		</>
	);
}

export default PatternSettings;
