/**
 * WordPress dependencies
 */
import { hasQueryArg } from '@wordpress/url';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Header from '../header';
import MastHead from '../masthead';
import PageMenu from '../page-menu';
import Breadcrumbs from '../breadcrumbs';
import { useRoute } from '../../hooks';
import { getCategoryFromPath } from '../../utils';
import { store as patternStore } from '../../store';

const PatternsHeader = ( { isHome, isLoggedIn } ) => {
	const { path } = useRoute();
	const [ showMastHead, setShowMastHead ] = useState( isHome );

	const { category, hasLoadedCategories } = useSelect( ( select ) => {
		const categorySlug = getCategoryFromPath( window.location.href );
		return {
			category: select( patternStore ).getCategoryBySlug( categorySlug ),
			hasLoadedCategories: select( patternStore ).hasLoadedCategories(),
		};
	} );

	useEffect( () => {
		if ( hasQueryArg( path, 'search' ) ) {
			setShowMastHead( false );
		}
	}, [ path ] );

	return (
		<Header isHome={ showMastHead }>
			{ showMastHead ? (
				<MastHead />
			) : (
				<>
					{ hasLoadedCategories ? (
						<Breadcrumbs
							crumbs={ [
								{ href: '/pattern-categories', label: 'Categories' },
								{ href: '', label: category.name },
							] }
						/>
					) : (
						<span />
					) }
				</>
			) }
			<PageMenu isHidden={ showMastHead } isLoggedIn={ isLoggedIn } />
		</Header>
	);
};

export default PatternsHeader;
