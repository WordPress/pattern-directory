/**
 * WordPress dependencies
 */
import { addQueryArgs, getQueryArg } from '@wordpress/url';
import { useDebounce } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import Search from '../search';
import { useRoute } from '../../hooks';

/**
 * Module constants
 */
const DEBOUNCE_MS = 300;

const PatternSearch = () => {
	const { path, update: updatePath } = useRoute();

	const handleUpdatePath = ( value ) => {
		const updatedPath = addQueryArgs( path, {
			search: value,
		} );

		updatePath( updatedPath );
	};

	const debouncedHandleUpdate = useDebounce( handleUpdatePath, DEBOUNCE_MS );

	return (
		<Search
			defaultValue={ getQueryArg( window.location.href, 'search' ) }
			onUpdate={ ( event ) => {
				event.preventDefault();
				debouncedHandleUpdate( event.target.value );
			} }
			onSubmit={ ( event ) => {
				event.preventDefault();
				debouncedHandleUpdate( event.target.elements[ 0 ].value );
			} }
		/>
	);
};

export default PatternSearch;
