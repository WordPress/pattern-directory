/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getCategoryFromPath } from '../../utils';
import { store as patternStore } from '../../store';

const Breadcrumbs = () => {
	const categorySlug = getCategoryFromPath( window.location.href );
	const { category, hasLoadedCategories } = useSelect( ( select ) => {
		return {
			category: select( patternStore ).getCategoryBySlug( categorySlug ),
			hasLoadedCategories: select( patternStore ).hasLoadedCategories(),
		};
	} );

	return (
		<div>
			<a href={ wporgSiteUrl } rel="home">
				{ __( 'All Patterns', 'wporg-patterns' ) }
			</a>
			{ hasLoadedCategories && (
				<>
					<span className="sep">/</span>
					<a href={ wporgSiteUrl } rel="home">
						{ __( 'Categories', 'wporg-patterns' ) }
					</a>
					<span className="sep">/</span>
					<span>{ category.name }</span>
				</>
			) }
		</div>
	);
};

export default Breadcrumbs;
