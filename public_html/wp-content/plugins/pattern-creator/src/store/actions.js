/**
 * External dependencies
 */
import { dispatch, select } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { KIND, MODULE_KEY, POST_TYPE } from './utils';

export function editBlockPattern( patternId ) {
	return { type: 'EDIT_BLOCK_PATTERN', value: patternId };
}

/**
 * Save a block pattern.
 */
export function* saveBlockPattern() {
	const patternId = yield select( MODULE_KEY, 'getEditingBlockPatternId' );

	// @todo maybe check for errors?
	yield dispatch( 'core', 'saveEditedEntityRecord', KIND, POST_TYPE, patternId );
}
