/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { useViewportMatch } from '@wordpress/compose';
import { addQueryArgs, getQueryArg } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { useRoute } from '../../hooks';

const PatternOrderSelect = ( { options } ) => {
	const { path, replace } = useRoute();
	const hideLabel = useViewportMatch( 'medium', '>=' );

	if ( ! options ) {
		return null;
	}

	return (
		<div className="pattern-select-control">
			<SelectControl
				label={ __( 'Order by', 'wporg-patterns' ) }
				labelPosition="side"
				hideLabelFromVision={ hideLabel }
				value={ getQueryArg( window.location.href, 'orderby' ) }
				options={ options }
				onChange={ ( value ) => {
					const newUrl = addQueryArgs( path, { orderby: value } );

					replace( newUrl );
				} }
			/>
		</div>
	);
};

export default PatternOrderSelect;
