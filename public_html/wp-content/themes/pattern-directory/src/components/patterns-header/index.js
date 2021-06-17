/**
 * WordPress dependencies
 */
import { hasQueryArg } from '@wordpress/url';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getCategoryFromPath } from '../../utils';
import { store as patternStore } from '../../store';

/**
 * Internal dependencies
 */
import Header from '../header';
import MastHead from '../masthead';
import PageMenu from '../page-menu';
import Breadcrumbs from '../breadcrumbs';
import { useRoute } from '../../hooks';

const PatternsHeader = ( { isHome, isLoggedIn } ) => {
	const { path } = useRoute();

	const categorySlug = getCategoryFromPath( window.location.href );
	const { category, hasLoadedCategories } = useSelect( ( select ) => {
		return {
			category: select( patternStore ).getCategoryBySlug( categorySlug ),
			hasLoadedCategories: select( patternStore ).hasLoadedCategories(),
		};
	} );

	const showMastHead = isHome && ! hasQueryArg( path, 'search' );

	return (
		<Header isHome={ showMastHead }>
			{ showMastHead ? (
				<MastHead />
			) : (
				<>
					{ hasLoadedCategories && (
						<Breadcrumbs
							crumbs={ [
								{ href: '/pattern-categories', label: 'Categories' },
								{ href: '', label: category.name },
							] }
						/>
					) }
					<PageMenu isLoggedIn={ isLoggedIn } />
				</>
			) }
		</Header>
	);
};

export default PatternsHeader;
