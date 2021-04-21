/**
 * WordPress dependencies
 */
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Modal, RadioControl, Spinner, TextareaControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store as patternStore } from '../../store';

const ReportPatternModal = ( { onClose } ) => {
	const [ selectedOption, setOption ] = useState( '' );
	const [ details, setDetails ] = useState( '' );

	const { isLoading, reasons } = useSelect( ( select ) => {
		const { getPatternFlagReasons, isLoadingPatternFlagReasons } = select( patternStore );

		return {
			isLoading: isLoadingPatternFlagReasons(),
			reasons: getPatternFlagReasons(),
		};
	} );

	const handleSubmit = () => {
		if ( ! selectedOption ) {

		}
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
		<Modal title={ __( 'Report this pattern', 'wporg-patterns' ) }>
			{ isLoading ? (
				<Spinner />
			) : (
				<form onSubmit={ handleSubmit }>
					<RadioControl
						label={ __( 'Please choose a reason:', 'wporg-patterns' ) }
						selected={ selectedOption }
						options={ mappedReasons }
						onChange={ setOption }
						required={ true }
					/>
					<TextareaControl label="Details" value={ details } onChange={ setDetails } required={ true } />
					<Button isSecondary onClick={ onClose }>
						{ __( 'Cancel', 'wporg-patterns' ) }
					</Button>
					<Button type="submit" isPrimary onClick={ handleSubmit }>
						{ __( 'Report', 'wporg-patterns' ) }
					</Button>
				</form>
			) }
		</Modal>
	);
};

export default ReportPatternModal;
