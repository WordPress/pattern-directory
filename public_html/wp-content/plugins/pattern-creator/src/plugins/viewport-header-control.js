/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
const CONTAINER_ID = 'pattern-viewport-container';

const WIDTH_DEFAULTS = [
	{
		label: 'Narrow (320px)',
		value: 320,
	},
	{
		label: 'Default (800px)',
		value: 800,
	},
	{
		label: 'Large (1200px)',
		value: 1200,
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

const insertContainer = ( btnDomRef ) => {
	const container = document.createElement( 'div' );
	container.id = CONTAINER_ID;

	btnDomRef.parentElement.insertBefore( container, btnDomRef );
};

/**
 * Insert an html element to the right.
 *
 * @param {HTMLElement} newNode Element to be added
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

const ViewportHeaderControl = () => {
	const { editPost } = useDispatch( 'core/editor' );
	const postMetaData = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {} );
	const [ viewportWidth, setViewport ] = useState( postMetaData.wpop_viewport_width );

	const updateElementWidth = () => {
		const page = document.querySelector( '.block-editor-block-list__layout' );
		page.style.width = `${ viewportWidth }px`;
	};

	useEffect( () => {
		editPost( {
			meta: {
				...postMetaData,
				wpop_viewport_width: viewportWidth,
			},
		} );

		// Update the main container width
		updateElementWidth();

		insertButton(
			<DropdownMenu
				className="viewport-header-control"
				icon={ null }
				text={ sprintf(
					/* translators: %d viewport width as a number*/
					__( `Width (%dpx)`, 'wporg-patterns' ),
					viewportWidth
				) }
				label={ __( 'Select a viewport width', 'wporg-patterns' ) }
			>
				{ ( { onClose } ) => (
					<>
						<MenuGroup>
							{ WIDTH_DEFAULTS.map( ( i ) => {
								const isActive = i.value === viewportWidth;

								return (
									<MenuItem
										key={ i.value }
										icon={ isActive ? 'yes' : '' }
										isSelected={ isActive }
										onClick={ () => {
											setViewport( i.value );
											onClose();
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
