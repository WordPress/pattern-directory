/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { ComboboxControl, TextControl, TextareaControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

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
	const postMetaData = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {} );
	const postTitle = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'title' ) || '' );

	return (
		<PluginDocumentSettingPanel
			name="wporg-pattern-details"
			title={ __( 'Pattern Details', 'wporg-patterns' ) }
			icon="nothing"
		>
			<TextControl
				key="title"
				label={ __( 'Title', 'wporg-patterns' ) }
				value={ postTitle }
				placeholder={ __( 'Pattern title', 'wporg-patterns' ) }
				onChange={ ( newTitle ) =>
					editPost( {
						title: newTitle,
					} )
				}
			/>
			<TextareaControl
				key="description"
				label={ __( 'Description', 'wporg-patterns' ) }
				value={ postMetaData.wpop_description }
				onChange={ ( newDescription ) =>
					editPost( {
						meta: {
							...postMetaData,
							wpop_description: newDescription,
						},
					} )
				}
				help={ __(
					'The description is used to help users of assistive technology understand the content of your pattern.',
					'wporg-patterns'
				) }
			/>
			<ComboboxControl
				key="locale"
				label={ __( 'Language', 'wporg-patterns' ) }
				options={ localeOptions }
				value={ postMetaData.wpop_locale }
				onChange={ ( newLocale ) =>
					editPost( {
						meta: {
							...postMetaData,
							wpop_locale: newLocale,
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
