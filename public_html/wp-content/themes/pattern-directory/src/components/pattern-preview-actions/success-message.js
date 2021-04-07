/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Notice } from '@wordpress/components';

const SuccessMessage = ( { showMessage, onClick } ) => (
	<Notice
		className={ `pattern-actions__notice ${ ! showMessage ? 'pattern-actions__notice--is-hidden' : '' }` }
		status="success"
		isDismissible={ false }
	>
		<div>
			<b>{ __( 'Pattern copied!', 'wporg-patterns' ) }</b>
			{ __( ' Now you can paste it into any WordPress post or page.', 'wporg-patterns' ) }
		</div>
		<Button onClick={ onClick } isSecondary tabIndex={ showMessage ? '0' : '-1' }>
			{ __( 'Learn More', 'wporg-patterns' ) }
		</Button>
	</Notice>
);

export default SuccessMessage;
