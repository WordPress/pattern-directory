/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { TextControl, TextareaControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

const PatternDetails = () => {
	const { editPost } = useDispatch( 'core/editor' );
	const postMetaData = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {} );
	const postTitle = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'title' ) || '' );

	return createElement(
		PluginDocumentSettingPanel,
		{
			name: 'wporg-pattern-details',
		},
		<>
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
		</>
	);
};

export default PatternDetails;
