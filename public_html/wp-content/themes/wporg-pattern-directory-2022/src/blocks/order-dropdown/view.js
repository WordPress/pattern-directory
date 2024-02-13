/**
 * WordPress dependencies
 */
import { addQueryArgs } from '@wordpress/url';

const init = () => {
	const containers = document.querySelectorAll( '.wp-block-wporg-order-dropdown' );
	if ( containers ) {
		containers.forEach( ( element ) => {
			const select = element.querySelector( 'select' );
			select.onchange = ( event ) => {
				const value = event.target.value;
				const url = addQueryArgs( window.location.href, { _orderby: value } ).replace(
					/\/page\/[\d]+/,
					''
				);
				window.location = url;
			};
		} );
	}
};

window.addEventListener( 'load', init );
