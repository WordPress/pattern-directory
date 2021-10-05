/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store as patternStore } from '../../store';

export default function SaveDraftButton() {
	const { isDirty, isSaving, isAutoSaving, isSaveable, isPublished } = useSelect( ( select ) => {
		const { __experimentalGetDirtyEntityRecords } = select( coreStore );
		const { isAutosavingPost, isSavingPost, isCurrentPostPublished, getCurrentPostId } = select( editorStore );
		const { isPatternSaveable } = select( patternStore );

		const dirtyEntityRecords = __experimentalGetDirtyEntityRecords();
		const _isAutoSaving = isAutosavingPost();
		return {
			isDirty: dirtyEntityRecords.length > 0,
			isSaving: isSavingPost() || _isAutoSaving,
			isAutoSaving: _isAutoSaving,
			isSaveable: isPatternSaveable( getCurrentPostId() ),
			isPublished: isCurrentPostPublished(),
		};
	} );
	const { editPost, savePost } = useDispatch( editorStore );

	// Button is disabled when not saveable, when it's already saving, or if the draft post is not dirty.
	// A published post can be switched to draft without any local changes.
	const isDisabled = ! isSaveable || isSaving || ( ! isDirty && ! isPublished );

	const onClick = () => {
		if ( isDisabled ) {
			return;
		}
		if ( isPublished ) {
			editPost( { status: 'draft' }, { undoIgnore: true } );
			savePost();
		} else {
			savePost();
		}
	};

	return (
		<>
			<Button
				variant="tertiary"
				className="pattern-save-button__button"
				aria-disabled={ isDisabled }
				disabled={ isDisabled }
				isBusy={ ! isAutoSaving && isSaving }
				onClick={ isDisabled ? undefined : onClick }
			>
				{ isPublished ? __( 'Switch to draft', 'wporg-patterns' ) : __( 'Save draft', 'wporg-patterns' ) }
			</Button>
		</>
	);
}
