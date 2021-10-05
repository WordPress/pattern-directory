/**
 * WordPress dependencies
 */
import { __, isRTL } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { redo as redoIcon, undo as undoIcon } from '@wordpress/icons';
import { displayShortcut } from '@wordpress/keycodes';
import { store as coreStore } from '@wordpress/core-data';

export default function UndoButton() {
	const hasUndo = useSelect( ( select ) => select( coreStore ).hasUndo() );
	const { undo } = useDispatch( coreStore );
	return (
		<Button
			icon={ ! isRTL() ? undoIcon : redoIcon }
			label={ __( 'Undo', 'wporg-patterns' ) }
			shortcut={ displayShortcut.primary( 'z' ) }
			// If there are no undo levels we don't want to actually disable this
			// button, because it will remove focus for keyboard users.
			// See: https://github.com/WordPress/gutenberg/issues/3486
			aria-disabled={ ! hasUndo }
			onClick={ hasUndo ? undo : undefined }
		/>
	);
}
