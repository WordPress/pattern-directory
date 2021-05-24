/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Module constants
 */
let tabBtnElement;

/**
 * Returns the WP button element
 *
 * @return {HTMLElement} Html anchor tag.
 */
const getElement = () => {
	if ( tabBtnElement ) {
		return tabBtnElement;
	}

	return document.querySelector( '.edit-post-sidebar__panel-tabs ul li:first-child button' );
};

const UpdateInspectorPanelText = () => {
	const updatedCopy = __( 'Pattern', 'wporg-patterns' );

	useEffect( () => {
		tabBtnElement = getElement();

		if ( tabBtnElement && tabBtnElement.innerText !== updatedCopy ) {
			tabBtnElement.innerText = updatedCopy;
		}
	} );

	return null;
};

registerPlugin( 'update-inspector-panel-text', {
	render: () => <UpdateInspectorPanelText />,
} );
