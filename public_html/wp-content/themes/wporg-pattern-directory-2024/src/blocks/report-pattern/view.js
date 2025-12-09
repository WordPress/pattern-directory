/**
 * Internal dependencies
 */
import A11yDialog from 'a11y-dialog';

const init = () => {
	const element = document.getElementById( 'report-dialog' );
	if ( ! element ) {
		return;
	}
	// Initialize dialog.
	const dialog = new A11yDialog( element );

	// Open dialog with button.
	const button = document.querySelector( '.wp-block-wporg-report-pattern [data-a11y-dialog-show]' );
	button.disabled = false;
	button.addEventListener( 'click', () => {
		dialog.show();
	} );
};

window.addEventListener( 'load', init );
