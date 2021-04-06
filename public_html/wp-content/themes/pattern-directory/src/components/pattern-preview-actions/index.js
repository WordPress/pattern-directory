/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CopyPatternButton from '../copy-pattern-button';
import AddToFavoriteButton from '../add-to-favorite-button';

const SuccessMessage = () => (
	<Notice className="pattern-actions__notice" status="success" isDismissible={ false }>
		<b>{ __( 'Pattern copied!', 'wporg-patterns' ) }</b>
		{ __( ' Now you can paste it into any WordPress post or page.', 'wporg-patterns' ) }
	</Notice>
);

const PatternPreviewActions = () => {
	const [ showSuccess, setShowSuccess ] = useState();

	return (
		<>
			<CopyPatternButton onSuccess={ () => setShowSuccess( true ) } />
			<AddToFavoriteButton />
			{ showSuccess && <SuccessMessage /> }
		</>
	);
};

export default PatternPreviewActions;
