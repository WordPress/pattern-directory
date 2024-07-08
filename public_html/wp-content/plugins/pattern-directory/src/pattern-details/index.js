/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { ComboboxControl, FormTokenField, TextControl, TextareaControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import PatternCategoriesControl from './pattern-categories-control';
import './index.scss';

const KEYWORD_SLUG = 'wpop_keywords';
const DESCRIPTION_SLUG = 'wpop_description';
const LOCALE_SLUG = 'wpop_locale';

const localeData = window.wporgLocaleData || {};
const localeOptions = [];
for ( const [ key, value ] of Object.entries( localeData ) ) {
	localeOptions.push( {
		value: key,
		label: value,
	} );
}

const PatternDetails = () => {
	const { editPost } = useDispatch( 'core/editor' );
	const { categories, description, keywords, locale, meta, title } = useSelect( ( select ) => {
		const { getEditedPostAttribute } = select( editorStore );
		const _meta = getEditedPostAttribute( 'meta' ) || {};
		return {
			categories: getEditedPostAttribute( 'pattern-categories' ),
			description: _meta[ DESCRIPTION_SLUG ],
			keywords: _meta[ KEYWORD_SLUG ].split( ', ' ).filter( ( item ) => item.length ),
			locale: _meta[ LOCALE_SLUG ],
			meta: _meta,
			title: getEditedPostAttribute( 'title' ) || '',
		};
	} );
	const canModeratePatterns = useSelect( ( select ) => {
		return select( coreStore ).canUser( 'create', 'posts' ) ?? false;
	}, [] );

	return (
		<PluginDocumentSettingPanel
			name="pattern-details"
			title={ false }
			icon={ false }
			className="wporg-pattern-details"
		>
			<h2>{ __( 'Pattern Details', 'wporg-patterns' ) }</h2>
			<TextControl
				key="title"
				label={ __( 'Title', 'wporg-patterns' ) }
				value={ title }
				placeholder={ __( 'Pattern title', 'wporg-patterns' ) }
				onChange={ ( newValue ) =>
					editPost( {
						title: newValue,
					} )
				}
			/>
			<TextareaControl
				key="description"
				label={ __( 'Description', 'wporg-patterns' ) }
				value={ description }
				onChange={ ( newValue ) =>
					editPost( {
						meta: {
							...meta,
							[ DESCRIPTION_SLUG ]: newValue,
						},
					} )
				}
				help={ __(
					'The description is used to help users of assistive technology understand the content of your pattern.',
					'wporg-patterns'
				) }
			/>
			<div className="wporg-pattern-details__panel">
				<h3>{ __( 'Categories', 'wporg-patterns' ) }</h3>
				<p>
					{ __(
						'Patterns are grouped into defined categories to help people browse.',
						'wporg-patterns'
					) }
				</p>
				<PatternCategoriesControl
					selectedTerms={ categories }
					setTerms={ ( newValue ) => {
						editPost( { 'pattern-categories': newValue } );
					} }
				/>
			</div>
			<div className="wporg-pattern-details__panel">
				<h3>{ __( 'Keywords', 'wporg-patterns' ) }</h3>
				<p>
					{ __(
						'Keywords are words or short phrases that will help people find your pattern. There is a maximum of 10 keywords.',
						'wporg-patterns'
					) }
				</p>
				<FormTokenField
					value={ keywords || [] }
					onChange={ ( newValue ) => {
						const keywordsString = newValue.join( ', ' );
						editPost( {
							meta: {
								...meta,
								[ KEYWORD_SLUG ]: keywordsString,
							},
						} );
					} }
					maxLength={ 10 }
					tokenizeOnSpace={ false }
				/>
			</div>
			{ canModeratePatterns && (
				<ComboboxControl
					key="locale"
					label={ __( 'Language', 'wporg-patterns' ) }
					options={ localeOptions }
					value={ locale }
					onChange={ ( newValue ) =>
						editPost( {
							meta: {
								...meta,
								[ LOCALE_SLUG ]: newValue,
							},
						} )
					}
					help={ __(
						'The language field is used to help users find patterns that were created in their preferred language.',
						'wporg-patterns'
					) }
				/>
			) }
		</PluginDocumentSettingPanel>
	);
};

export default PatternDetails;
