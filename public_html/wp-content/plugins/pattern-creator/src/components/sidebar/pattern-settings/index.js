/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow, TextControl, TextareaControl } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

function PatternSettings() {
	const { postTitle, meta } = useSelect( ( select ) => {
		const { getEditedPostAttribute } = select( editorStore );
		return {
			meta: getEditedPostAttribute( 'meta' ) || {},
			postTitle: getEditedPostAttribute( 'title' ) || '',
		};
	} );
	const [ title, setTitle ] = useState( postTitle );
	const [ description, setDescription ] = useState( meta.wpop_description );

	const { editPost } = useDispatch( editorStore );
	useEffect( () => {
		editPost( {
			meta: {
				...meta,
				wpop_description: description,
			},
			title: title,
		} );
	}, [ title, description ] );

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
			<PanelBody title={ __( 'Categories', 'wporg-patterns' ) } initialOpen={ false }>
				<p>Maybe existing component?</p>
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
