/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import Header from '../header';
import PageMenu from '../page-menu';
import Search from '../search';
import Breadcrumbs from '../breadcrumbs';

/**
 * Use a different search component when viewing a single pattern since it should just post to the categories page.
 */
const CustomSearch = () => {
	const [ searchValue, setSearchValue ] = useState();

	return (
		<Search
			onUpdate={ ( event ) => {
				event.preventDefault();
				setSearchValue( event.target.value );
			} }
			onSubmit={ ( event ) => {
				event.preventDefault();
				window.location.href = addQueryArgs( wporgSiteUrl, {
					search: searchValue,
				} );
			} }
		/>
	);
};

const PatternHeader = ( { isLoggedIn } ) => {
	return (
		<Header isHome={ false }>
			<Breadcrumbs crumbs={ [ { href: '', label: __( 'Pattern Details', 'wporg-patterns' ) } ] } />
			<PageMenu isLoggedIn={ isLoggedIn } searchComponent={ CustomSearch } />
		</Header>
	);
};

export default PatternHeader;
