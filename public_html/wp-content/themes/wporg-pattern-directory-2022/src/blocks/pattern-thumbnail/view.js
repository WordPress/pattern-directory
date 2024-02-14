/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Iframe from '../../components/iframe';

const init = () => {
	const containers = document.querySelectorAll( '.wp-block-wporg-pattern-thumbnail' );
	if ( containers ) {
		containers.forEach( ( element ) => {
			const root = createRoot( element );
			root.render( <Iframe url={ element.dataset.url } /> );
		} );
	}
};

window.addEventListener( 'load', init );
