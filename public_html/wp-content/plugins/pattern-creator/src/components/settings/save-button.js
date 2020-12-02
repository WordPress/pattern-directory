/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';

function SaveButton() {
	const { hasEdits, isAutosaving, isSaving } = useSelect( ( select ) => {
		const {
			getEditingBlockPatternId,
			hasEditsBlockPattern,
			isAutosavingBlockPattern,
			isSavingBlockPattern,
		} = select( 'wporg/block-pattern-creator' );
		const patternId = getEditingBlockPatternId();
		return {
			hasEdits: hasEditsBlockPattern( patternId ),
			isAutosaving: isAutosavingBlockPattern( patternId ),
			isSaving: isSavingBlockPattern( patternId ),
		};
	} );
	const { saveBlockPattern } = useDispatch( 'wporg/block-pattern-creator' );

	let label = __( 'Update', 'wporg-patterns' );
	if ( isSaving ) {
		label = __( 'Saving…', 'wporg-patterns' );
	} else if ( isAutosaving ) {
		label = __( 'Autosaving…', 'wporg-patterns' );
	}

	return (
		<Button
			isPrimary
			isBusy={ isSaving || isAutosaving }
			aria-disabled={ isSaving || isAutosaving }
			onClick={ saveBlockPattern }
			disabled={ ! hasEdits }
		>
			{ label }
		</Button>
	);
}

export default SaveButton;
