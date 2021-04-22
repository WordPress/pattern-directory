/**
 * WordPress dependencies
 */
import { useMemo, useReducer, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Modal, RadioControl, Spinner, TextareaControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { store as patternStore } from '../../store';

const reducer = ( state, action ) => {
	switch ( action.status ) {
		case 'submitting':
			return { isSubmitting: true };
		case 'submitted':
			return { isSubmitted: true };
		case 'error':
			return { hasError: true, message: action.message };
		default:
			return {};
	}
};

const ReportPatternModal = ( { postId, onClose } ) => {
	const [ state, dispatch ] = useReducer( reducer, {} );
	const [ selectedOption, setOption ] = useState( '' );
	const [ details, setDetails ] = useState( '' );

	const { isLoading, reasons } = useSelect( ( select ) => {
		const { getPatternFlagReasons, isLoadingPatternFlagReasons } = select( patternStore );

		return {
			isLoading: isLoadingPatternFlagReasons(),
			reasons: getPatternFlagReasons(),
		};
	} );

	const handleSubmit = ( event ) => {
		event.preventDefault();

		if ( ! selectedOption || ! details.length || state.isSubmitted || state.isSubmitting ) {
			return;
		}

		dispatch( { status: 'submitting' } );

		apiFetch( {
			path: addQueryArgs( '/wp/v2/wporg-pattern-flag' ),
			method: 'POST',
			data: {
				parent: postId,
				'wporg-pattern-flag-reason': selectedOption,
				excerpt: details,
			},
		} )
			.then( () => {
				dispatch( { status: 'submitted' } );
			} )
			.catch( ( err ) => {
				dispatch( {
					status: 'error',
					message: err.message,
				} );
			} );
	};

	const mappedReasons = useMemo( () => {
		if ( ! reasons ) {
			return [];
		}

		return (
			reasons
				// We sort by id to give some control on the server side.
				.sort( ( a, b ) => a.id - b.id )
				.map( ( i ) => {
					// We need to convert id to string to make the RadioControl match the selected item.
					return { label: i.name, value: i.id.toString() };
				} )
		);
	}, [ reasons ] );

	return (
		<Modal
			className="pattern-report-modal"
			title={ __( 'Report this pattern', 'wporg-patterns' ) }
			onRequestClose={ () => {
				onClose( state.isSubmitted );
			} }
		>
			{ isLoading ? (
				<Spinner />
			) : state.isSubmitted ? (
				<p className="pattern-report-modal__copy">
					{ __(
						'Thank you for your report submission. We will review this pattern and act accordingly.',
						'wporg-patterns'
					) }
				</p>
			) : (
				<form onSubmit={ handleSubmit }>
					<RadioControl
						className="pattern-report-modal__radio"
						label={ __( 'Please choose a reason:', 'wporg-patterns' ) }
						selected={ selectedOption }
						options={ mappedReasons }
						onChange={ setOption }
						required={ true }
					/>
					<TextareaControl
						label="Please provide details (required)"
						value={ details }
						onChange={ setDetails }
						required={ true }
					/>

					{ state.hasError && (
						<div className="notice notice-large notice-alt notice-error">{ state.message }</div>
					) }
					<div className="pattern-report-modal__actions">
						<Button isSecondary onClick={ onClose }>
							{ __( 'Cancel', 'wporg-patterns' ) }
						</Button>
						<Button type="submit" isBusy={ state.isSubmitting } isPrimary>
							{ state.isSubmitting
								? __( 'Submitting …', 'wporg-patterns' )
								: __( 'Report', 'wporg-patterns' ) }
						</Button>
					</div>
				</form>
			) }
		</Modal>
	);
};

export default ReportPatternModal;
