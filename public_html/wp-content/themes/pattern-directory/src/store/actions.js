/**
 * Get the action object signalling that patterns have been requested.
 *
 * @param {string} query Search string.
 *
 * @return {Object} Action object.
 */
export function fetchPatterns( query ) {
	return { type: 'FETCH_BLOCK_PATTERNS', query: query };
}

/**
 * Get the action object signalling that patterns have been loaded.
 *
 * @param {string} query Search string.
 * @param {Array} patterns A list of patterns.
 *
 * @return {Object} Action object.
 */
export function loadPatterns( query, patterns ) {
	return { type: 'LOAD_BLOCK_PATTERNS', query: query, patterns: patterns };
}

/**
 * Get the action object signalling that the current view has been set.
 *
 * @param {string} query Query object.
 * @return {Object} Action object.
 */
export function setCurrentQuery( query ) {
	return { type: 'SET_CURRENT_QUERY', query: query };
}

/**
 * Get the action object signalling that categories have been requested.
 *
 * @return {Object} Action object.
 */
export function fetchCategories() {
	return { type: 'FETCH_CATEGORIES' };
}

/**
 * Get the action object signalling that categories have been loaded.
 *
 * @param {Array} categories A list of categories.
 * @return {Object} Action object.
 */
export function loadCategories( categories ) {
	return { type: 'LOAD_CATEGORIES', categories: categories };
}

/**
 * Get the action object signalling that pattern flag reasons have been requested.
 *
 * @return {Object} Action object.
 */
export function fetchPatternFlagReasons() {
	return { type: 'FETCH_PATTERN_FLAG_REASONS' };
}

/**
 * Get the action object signalling that pattern flag reasons have been loaded.
 *
 * @param {Array} reasons A list of reasons.
 * @return {Object} Action object.
 */
export function loadPatternFlagReasons( reasons ) {
	return { type: 'LOAD_PATTERN_FLAG_REASONS', reasons: reasons };
}
