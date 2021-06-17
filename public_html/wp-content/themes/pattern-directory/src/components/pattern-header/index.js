/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Header from '../header';
import PageMenu from '../page-menu';
import Search from '../search';
import Breadcrumbs from '../breadcrumbs';

const MySearch = () => {
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
			<Breadcrumbs crumbs={ [ { href: '', label: 'Pattern Details' } ] } />
			<PageMenu isLoggedIn={ isLoggedIn } searchComponent={ MySearch } />
		</Header>
	);
};

export default PatternHeader;
