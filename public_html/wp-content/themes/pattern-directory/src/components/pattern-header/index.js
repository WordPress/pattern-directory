/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Header from '../header';
import PageMenu from '../page-menu';
import Breadcrumbs from '../breadcrumbs';

const PatternHeader = ( { isLoggedIn } ) => {
	return (
		<Header isHome={ false }>
			<Breadcrumbs crumbs={ [ { href: '', label: __( 'Pattern Details', 'wporg-patterns' ) } ] } />
			<PageMenu
				isLoggedIn={ isLoggedIn }
				searchProps={ {
					action: '/',
				} }
			/>
		</Header>
	);
};

export default PatternHeader;
