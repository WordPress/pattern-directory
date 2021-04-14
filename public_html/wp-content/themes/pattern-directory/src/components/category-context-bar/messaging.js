/**
 * WordPress dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Returns an object with extra information about the category.
 *
 * @param {number} count Number of patterns associated to the category.
 * @param {string} categoryName The category name.
 * @return {Object}
 */
export const getContextMessage = ( count, categoryName ) => {
	return {
		message: createInterpolateElement(
			sprintf(
				/* translators: %1$d: number of patterns. %2$s category name. */
				_n( '%1$d <b>%2$s</b> pattern.', '%1$d <b>%2$s</b> patterns.', count, 'wporg-patterns' ),
				count,
				categoryName.toLowerCase(),
				'wporg-patterns'
			),

			{
				b: <b />,
			}
		),

		// TODO Fetch these from somewhere
		title: '',
		links: [],
	};
};
