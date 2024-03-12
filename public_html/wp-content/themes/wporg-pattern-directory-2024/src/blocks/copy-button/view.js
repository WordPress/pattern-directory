/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';

/**
 * Internal dependencies
 */
import copyToClipboard from '../../utils/copy-to-clipboard';

const init = () => {
	const containers = document.querySelectorAll( '.wp-block-wporg-copy-button' );
	if ( containers ) {
		containers.forEach( ( element ) => {
			const button = element.querySelector( 'button' );
			const input = element.querySelector( '.wp-block-wporg-copy-button__content' );
			const content = JSON.parse( decodeURIComponent( input.value ) );

			button.disabled = false;
			button.onclick = async ( { target } ) => {
				const success = copyToClipboard( content );

				// Make sure we reset focus in case it was lost in the 'copy' command.
				target.focus();

				if ( success ) {
					speak( __( 'Copied pattern to clipboard.', 'wporg-patterns' ) );
					button.innerText = button.dataset.labelSuccess;
					setTimeout( () => {
						button.innerText = button.dataset.label;
					}, 20000 );
				}
			};
		} );
	}
};

window.addEventListener( 'load', init );
