/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CopyPatternButton from '../copy-pattern-button';
import AddToFavoriteButton from '../add-to-favorite-button';

const SuccessMessage = ( { showMessage } ) => (
	<Notice
		className={ `pattern-actions__notice ${ ! showMessage ? 'pattern-actions__notice--is-hidden' : '' }` }
		status="success"
		isDismissible={ false }
	>
		<div>
			<b>{ __( 'Pattern copied!', 'wporg-patterns' ) }</b>
			{ __( ' Now you can paste it into any WordPress post or page.', 'wporg-patterns' ) }
		</div>
		<Button isSecondary>{ __( 'Learn More', 'wporg-patterns' ) }</Button>
	</Notice>
);

const PatternPreviewActions = () => {
	const [ showSuccess, setShowSuccess ] = useState();

	return (
		<>
			<CopyPatternButton onSuccess={ () => setShowSuccess( true ) } />
			<AddToFavoriteButton />
			<SuccessMessage showMessage={ showSuccess } />
		</>
	);
};

export default PatternPreviewActions;
