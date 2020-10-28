/**
 * WordPress dependencies
 */
import { combineReducers } from '@wordpress/data';

function currentPatternId( state = -1, action ) {
	switch ( action.type ) {
		case 'EDIT_BLOCK_PATTERN':
			return action.value;
	}
	return state;
}

export default combineReducers( {
	currentPatternId,
} );
