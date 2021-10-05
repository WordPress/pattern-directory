/**
 * External dependencies
 */
import { get } from 'lodash';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SubmissionModal from '../submission-modal';
import { store as patternStore } from '../../store';

export { default as SaveDraftButton } from './draft';

export function SaveButton() {
	const { isDirty, isSaving, isAutoSaving, isSaveable, isPublished, hasPublishAction } = useSelect(
		( select ) => {
			const { __experimentalGetDirtyEntityRecords } = select( coreStore );
			const {
				isAutosavingPost,
				isSavingPost,
				isCurrentPostPublished,
				getCurrentPost,
				getCurrentPostId,
			} = select( editorStore );
			const { isPatternSaveable } = select( patternStore );

			const dirtyEntityRecords = __experimentalGetDirtyEntityRecords();
			const _isAutoSaving = isAutosavingPost();
			return {
				isDirty: dirtyEntityRecords.length > 0,
				isSaving: isSavingPost() || _isAutoSaving,
				isAutoSaving: _isAutoSaving,
				isSaveable: isPatternSaveable( getCurrentPostId() ),
				isPublished: isCurrentPostPublished(),
				hasPublishAction: get( getCurrentPost(), [ '_links', 'wp:action-publish' ], false ),
			};
		}
	);
	const { editPost, savePost } = useDispatch( editorStore );
	const [ showModal, setShowModal ] = useState( false );

	// Button is disabled when not saveable, when it's already saving, or if the draft post is not dirty.
	// A draft post can be published without any local changes (the modal will catch if there is no content).
	const isDisabled = ! isSaveable || isSaving || ( ! isDirty && isPublished );

	let publishStatus;
	if ( ! hasPublishAction ) {
		publishStatus = 'pending';
	} else {
		publishStatus = 'publish';
	}

	const onClick = () => {
		if ( isDisabled ) {
			return;
		}
		if ( isPublished ) {
			onSuccess();
		} else {
			setShowModal( true );
		}
	};

	const onSuccess = () => {
		editPost( { status: publishStatus }, { undoIgnore: true } );
		savePost();
	};

	return (
		<>
			{ showModal && (
				<SubmissionModal
					isPublished={ isPublished }
					onSubmit={ onSuccess }
					onClose={ () => setShowModal( false ) }
				/>
			) }
			<Button
				variant="primary"
				className="pattern-save-button__button"
				aria-disabled={ isDisabled }
				disabled={ isDisabled }
				isBusy={ ! isAutoSaving && isSaving && isPublished }
				onClick={ isDisabled ? undefined : onClick }
			>
				{ isPublished ? __( 'Update', 'wporg-patterns' ) : __( 'Submit', 'wporg-patterns' ) }
			</Button>
		</>
	);
}
