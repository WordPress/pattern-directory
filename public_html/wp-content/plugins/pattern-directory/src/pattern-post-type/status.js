/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, useEffect, useState } from '@wordpress/element';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { dispatch, select, useDispatch, useSelect } from '@wordpress/data';
import { PanelRow, SelectControl } from '@wordpress/components';

/**
 * Module Constants
 */
const PANEL_NAME = 'alternate-post-status';
const STORE_KEY = 'core/edit-post';

const statuses = [
	{ value: 'draft', label: 'Draft' },
	{ value: 'pending', label: 'Pending Review' },
	{ value: 'declined', label: 'Declined' },
	{ value: 'publish', label: 'Publish' },
	{ value: 'removed', label: 'Removed' },
];

const StatusSelect = () => {
	const { editPost } = useDispatch( 'core/editor' );
	const postStatus = useSelect( ( statusSelect ) => statusSelect( 'core/editor' ).getEditedPostAttribute( 'status' ) || {} );
	const [ status, setStatus ] = useState( postStatus );

	useEffect( () => {
		editPost( { status } );
	}, [ status ] );

	return (
		<SelectControl
			value={ status }
			onChange={ setStatus }
			label={ 'Pattern Status' }
			hideLabelFromVision={ true }
			options={ statuses }
		/>
	);
};

const PatternStatus = () => {
	useEffect( () => {
		// Hide the default panel;
		dispatch( STORE_KEY ).removeEditorPanel( 'post-status' );

		// Make sure we turn on our panel
		const togglePanelOn = async () => {
			const isToggledOn = await select( STORE_KEY ).isEditorPanelOpened(
				PANEL_NAME
			);

			if ( ! isToggledOn ) {
				await dispatch( STORE_KEY ).toggleEditorPanelOpened(
					PANEL_NAME
				);
			}
		};

		togglePanelOn();
	}, [] );

	return createElement(
		PluginDocumentSettingPanel,
		{
			name: PANEL_NAME,
			title: __( 'Status', 'wporg-patterns' ),
		},
		<>
			<PanelRow>
				<StatusSelect />
			</PanelRow>
		</>
	);
};

export default PatternStatus;
