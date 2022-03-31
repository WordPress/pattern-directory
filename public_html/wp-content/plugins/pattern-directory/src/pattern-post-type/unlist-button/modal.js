/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, Modal, RadioControl, Spinner, TextareaControl } from '@wordpress/components';
import { speak } from '@wordpress/a11y';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useEffect, useReducer, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getUnlistedReasons, sendUnlistedNote } from './utils';

const DEFAULT_STATE = {
	hasError: false,
	isSubmitted: false,
	isSubmitting: false,
	message: null,
	reasonList: [],
};

const reducer = ( state, action ) => {
	const resetState = {
		hasError: false,
		isSubmitted: false,
		isSubmitting: false,
		message: null,
	};

	switch ( action.status ) {
		case 'NOTE_SENT':
			return { ...state, ...resetState, isSubmitting: true };
		case 'NOTE_RECIEVED':
			return { ...state, ...resetState, isSubmitted: true };
		case 'REASONS_RECIEVED':
			return { ...state, ...resetState, reasonList: action.reasonList };
		case 'ERROR':
			return { ...state, ...resetState, hasError: true, message: action.message };
		default:
			return state;
	}
};

const UnlistModal = ( { onClose, onSubmit } ) => {
	const apiUrl = useSelect( ( select ) => {
		const postId = select( editorStore ).getCurrentPostId();
		const postType = select( editorStore ).getCurrentPostType();
		const { rest_base: restBase } = select( coreStore ).getPostType( postType );
		return `/wporg/v1/${ restBase }/${ postId }/internal-notes`;
	} );
	const [ state, dispatch ] = useReducer( reducer, DEFAULT_STATE );
	const [ selectedOption, setOption ] = useState( '' );
	const [ details, setDetails ] = useState( '' );
	const container = useRef();

	useEffect( () => {
		getUnlistedReasons( {
			onSuccess: ( reasonList = [] ) => {
				dispatch( {
					status: 'REASONS_RECIEVED',
					reasonList: reasonList,
				} );
			},
			onFailure: ( err ) => {
				dispatch( {
					status: 'ERROR',
					message: err.message,
				} );
			},
		} );
	}, [] );

	const submittedText = __(
		'The pattern has been unlisted, and your internal note has been saved.',
		'wporg-patterns'
	);

	const handleSubmit = ( event ) => {
		event.preventDefault();

		if ( state.isSubmitted || state.isSubmitting ) {
			return;
		}

		if ( ! selectedOption || ! details.length ) {
			dispatch( {
				status: 'ERROR',
				message: __( 'Please select a reason and add an internal note.', 'wporg-patterns' ),
			} );
			return;
		}
		const reason = state.reasonList.find( ( { value } ) => value === selectedOption );

		dispatch( { status: 'NOTE_SENT' } );

		sendUnlistedNote( {
			url: apiUrl,
			note: `UNLISTED: ${ reason.label } — ${ details }`,
			onSuccess: () => {
				if ( 'function' === typeof onSubmit ) {
					onSubmit( selectedOption );
				}
				dispatch( { status: 'NOTE_RECIEVED' } );
				speak( submittedText );
				container.current.closest( '[role="dialog"]' ).focus();
			},
			onFailure: ( err ) => {
				dispatch( {
					status: 'ERROR',
					message: err.message,
				} );

				speak(
					sprintf(
						/* translators: %s: Error message. */
						__( 'Error: %s', 'wporg-patterns' ),
						err.message
					)
				);
			},
		} );
	};
	const handleClose = () => {
		onClose();
	};

	return (
		<Modal
			title={ __( 'Unlist this pattern', 'wporg-patterns' ) }
			onRequestClose={ handleClose }
			className="wporg-patterns-unlist__modal"
		>
			<div ref={ container }>
				{ state.isSubmitted ? (
					<p>{ submittedText }</p>
				) : (
					<form onSubmit={ handleSubmit }>
						{ state.reasonList.length ? (
							<RadioControl
								className="wporg-patterns-unlist__reasons"
								label={ __( 'Please choose a reason:', 'wporg-patterns' ) }
								help={ __(
									'The reason chosen will be used to show a message to the pattern author.',
									'wporg-patterns'
								) }
								selected={ selectedOption }
								options={ state.reasonList }
								onChange={ setOption }
								required={ true }
							/>
						) : (
							<Spinner />
						) }
						<TextareaControl
							label={ __( 'Please provide internal details (required)', 'wporg-patterns' ) }
							help={ __(
								'This note will only be seen by other admins and moderators.',
								'wporg-patterns'
							) }
							value={ details }
							onChange={ setDetails }
							required={ true }
						/>

						{ state.hasError && (
							<div className="notice notice-large notice-alt notice-error">{ state.message }</div>
						) }
						<div className="wporg-patterns-unlist__actions">
							<Button isSecondary onClick={ handleClose }>
								{ __( 'Cancel', 'wporg-patterns' ) }
							</Button>
							<Button type="submit" isBusy={ state.isSubmitting } isPrimary>
								{ state.isSubmitting
									? __( 'Submitting …', 'wporg-patterns' )
									: __( 'Unlist Pattern', 'wporg-patterns' ) }
							</Button>
						</div>
					</form>
				) }
			</div>
		</Modal>
	);
};

export default UnlistModal;
