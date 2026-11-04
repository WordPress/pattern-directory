/* global wporgBlockPattern */
/**
 * WordPress dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CATEGORY_SLUG } from '../store';

const QUERY = {
	per_page: -1,
	orderby: 'name',
	order: 'asc',
	_fields: 'id,name,parent,slug',
	context: 'view',
};

const EMPTY_ARRAY = [];

/**
 * Filter a list of category terms down to the ones authors can select.
 *
 * @param {Object[]} terms        Category terms.
 * @param {string[]} allowedSlugs Slugs of the selectable categories.
 *
 * @return {Object[]} Selectable category terms.
 */
export function filterSelectableCategories( terms, allowedSlugs ) {
	return terms.filter( ( { slug } ) => allowedSlugs.includes( slug ) );
}

/**
 * A hook to get the pattern categories that authors can assign to a pattern.
 *
 * The list of selectable slugs comes from the server, so that every category picker shows the same options.
 *
 * @return {Object[]} Category terms, sorted by name.
 */
export default function usePatternCategories() {
	const terms = useSelect(
		( select ) => select( coreStore ).getEntityRecords( 'taxonomy', CATEGORY_SLUG, QUERY ),
		[]
	);

	return useMemo(
		() =>
			terms
				? filterSelectableCategories( terms, wporgBlockPattern.categorySlugs || EMPTY_ARRAY )
				: EMPTY_ARRAY,
		[ terms ]
	);
}
