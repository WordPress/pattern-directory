/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	ExternalLink,
	FormTokenField,
	PanelBody,
	PanelRow,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
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
	const { description, keywords, link, meta, selectedCategories, status, title } = useSelect( ( select ) => {
		const { getCurrentPost, getEditedPostAttribute } = select( editorStore );
		const _meta = getEditedPostAttribute( 'meta' ) || {};
		const _post = getCurrentPost();
		return {
			description: _meta[ DESCRIPTION_SLUG ],
			keywords: _meta[ KEYWORD_SLUG ].split( ', ' ).filter( ( item ) => item.length ),
			link: _post.link,
			meta: _meta,
			selectedCategories: getEditedPostAttribute( 'pattern-categories' ),
			status: _post.status,
			title: getEditedPostAttribute( 'title' ) || '',
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
			<PanelBody initialOpen>
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
				{ [ 'pending', 'publish' ].includes( status ) && (
					<div className="pattern-sidebar__preview-link">
						<h3 className="pattern-sidebar__preview-link-label">
							{ __( 'View pattern', 'wporg-patterns' ) }
						</h3>
						<ExternalLink href={ link }>{ link }</ExternalLink>
					</div>
				) }
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
						'Keywords are words or short phrases that will help people find your pattern. There is a maximum of 10 keywords.',
						'wporg-patterns'
					) }
				</p>
				<FormTokenField
					value={ keywords || [] }
					maxLength={ 10 }
					onChange={ setKeywords }
					tokenizeOnSpace={ false }
				/>
			</PanelBody>
		</>
	);
}

export default PatternSettings;
