/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { ComboboxControl, FormTokenField, TextControl, TextareaControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

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
	const { description, keywords, locale, meta, title } = useSelect( ( select ) => {
		const { getEditedPostAttribute } = select( editorStore );
		const _meta = getEditedPostAttribute( 'meta' ) || {};
		return {
			description: _meta[ DESCRIPTION_SLUG ],
			keywords: _meta[ KEYWORD_SLUG ].split( ', ' ).filter( ( item ) => item.length ),
			locale: _meta[ LOCALE_SLUG ],
			meta: _meta,
			title: getEditedPostAttribute( 'title' ) || '',
		};
	} );

	return (
		<PluginDocumentSettingPanel
			name="wporg-pattern-details"
			title={ __( 'Pattern Details', 'wporg-patterns' ) }
			icon="nothing"
		>
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
			<div>
				<p>
					<strong>{ __( 'Keywords', 'wporg-patterns' ) }</strong>
				</p>
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
		</PluginDocumentSettingPanel>
	);
};

export default PatternDetails;
