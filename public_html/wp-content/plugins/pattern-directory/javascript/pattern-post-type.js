/*
 * There's no need for webpack etc, since we can assume wp-admin users will have modern browsers.
 *
 * JSX is the only thing that'd be nice to have, but it's not worth the tooling costs for just a few fields.
 * See https://reactjs.org/docs/react-without-jsx.html.
 */

const PatternPostType = () => {
	const { useSelect, useDispatch } = wp.data;
	const { createElement, Fragment, useState, useEffect } = wp.element;
	const { TextControl, TextareaControl } = wp.components;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const { editPost } = useDispatch( 'core/editor' );

	const postMetaData = useSelect( select => select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {} );
	const [ description, setDescription ] = useState( postMetaData.wpop_description );
	const [ viewportWidth, setViewportWidth ] = useState( postMetaData.wpop_viewport_width );

	useEffect(
		() => {
			editPost( {
				meta: {
					...postMetaData,
					wpop_description: description,
					wpop_viewport_width: viewportWidth
				},
			} );
		},
		[ description, viewportWidth ]
	);

	const descriptionInput = createElement(
		TextareaControl,
		{
			key: 'description',
			label: 'Description',
			value: description,
			onChange: setDescription
		}
	);

	const viewportWidthInput = createElement(
		TextControl,
		{
			key: 'viewportWidth',
			label: 'Viewport Width',
			value: viewportWidth,
			onChange: setViewportWidth,
			type: 'number' // Replace this with a `NumberControl` once it's stable.
		}
	);

	const container = createElement(
		Fragment,
		{},
		[ descriptionInput, viewportWidthInput ]
	);

	return createElement(
		PluginDocumentSettingPanel,
		{
			name: 'wporg-pattern-details',
			title: 'Pattern Details'
		},
		container
	);
};

wp.plugins.registerPlugin( 'pattern-post-type', {
	render: PatternPostType,
	icon: null,
} );
