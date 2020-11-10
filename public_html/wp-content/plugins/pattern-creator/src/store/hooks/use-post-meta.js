/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { KIND, MODULE_KEY, POST_TYPE } from '../utils';

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
		const { meta = {} } = select( 'core' ).getEditedEntityRecord( KIND, POST_TYPE, patternId );
		if ( ! meta ) {
			return defaultValue;
		}
		return meta[ key ] || defaultValue;
	} );

	const { editEntityRecord } = useDispatch( 'core' );
	const setMetaValue = ( value ) => {
		editEntityRecord( KIND, POST_TYPE, patternId, {
			meta: {
				[ key ]: value,
			},
		} );
	};

	return [ metaValue, setMetaValue ];
}
