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
import Breadcrumbs from '../breadcrumbs';

const PatternHeader = ( { isLoggedIn } ) => {

    console.log( document.referrer );
	return (
		<Header isHome={ false }>
			<Breadcrumbs crumbs={ [ { href: '', label: __( 'Pattern Details', 'wporg-patterns' ) } ] } />
			<PageMenu
				isLoggedIn={ isLoggedIn }
				searchProps={ {
					action: '/patterns-categories/all',
				} }
			/>
		</Header>
	);
};

export default PatternHeader;
