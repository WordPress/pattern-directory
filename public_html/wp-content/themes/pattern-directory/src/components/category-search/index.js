/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, search } from '@wordpress/icons';

const CategorySearch = ( { isLoading, isVisible } ) => {
	if ( isLoading ) {
		return <span className="category-search--is-loading" />;
	}

	if ( ! isVisible ) {
		return null;
	}

	return (
		<form method="get" action="/" className="category-search">
			<label htmlFor="pattern-search" className="screen-reader-text">
				{ __( 'Search for:', 'wporg-patterns' ) }
			</label>
			<input id="pattern-search" type="search" placeholder={ __( 'Search patterns', 'wporg-patterns' ) } />
			<button className="category-search__button">
				<span className="screen-reader-text"> { __( 'Search patterns', 'wporg-patterns' ) }</span>
				<Icon icon={ search } />
			</button>
		</form>
	);
};

export default CategorySearch;
