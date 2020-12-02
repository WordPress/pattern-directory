/**
 * External dependencies
 */
import { createRegistrySelector } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { KIND, POST_TYPE } from './utils';

export function getEditingBlockPatternId( state ) {
	return state.currentPatternId;
}

export const getEditedBlockPattern = createRegistrySelector( ( select ) => ( state, patternId ) => {
	return select( 'core' ).getEditedEntityRecord( KIND, POST_TYPE, patternId );
} );

export const hasEditsBlockPattern = createRegistrySelector( ( select ) => ( state, patternId ) => {
	return select( 'core' ).hasEditsForEntityRecord( KIND, POST_TYPE, patternId );
} );

export const isAutosavingBlockPattern = createRegistrySelector( ( select ) => ( state, patternId ) => {
	return select( 'core' ).isAutosavingEntityRecord( KIND, POST_TYPE, patternId );
} );

export const isSavingBlockPattern = createRegistrySelector( ( select ) => ( state, patternId ) => {
	const isSaving = select( 'core' ).isSavingEntityRecord( KIND, POST_TYPE, patternId );
	const isAutosaving = select( 'core' ).isAutosavingEntityRecord( KIND, POST_TYPE, patternId );

	return isSaving && ! isAutosaving;
} );
