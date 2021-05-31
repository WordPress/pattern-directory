/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { select } from '@wordpress/data';
import { store } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import UnitControl from '../components/unit-control';
import usePostData from '../hooks/use-post-data';

/**
 * Module Constants
 */
const CONTAINER_ID = 'pattern-viewport-container';
const META_KEY = 'wpop_viewport_width';
const WIDTH_DEFAULTS = [
	{
		label: 'Narrow (320px)',
		value: '320px',
	},
	{
		label: 'Default (800px)',
		value: '800px',
	},
	{
		label: 'Full (100%)',
		value: '100%',
	},
];

/**
 * Removes the button container if it exists
 */
const maybeRemoveContainer = () => {
	const container = document.getElementById( CONTAINER_ID );

	if ( container && container.parentElement ) {
		container.parentElement.removeChild( container );
	}
};

/**
 * Inserts an empty container so we can bind our ReactDOM node.
 *
 * @param {external:Node} btnDomRef
 */
const insertContainer = ( btnDomRef ) => {
	const container = document.createElement( 'div' );
	container.id = CONTAINER_ID;

	btnDomRef.parentElement.insertBefore( container, btnDomRef );
};

/**
 * Insert an html element to the right.
 *
 * @param {external:Node} newNode Element to be added
 */
export const insertButton = ( newNode ) => {
	// The Gutenberg Publish Button
	const btnDomRef = document.querySelector( '.editor-post-publish-button__button' );

	if ( ! btnDomRef ) {
		return;
	}

	// We may re-insert the same button if it state's changes
	maybeRemoveContainer();

	insertContainer( btnDomRef );

	/* eslint-disable no-undef*/
	ReactDOM.render( newNode, document.getElementById( CONTAINER_ID ) );
};

/**
 * Updates the width of the DOM element surrounded the patterns.
 *
 * @param {string} newValue A valid css width property
 */
const updateElementWidth = ( newValue ) => {
	const page = document.querySelector( '.block-editor-block-list__layout' );
	page.style.width = newValue;
};

const ViewportHeaderControl = () => {
	const [ meta, setMeta ] = usePostData( 'meta' );
	const [ viewportWidth, setViewport ] = useState( meta[ META_KEY ] );

	/**
	 * Updates the setViewport property which rebinds the control to the header.
	 */
	const updateControlWithEditedData = () => {
		// Because this context is bound when we insert the button,
		// We need to retrieve the value from the global state or we will get the original, out of date value
		const editedMeta = select( store ).getEditedPostAttribute( 'meta' );

		setViewport( editedMeta[ META_KEY ] );
	};

	useEffect( () => {
		insertButton(
			<DropdownMenu
				className="viewport-header-control"
				icon={ null }
				text={ sprintf(
					/* translators: %s viewport width as css, ie: 100% */
					__( `Width (%s)`, 'wporg-patterns' ),
					viewportWidth
				) }
				popoverProps={ {
					onClose: updateControlWithEditedData,
					onFocusOutside: updateControlWithEditedData,
				} }
				label={ __( 'Select a viewport width', 'wporg-patterns' ) }
			>
				{ ( ) => (
					<>
						<MenuGroup label="Viewport Width">
							<p className="viewport-header-control__copy">
								This is used when displaying a preview of your pattern.
							</p>
							<UnitControl
								label={ __( 'Viewport Width', 'wporg-patterns' ) }
								onChange={ ( newValue ) => {
									setMeta( {
										...meta,
										[ META_KEY ]: newValue,
									} );
									updateElementWidth( newValue );
								} }
							/>
						</MenuGroup>
						<MenuGroup>
							{ WIDTH_DEFAULTS.map( ( i ) => {
								const isActive = i.value === viewportWidth;

								return (
									<MenuItem
										key={ i.value }
										icon={ isActive ? 'yes' : '' }
										isSelected={ isActive }
										onClick={ () => {
											setMeta( {
												...meta,
												[ META_KEY ]: i.value,
											} );
											updateElementWidth( i.value );
											updateControlWithEditedData();
										} }
									>
										{ i.label }
									</MenuItem>
								);
							} ) }
						</MenuGroup>
					</>
				) }
			</DropdownMenu>
		);
	}, [ viewportWidth ] );

	return null;
};

registerPlugin( 'viewport-header-control', {
	render: ViewportHeaderControl,
} );
