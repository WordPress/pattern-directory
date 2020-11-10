/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { MODULE_KEY } from '../utils';

/**
 * A hook to get and set a post meta value.
 *
 * @param {string} key          Meta key.
 * @param {?*}     defaultValue A default value, if the key is not set.
 *
 * @return {Array<*,Function>} A pair of values: the current meta value and a callback to update this meta value.
 */
export default function usePostMeta( key, defaultValue = '' ) {
	const patternId = useSelect( ( select ) => select( MODULE_KEY ).getEditingBlockPatternId() );

	const metaValue = useSelect( ( select ) => {
		const { meta = {} } = select( MODULE_KEY ).getEditedBlockPattern( patternId );
		if ( ! meta ) {
			return defaultValue;
		}
		return meta[ key ] || defaultValue;
	} );

	const { editBlockPattern } = useDispatch( MODULE_KEY );
	const setMetaValue = ( value ) => {
		editBlockPattern( {
			meta: {
				[ key ]: value,
			},
		} );
	};

	return [ metaValue, setMetaValue ];
}
