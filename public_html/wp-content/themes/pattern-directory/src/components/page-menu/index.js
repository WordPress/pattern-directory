/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PatternSearch from '../pattern-search';

const PageMenu = ( { isHidden, isLoggedIn, searchProps } ) => {
	const extraProps = {};

	// Since we use a third party navigation script, we always need the elements in the dom to bind to
	if ( isHidden ) {
		extraProps.style = {
			display: 'none',
		};
	}

	return (
		<nav id="site-navigation" className="main-navigation" role="navigation" { ...extraProps }>
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
							<a href={ `${ wporgSiteUrl }/my-patterns/` }>
								{ __( 'My patterns', 'wporg-patterns' ) }
							</a>
						</li>
					) }
					<li className="page_item">
						<a href={ `${ wporgSiteUrl }/new-patterns/` }>
							{ __( 'Create pattern', 'wporg-patterns' ) }
						</a>
					</li>
					<li className="page_item">
						<PatternSearch { ...searchProps } />
					</li>
				</ul>
			</div>
		</nav>
	);
};

export default PageMenu;
