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
	const [ description, setDescription ] = useState( postMetaData.wpop_description );
	const [ viewportWidth, setViewportWidth ] = useState( postMetaData.wpop_viewport_width );

	useEffect( () => {
		editPost( {
			meta: {
				...postMetaData,
				wpop_description: description,
				wpop_viewport_width: viewportWidth,
			},
		} );
	}, [ description, viewportWidth ] );

	return createElement(
		PluginDocumentSettingPanel,
		{
			name: 'wporg-pattern-details',
			title: 'Pattern Details',
		},
		<>
			<TextareaControl
				key="description"
				label={ __( 'Description', 'wporg-patterns' ) }
				value={ description }
				onChange={ setDescription }
			/>
			{ /* Replace this with a `NumberControl` once it's stable. */ }
			<TextControl
				key="viewportWidth"
				label={ __( 'Viewport Width', 'wporg-patterns' ) }
				value={ viewportWidth }
				onChange={ setViewportWidth }
				type="number"
			/>
		</>
	);
};

registerPlugin( 'pattern-post-type', {
	render: PatternPostType,
	icon: null,
} );
