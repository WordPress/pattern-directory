/**
 * External dependencies
 */
import { dispatch, select } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

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
 * Set the ID of the block pattern which is being edited.
 * Helper layer over `editEntityRecord`.
 *
 * @param {Object} edits   The edits.
 * @param {Object} options Options for the edit.
 * @param {boolean} options.undoIgnore Whether to ignore the edit in undo history or not.
 * @yield {Object} Action object
 */
export function* editBlockPattern( edits, options = {} ) {
	const patternId = yield select( MODULE_KEY ).getEditingBlockPatternId();

	yield dispatch( 'core' ).editEntityRecord( KIND, POST_TYPE, patternId, edits, options );
}

/**
 * Save a block pattern.
 *
 * @yield {Object} Action object
 */
export function* saveBlockPattern() {
	yield dispatch( editorStore ).savePost();
}
