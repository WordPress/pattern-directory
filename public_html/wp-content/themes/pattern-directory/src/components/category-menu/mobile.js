/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { ifViewportMatches } from '@wordpress/viewport';

const MobileMenu = ( { onClick, options } ) => (
	<SelectControl label="Categories" options={ options } onChange={ onClick } />
);

export default ifViewportMatches( '< medium' )( MobileMenu );
