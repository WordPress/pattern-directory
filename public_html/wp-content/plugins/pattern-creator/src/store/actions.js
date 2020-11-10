/**
 * External dependencies
 */
import { dispatch, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { KIND, MODULE_KEY, POST_TYPE } from './utils';

/**
 * Set the ID of the block pattern which is being edited.
 *
 * @param {number} patternId
 * @return {Object} Action object
 */
export function editBlockPatternId( patternId ) {
	return { type: 'EDIT_BLOCK_PATTERN', value: patternId };
}

/**
 * Save a block pattern.
 *
 * @yield {Object} Action object
 */
export function* saveBlockPattern() {
	const patternId = yield select( MODULE_KEY ).getEditingBlockPatternId();

	// @todo maybe check for errors?
	yield dispatch( 'core' ).saveEditedEntityRecord( KIND, POST_TYPE, patternId );
}
