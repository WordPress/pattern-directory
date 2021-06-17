/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PatternSearch from '../pattern-search';

const PageMenu = ( { isLoggedIn, searchComponent = PatternSearch } ) => {
	const SearchComponent = searchComponent;

	return (
		<nav id="site-navigation" className="main-navigation" role="navigation">
			<button
				className="menu-toggle dashicons dashicons-arrow-down-alt2"
				aria-controls="primary-menu"
				aria-expanded="false"
				aria-label={ __( 'Primary Menu', 'wporg-patterns' ) }
			></button>
			<div id="primary-menu" className="menu">
				<ul className="nav-menu">
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
						<SearchComponent />
					</li>
				</ul>
			</div>
		</nav>
	);
};

export default PageMenu;
