/**
 * WordPress dependencies
 */
import { addQueryArgs, getQueryArg } from '@wordpress/url';

/**
 * Internal dependencies
 */
import Search from '../search';
import { useRoute } from '../../hooks';

const PatternSearch = ( { action } ) => {
	const { path, update: updatePath } = useRoute();

	const handleUpdatePath = ( value ) => {
		const _path = action ? action : path;

        debugger;

		const updatedPath = addQueryArgs( _path, {
			search: value,
		} );

		if ( action ) {
			window.location.href = _path;
		} else {
			updatePath( updatedPath );
		}
	};

	return (
		<Search
			defaultValue={ getQueryArg( window.location.href, 'search' ) }
			onSubmit={ ( event ) => {
				event.preventDefault();
				handleUpdatePath( event.target.elements[ 0 ].value );
			} }
		/>
	);
};

export default PatternSearch;
