/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { store } from '@wordpress/editor';

/**
 * A hook to get and set a post value.
 *
 * @param {string|Object} property Identifier for a post property to use.
 *
 * @return {Array<*,Function>} A pair of values: the current meta value and a callback to update this meta value.
 */
export default function usePostData( property ) {
	const propValue = useSelect( ( select ) => select( store ).getEditedPostAttribute( property ) );

	const { editPost } = useDispatch( store );
	const setPropValue = ( value ) => {
		editPost( {
			[ property ]: value,
		} );
	};

	return [ propValue, setPropValue ];
}
