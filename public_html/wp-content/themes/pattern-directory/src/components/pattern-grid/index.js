/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import PatternThumbnail from '../pattern-thumbnail';

function PatternGrid() {
	const posts = useSelect( ( select ) => {
		// @todo This only works for logged in users, we'll need to create our own store for general use.
		const { getEntityRecords } = select( coreStore );
		return getEntityRecords( 'postType', 'wporg-pattern' );
	} );
	// `posts` will be null while the fetch happens.
	const isLoading = ! Array.isArray( posts );

	return (
		<>
			<header
				style={ { background: 'whitesmoke', padding: '24px', textAlign: 'center', margin: '0 0 24px' } }
			>
				Section filters
			</header>

			<div className="pattern-grid">
				{ isLoading ? (
					<Spinner />
				) : (
					posts.map( ( post ) => <PatternThumbnail key={ post.id } pattern={ post } /> )
				) }
			</div>
		</>
	);
}

export default PatternGrid;
