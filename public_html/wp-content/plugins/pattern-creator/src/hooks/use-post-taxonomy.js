/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { store } from '@wordpress/editor';

/**
 * A hook to get and set a post taxonomy value.
 *
 * @param {string} taxonomy Identifier for taxonomy to use. Should be the "REST base" name, not the tax slug.
 *
 * @return {Array<*,Function>} A pair of values: the current meta value and a callback to update this meta value.
 */
export default function usePostTaxonomy( taxonomy ) {
	const taxValue = useSelect( ( select ) => select( store ).getEditedPostAttribute( taxonomy ) || [] );

	const { editPost } = useDispatch( store );
	const setTaxValue = ( value ) => {
		if ( ! Array.isArray( value ) ) {
			value = [ value ];
		}
		editPost( {
			[ taxonomy ]: value,
		} );
	};

	return [ taxValue, setTaxValue ];
}
