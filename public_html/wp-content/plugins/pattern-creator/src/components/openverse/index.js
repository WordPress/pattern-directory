/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
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

const suggestedTerms = [
	__( 'Mountains', 'wporg-patterns' ),
	__( 'Trees', 'wporg-patterns' ),
	__( 'Water', 'wporg-patterns' ),
	__( 'Space', 'wporg-patterns' ),
	__( 'Birds', 'wporg-patterns' ),
];

function OpenverseExplorer( { onClose, ...props } ) {
	const [ searchTerm, setSearchTerm ] = useState( '' );

	return (
		<StyleProvider document={ document }>
			<div className="pattern-openverse__container">
				<div className="pattern-openverse__search">
					<SearchControl
						onChange={ setSearchTerm }
						value={ searchTerm }
						label={ __( 'Search for patterns', 'wporg-patterns' ) }
						placeholder={ __( 'Search', 'wporg-patterns' ) }
					/>
					{ searchTerm.length ? null : (
						<p className="pattern-openverse__search-suggestions">
							<strong>{ __( 'Suggestions', 'wporg-patterns' ) }</strong>
							{ suggestedTerms.map( ( term, i ) => (
								<Button key={ i } variant="link" onClick={ () => setSearchTerm( term ) }>
									{ term }
								</Button>
							) ) }
						</p>
					) }
				</div>
				<OpenverseGrid searchTerm={ searchTerm } onClose={ onClose } { ...props } />
			</div>
		</StyleProvider>
	);
}

export default function OpenverseGallery( { render, ...props } ) {
	const [ showModal, setShowModal ] = useState( false );
	const handleOpen = () => setShowModal( true );
	const handleClose = () => setShowModal( false );

	return (
		<>
			{ render( {
				open: handleOpen,
			} ) }
			{ showModal && (
				<Modal
					className="pattern-openverse__modal"
					title={
						<>
							<span className="pattern-openverse__modal-title">
								{ __( 'Pattern media', 'wporg-patterns' ) }
							</span>
							<span
								className="pattern-openverse__powered-by"
								aria-label={ __( 'Powered by Openverse', 'wporg-patterns' ) }
							/>
						</>
					}
					closeLabel={ __( 'Close', 'wporg-patterns' ) }
					onRequestClose={ handleClose }
				>
					<OpenverseExplorer onClose={ handleClose } { ...props } />
				</Modal>
			) }
		</>
	);
}
