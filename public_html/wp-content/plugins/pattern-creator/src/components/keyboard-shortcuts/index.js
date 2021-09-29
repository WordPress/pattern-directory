/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import { store as keyboardShortcutsStore, useShortcut } from '@wordpress/keyboard-shortcuts';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as coreStore } from '@wordpress/core-data';
import { store as interfaceStore } from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { store as patternStore } from '../../store';
import { SIDEBAR_BLOCK } from '../sidebar/constants';
import { STORE_NAME } from '../../store/constants';

function KeyboardShortcuts() {
	const isListViewOpen = useSelect( ( select ) => select( patternStore ).isListViewOpened() );
	const isBlockInspectorOpen = useSelect(
		( select ) => select( interfaceStore ).getActiveComplementaryArea( patternStore.name ) === SIDEBAR_BLOCK,
		[]
	);
	const { redo, undo } = useDispatch( coreStore );
	const { setIsListViewOpened } = useDispatch( patternStore );
	const { enableComplementaryArea, disableComplementaryArea } = useDispatch( interfaceStore );

	useShortcut( 'core/edit-site/undo', ( event ) => {
		undo();
		event.preventDefault();
	} );

	useShortcut( 'core/edit-site/redo', ( event ) => {
		redo();
		event.preventDefault();
	} );

	useShortcut( 'core/edit-site/toggle-list-view', () => {
		setIsListViewOpened( ! isListViewOpen );
	} );

	useShortcut( 'core/edit-site/toggle-block-settings-sidebar', ( event ) => {
		// This shortcut has no known clashes, but use preventDefault to prevent any
		// obscure shortcuts from triggering.
		event.preventDefault();

		if ( isBlockInspectorOpen ) {
			disableComplementaryArea( STORE_NAME );
		} else {
			enableComplementaryArea( STORE_NAME, SIDEBAR_BLOCK );
		}
	} );

	return null;
}
function KeyboardShortcutsRegister() {
	// Registering the shortcuts
	const { registerShortcut } = useDispatch( keyboardShortcutsStore );
	useEffect( () => {
		registerShortcut( {
			name: 'core/edit-site/undo',
			category: 'global',
			description: __( 'Undo your last changes.', 'wporg-patterns' ),
			keyCombination: {
				modifier: 'primary',
				character: 'z',
			},
		} );

		registerShortcut( {
			name: 'core/edit-site/redo',
			category: 'global',
			description: __( 'Redo your last undo.', 'wporg-patterns' ),
			keyCombination: {
				modifier: 'primaryShift',
				character: 'z',
			},
		} );

		registerShortcut( {
			name: 'core/edit-site/toggle-list-view',
			category: 'global',
			description: __( 'Open the block list view.', 'wporg-patterns' ),
			keyCombination: {
				modifier: 'access',
				character: 'o',
			},
		} );

		registerShortcut( {
			name: 'core/edit-site/toggle-block-settings-sidebar',
			category: 'global',
			description: __( 'Show or hide the block settings sidebar.', 'wporg-patterns' ),
			keyCombination: {
				modifier: 'primaryShift',
				character: ',',
			},
		} );
	}, [ registerShortcut ] );

	return null;
}

KeyboardShortcuts.Register = KeyboardShortcutsRegister;
export default KeyboardShortcuts;
