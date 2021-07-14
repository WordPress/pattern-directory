/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
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

	return (
		<SelectControl
			className="select-control"
			label={ __( 'Order by', 'wporg-patterns' ) }
			value={ getQueryArg( window.location.href, 'orderby' ) }
			hideLabelFromVision={ true }
			options={ DEFAULT_ORDER_VALUES }
			onChange={ ( value ) => {
				const newUrl = addQueryArgs( path, { orderby: value } );

				replace( newUrl );
			} }
		/>
	);
};

export default PatternOrderSelect;
