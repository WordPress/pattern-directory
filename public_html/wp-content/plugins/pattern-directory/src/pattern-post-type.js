/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, useEffect, useState } from '@wordpress/element';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { registerPlugin } from '@wordpress/plugins';
import { TextControl, TextareaControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

const PatternPostType = () => {
	const { editPost } = useDispatch( 'core/editor' );
	const postMetaData = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {} );
	const postTitle = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'title' ) || '' );
	const [ description, setDescription ] = useState( postMetaData.wpop_description );

	useEffect( () => {
		editPost( {
			meta: {
				...postMetaData,
				wpop_description: description,
			},
		} );
	}, [ description ] );

	return createElement(
		PluginDocumentSettingPanel,
		{
			name: 'wporg-pattern-details',
			title: 'Pattern Details',
		},
		<>
			<TextControl
				key="title"
				label={ __( 'Title', 'wporg-patterns' ) }
				value={ postTitle }
				placeholder={ __( 'Pattern title', 'wporg-patterns' ) }
				onChange={ ( newTitle ) => editPost( {
					title: newTitle,
				} ) }
			/>
			<TextareaControl
				key="description"
				label={ __( 'Description', 'wporg-patterns' ) }
				value={ description }
				onChange={ setDescription }
				help={
					__( 'The description is used to help users of assistive technology understand the content of your pattern.', 'wporg-patterns' )
				}
			/>
		</>
	);
};

registerPlugin( 'pattern-post-type', {
	render: PatternPostType,
	icon: null,
} );
