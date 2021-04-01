/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { ifViewportMatches } from '@wordpress/viewport';

const MobileMenu = ( { onClick, path, options } ) => (
	<SelectControl
		labelPosition="side"
		label={ __( 'Categories', 'wporg-patterns' ) }
		value={ path }
		options={ options }
		onChange={ onClick }
	/>
);

export default ifViewportMatches( '< medium' )( MobileMenu );
