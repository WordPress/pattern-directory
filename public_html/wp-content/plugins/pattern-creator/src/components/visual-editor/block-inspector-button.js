/**
 * External dependencies
 */
import { noop } from 'lodash';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuItem } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { speak } from '@wordpress/a11y';
import { store as editPostStore } from '@wordpress/edit-post';

export function BlockInspectorButton( { onClick = noop, small = false } ) {
	const { areAdvancedSettingsOpened } = useSelect(
		( select ) => ( {
			areAdvancedSettingsOpened: select( editPostStore ).getActiveGeneralSidebarName() === 'edit-post/block',
		} ),
		[]
	);
	const { openGeneralSidebar, closeGeneralSidebar } = useDispatch( editPostStore );

	const speakMessage = () => {
		if ( areAdvancedSettingsOpened ) {
			speak( __( 'Block settings closed', 'wporg-patterns' ) );
		} else {
			speak(
				__(
					'Additional settings are now available in the Editor block settings sidebar',
					'wporg-patterns'
				)
			);
		}
	};

	const label = areAdvancedSettingsOpened
		? __( 'Hide more settings', 'wporg-patterns' )
		: __( 'Show more settings', 'wporg-patterns' );

	return (
		<MenuItem
			onClick={ () => {
				if ( areAdvancedSettingsOpened ) {
					closeGeneralSidebar();
				} else {
					openGeneralSidebar( 'edit-post/block' );
					speakMessage();
					onClick();
				}
			} }
		>
			{ ! small && label }
		</MenuItem>
	);
}

export default BlockInspectorButton;
