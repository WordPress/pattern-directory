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

export default function SaveButton() {
	const { isDirty, isSaving, isAutoSaving, isSaveable, isPublished, hasPublishAction } = useSelect(
		( select ) => {
			const { __experimentalGetDirtyEntityRecords } = select( coreStore );
			const {
				isAutosavingPost,
				isSavingPost,
				isEditedPostSaveable,
				isEditedPostPublishable,
				isCurrentPostPublished,
				getCurrentPost,
			} = select( editorStore );

			const dirtyEntityRecords = __experimentalGetDirtyEntityRecords();
			const _isAutoSaving = isAutosavingPost();
			return {
				isDirty: dirtyEntityRecords.length > 0,
				isSaving: isSavingPost() || _isAutoSaving,
				isAutoSaving: _isAutoSaving,
				isSaveable: isEditedPostSaveable(),
				isPublishable: isEditedPostPublishable(),
				isPublished: isCurrentPostPublished(),
				hasPublishAction: get( getCurrentPost(), [ '_links', 'wp:action-publish' ], false ),
			};
		}
	);
	const { editPost, savePost } = useDispatch( editorStore );
	const [ showModal, setShowModal ] = useState( false );

	const isDisabled = ! isDirty || isSaving || ! isSaveable;

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
