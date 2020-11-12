/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { MODULE_KEY } from '../utils';

/**
 * A hook to get and set a post taxonomy value.
 *
 * @param {string} taxonomy Identifier for taxonomy to use. Should be the "REST base" name, not the tax slug.
 *
 * @return {Array<*,Function>} A pair of values: the current meta value and a callback to update this meta value.
 */
export default function usePostTaxonomy( taxonomy ) {
	const patternId = useSelect( ( select ) => select( MODULE_KEY ).getEditingBlockPatternId() );

	const taxValue = useSelect( ( select ) => {
		const post = select( MODULE_KEY ).getEditedBlockPattern( patternId );
		return post[ taxonomy ] || [];
	} );

	const { editBlockPattern } = useDispatch( MODULE_KEY );
	const setTaxValue = ( value ) => {
		if ( ! Array.isArray( value ) ) {
			value = [ value ];
		}
		editBlockPattern( {
			[ taxonomy ]: value.map( Number ),
		} );
	};

	return [ taxValue, setTaxValue ];
}
