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

const DEFAULT_ORDER_VALUES = [
	{ label: 'Newest', value: 'date' },
	{ label: 'Favorites', value: 'favorite_count' },
];

const PatternOrderSelect = () => {
	const { path, replace } = useRoute();
	const hideLabel = useViewportMatch( 'medium', '>=' );

	return (
		<div className="pattern-select-control">
			<SelectControl
				label={ __( 'Order by', 'wporg-patterns' ) }
				labelPosition="side"
				hideLabelFromVision={ hideLabel }
				value={ getQueryArg( window.location.href, 'orderby' ) }
				options={ DEFAULT_ORDER_VALUES }
				onChange={ ( value ) => {
					const newUrl = addQueryArgs( path, { orderby: value } );

					replace( newUrl );
				} }
			/>
		</div>
	);
};

export default PatternOrderSelect;
