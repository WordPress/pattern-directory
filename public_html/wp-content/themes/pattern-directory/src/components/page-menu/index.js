/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PatternSearch from '../pattern-search';
import { getCategoryFromPath } from '../../utils';
import { useRoute } from '../../hooks';
import { store as patternStore } from '../../store';
import { useSelect } from '@wordpress/data';

const PageMenu = ( { isLoggedIn } ) => {
	const { path, update: updatePath } = useRoute();
	const categorySlug = getCategoryFromPath( path );
	const category = useSelect( ( select ) => select( patternStore ).getCategoryBySlug( categorySlug ) );

	const handleLinkClick = ( event ) => {
		event.preventDefault();

		updatePath( event.target.pathname );
	};

	return (
		<>
			<div>
				<a href={ wporgSiteUrl } onClick={ handleLinkClick } rel="home">
					{ __( 'All Patterns', 'wporg-patterns' ) }
				</a>
				<span className="sep">/</span>
				{ category && (
					<>
						<a href={ wporgSiteUrl } rel="home">
							{ __( 'Categories', 'wporg-patterns' ) }
						</a>
						<span className="sep">/</span>
					</>
				) }

				<span>{ category ? category.name : 'Single' }</span>
			</div>
			<nav id="site-navigation" className="main-navigation" role="navigation">
				<button
					className="menu-toggle dashicons dashicons-arrow-down-alt2"
					aria-controls="primary-menu"
					aria-expanded="false"
					aria-label={ __( 'Primary Menu', 'wporg-patterns' ) }
				></button>
				<div id="primary-menu" className="menu">
					<ul className=" nav-menu">
						{ isLoggedIn && (
							<li className="page_item">
								<a href="http://localhost:8888/my-patterns/">
									{ __( 'My patterns', 'wporg-patterns' ) }
								</a>
							</li>
						) }
						<li className="page_item">
							<a href="http://localhost:8888/new-patterns/">
								{ __( 'Create patterns', 'wporg-patterns' ) }
							</a>
						</li>
						<li className="page_item">
							<PatternSearch />
						</li>
					</ul>
				</div>
			</nav>
		</>
	);
};

export default PageMenu;
