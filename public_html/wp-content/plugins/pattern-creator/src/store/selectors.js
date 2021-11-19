/**
 * External dependencies
 */
import { get } from 'lodash';
import createSelector from 'rememo';

/**
 * WordPress dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { createRegistrySelector } from '@wordpress/data';
import {
	/* eslint-disable-next-line @wordpress/no-unsafe-wp-apis */
	__unstableSerializeAndClean,
	getDefaultBlockName,
	getFreeformContentHandlerName,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { POST_TYPE } from './constants';

/**
 * Shared reference to an empty object for cases where it is important to avoid
 * returning a new object reference on every invocation, as in a connected or
 * other pure component which performs `shouldComponentUpdate` check on props.
 * This should be used as a last resort, since the normalized data should be
 * maintained by the reducer result in state.
 */
const EMPTY_OBJECT = {};
const EMPTY_ARRAY = [];

/**
 * Returns whether the given feature is enabled or not.
 *
 * @param {Object} state   Global application state.
 * @param {string} feature Feature slug.
 *
 * @return {boolean} Is active.
 */
export function isFeatureActive( state, feature ) {
	return get( state.preferences.features, [ feature ], false );
}

/**
 * Returns the current editing canvas device type.
 *
 * @param {Object} state Global application state.
 *
 * @return {string} Device type.
 */
export function getPreviewDeviceType( state ) {
	return state.deviceType;
}

/**
 * Returns the settings, taking into account active features and permissions.
 *
 * @param {Object}   state             Global application state.
 * @param {Function} setIsInserterOpen Setter for the open state of the global inserter.
 *
 * @return {Object} Settings.
 */
export const getSettings = createSelector(
	( state, setIsInserterOpen ) => {
		const settings = {
			...state.settings,
			outlineMode: true,
			focusMode: isFeatureActive( state, 'focusMode' ),
			hasFixedToolbar: isFeatureActive( state, 'fixedToolbar' ),
			__experimentalSetIsInserterOpened: setIsInserterOpen,
		};

		return settings;
	},
	( state ) => [
		state.settings,
		isFeatureActive( state, 'focusMode' ),
		isFeatureActive( state, 'fixedToolbar' ),
	]
);

/**
 * Returns the current opened/closed state of the inserter panel.
 *
 * @param {Object} state Global application state.
 *
 * @return {boolean} True if the inserter panel should be open; false if closed.
 */
export function isInserterOpened( state ) {
	return !! state.blockInserterPanel;
}

/**
 * Get the insertion point for the inserter.
 *
 * @param {Object} state Global application state.
 *
 * @return {Object} The root client ID and index to insert at.
 */
export function __experimentalGetInsertionPoint( state ) {
	const { rootClientId, insertionIndex } = state.blockInserterPanel;
	return { rootClientId, insertionIndex };
}

/**
 * Returns the current opened/closed state of the list view panel.
 *
 * @param {Object} state Global application state.
 *
 * @return {boolean} True if the list view panel should be open; false if closed.
 */
export function isListViewOpened( state ) {
	return state.listViewPanel;
}

/**
 * Returns whether the pattern is "saveable".
 *
 * A pattern can be saved if it has a title and content. The other requirements
 * are handled in the publish flow, title and content are the only things
 * required for saving a draft.
 *
 * See https://github.com/WordPress/gutenberg/blob/31330dbb737ce30646a4300410faed633061547a/packages/editor/src/store/selectors.js#L531
 *
 * @param {Object} state  Global application state.
 * @param {number} postId The ID of this pattern.
 *
 * @return {Object} Whether the post can be saved.
 */
export const isPatternSaveable = createRegistrySelector( ( select ) => ( state, postId ) => {
	const post = select( coreStore ).getEditedEntityRecord( 'postType', POST_TYPE, postId ) || EMPTY_OBJECT;

	const hasTitle = !! post.title;
	const hasContent = ! isEditedPostEmpty( post );

	return hasTitle && hasContent;
} );

function isEditedPostEmpty( post ) {
	const blocks = post.blocks || EMPTY_ARRAY;

	if ( blocks.length ) {
		if ( blocks.length > 1 ) {
			return false;
		}

		const blockName = blocks[ 0 ].name;
		if ( blockName !== getDefaultBlockName() && blockName !== getFreeformContentHandlerName() ) {
			return false;
		}
	}

	if ( typeof post.content === 'function' ) {
		return ! post.content( post );
	} else if ( post.blocks ) {
		return ! __unstableSerializeAndClean( post.blocks );
	} else if ( post.content ) {
		return ! post.content;
	}
}
