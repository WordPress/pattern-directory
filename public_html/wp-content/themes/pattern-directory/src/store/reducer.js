/**
 * WordPress dependencies
 */
import { combineReducers } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getAllCategory } from './utils';

/**
 * Reducer to track available patterns.
 *
 * @param {Object} state Current state.
 * @param {Object} action Dispatched action.
 * @return {Object} Updated state.
 */
export function patterns( state = {}, action ) {
	return {
		byId: byId( state.byId, action ),
		queries: queries( state.queries, action ),
	};
}

function byId( state = {}, action ) {
	const patternsById = ( action.patterns || [] ).reduce( ( acc, cur ) => ( { ...acc, [ cur.id ]: cur } ), {} );
	switch ( action.type ) {
		case 'LOAD_BLOCK_PATTERNS':
			return { ...state, ...patternsById };
		default:
			return state;
	}
}

function queries( state = {}, action ) {
	const patternIds = ( action.patterns || [] ).map( ( { id } ) => id );
	switch ( action.type ) {
		case 'LOAD_BLOCK_PATTERNS':
			return { ...state, [ action.query ]: [ ...( state[ action.query ] || [] ), ...patternIds ] };
		default:
			return state;
	}
}

/**
 * Reducer to track categories.
 *
 * @param {Object} state Current state.
 * @param {Object} action Dispatched action.
 * @return {Object} Updated state.
 */
export function categories( state = undefined, action ) {
	switch ( action.type ) {
		case 'FETCH_CATEGORIES':
			return null; // Indicates the query is in progress
		case 'LOAD_CATEGORIES':
			// Sort the categories alphabetically.
			// See: https://github.com/WordPress/pattern-directory/pull/76#issuecomment-818330872
			const sorted = ( action.categories || [] ).sort( ( a, b ) => a.name.localeCompare( b.name ) );

			return [ getAllCategory(), ...sorted ];
	}

	return state;
}

/**
 * Reducer to track the current query.
 *
 * @param {Object} state Current state.
 * @param {Object} action Dispatched action.
 * @return {Object} Updated state.
 */
export function currentQuery( state = undefined, action ) {
	switch ( action.type ) {
		case 'SET_CURRENT_QUERY':
			return action.query;
	}

	return state;
}

/**
 * Reducer to track pattern flag reasons.
 *
 * @param {Object} state Current state.
 * @param {Object} action Dispatched action.
 * @return {Object} Updated state.
 */
export function patternFlagReasons( state = undefined, action ) {
	switch ( action.type ) {
		case 'FETCH_PATTERN_FLAG_REASONS':
			return null;
		case 'LOAD_PATTERN_FLAG_REASONS':
			return [ ...action.reasons ];
		default:
			return state;
	}
}

/**
 * Reducer to track the user's favorites.
 *
 * @param {Object} state Current state.
 * @param {Object} action Dispatched action.
 * @return {Object} Updated state.
 */
export function favorites( state = [], action ) {
	const { patternId } = action;
	switch ( action.type ) {
		case 'LOAD_FAVORITES':
			return action.patternIds;
		case 'ADD_FAVORITE':
			return state.includes( patternId ) ? state : [ ...state, patternId ];
		case 'REMOVE_FAVORITE':
			return state.filter( ( id ) => id !== patternId );
	}

	return state;
}

export default combineReducers( {
	patterns,
	categories,
	currentQuery,
	patternFlagReasons,
	favorites,
} );
