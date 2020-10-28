/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';

function SaveButton() {
	const { hasEdits, isSaving } = useSelect( ( select ) => {
		const { getEditingBlockPatternId, hasEditsBlockPattern, isSavingBlockPattern } = select(
			'wporg/block-pattern-creator'
		);
		const patternId = getEditingBlockPatternId();
		return {
			hasEdits: hasEditsBlockPattern( patternId ),
			isSaving: isSavingBlockPattern( patternId ),
		};
	} );
	const { saveBlockPattern } = useDispatch( 'wporg/block-pattern-creator' );

	return (
		<Button
			isPrimary
			isBusy={ isSaving }
			aria-disabled={ isSaving }
			onClick={ saveBlockPattern }
			disabled={ ! hasEdits }
		>
			{ isSaving ? __( 'Saving…', 'wporg-patterns' ) : __( 'Update', 'wporg-patterns' ) }
		</Button>
	);
}

export default SaveButton;
