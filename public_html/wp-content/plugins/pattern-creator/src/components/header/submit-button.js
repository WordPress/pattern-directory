/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export default function SubmitButton( { isPressed, onClick } ) {
	const { isAutosaving } = useSelect( ( select ) => {
		const { getEditingBlockPatternId, isAutosavingBlockPattern } = select( 'wporg/block-pattern-creator' );
		const patternId = getEditingBlockPatternId();
		return {
			isAutosaving: isAutosavingBlockPattern( patternId ),
		};
	} );

	return (
		<Button
			isPrimary
			isBusy={ isAutosaving }
			aria-disabled={ isAutosaving }
			isPressed={ isPressed }
			onMouseDown={ ( event ) => {
				event.preventDefault();
			} }
			onClick={ onClick }
		>
			{ isAutosaving ? __( 'Autosaving…', 'wporg-patterns' ) : __( 'Submit', 'wporg-patterns' ) }
		</Button>
	);
}
