/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
	__experimentalGrid as Grid,
	Modal,
	SearchControl,
	/* @todo Remove StyleProvider workaround when https://github.com/WordPress/gutenberg/pull/36261 is released. */
	/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- Experimental is OK. */
	__experimentalStyleProvider as StyleProvider,
} from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import OpenverseGrid from './grid';

/* addToGallery, allowedTypes, gallery, multiple, value */
function OpenverseExplorer( { onSelect } ) {
	const [ searchTerm, setSearchTerm ] = useState( '' );

	return (
		<StyleProvider document={ document }>
			<Grid className="pattern-openverse__container" columns={ 2 } gap={ 6 } templateColumns="240px auto">
				<div>
					<SearchControl
						onChange={ setSearchTerm }
						value={ searchTerm }
						label={ __( 'Search for patterns', 'wporg-patterns' ) }
						placeholder={ __( 'Search', 'wporg-patterns' ) }
					/>
				</div>
				<OpenverseGrid searchTerm={ searchTerm } onSelect={ onSelect } />
			</Grid>
		</StyleProvider>
	);
}

export default function OpenverseGallery( { render, ...props } ) {
	const [ showModal, setShowModal ] = useState( false );

	return (
		<>
			{ render( {
				open: () => setShowModal( true ),
			} ) }
			{ showModal && (
				<Modal
					className="pattern-openverse__modal"
					title={ __( 'Openverse Media', 'wporg-patterns' ) }
					closeLabel={ __( 'Close', 'wporg-patterns' ) }
					onRequestClose={ () => setShowModal( false ) }
				>
					<OpenverseExplorer { ...props } />
				</Modal>
			) }
		</>
	);
}
