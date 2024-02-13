/**
 * WordPress dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Iframe from '../../components/iframe';

const init = () => {
	const containers = document.querySelectorAll( '.wp-block-wporg-pattern-thumbnail' );
	if ( containers ) {
		containers.forEach( ( element ) => {
			render( <Iframe url={ element.dataset.url } />, element );
		} );
	}
};

window.addEventListener( 'load', init );
