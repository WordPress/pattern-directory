/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const init = () => {
	const containers = document.querySelectorAll( '.wp-block-wporg-favorite-button' );
	if ( containers ) {
		containers.forEach( ( element ) => {
			const button = element.querySelector( 'button' );
			if ( ! button ) {
				return;
			}

			const label = element.querySelector( '.wp-block-wporg-favorite-button__label' );
			const countEl = element.querySelector( '.wp-block-wporg-favorite-button__count' );
			button.disabled = false;
			button.onclick = async () => {
				const { action, postId } = element.dataset;
				if ( action === 'add' ) {
					try {
						const newCount = await apiFetch( {
							path: '/wporg/v1/pattern-favorites',
							method: 'POST',
							data: { id: postId },
						} );
						element.classList.add( 'is-favorite' );
						label.innerText = __( 'Remove from favorites', 'wporg-patterns' );
						element.dataset.action = 'remove';
						if ( countEl ) {
							countEl.innerText = newCount;
						}
					} catch ( error ) {}
				} else {
					try {
						const newCount = await apiFetch( {
							path: '/wporg/v1/pattern-favorites',
							method: 'DELETE',
							data: { id: postId },
						} );
						element.classList.remove( 'is-favorite' );
						label.innerText = __( 'Add to favorites', 'wporg-patterns' );
						element.dataset.action = 'add';
						if ( countEl ) {
							countEl.innerText = newCount;
						}
					} catch ( error ) {}
				}
			};
		} );
	}
};

window.addEventListener( 'load', init );
