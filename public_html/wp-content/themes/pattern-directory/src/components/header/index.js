/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { hasQueryArg } from '@wordpress/url';

/**
 * Internal dependencies
 */
import MastHead from '../masthead';
import PageMenu from '../page-menu';
import { useRoute } from '../../hooks';

const Header = ( { isHome, loggedIn } ) => {
	const { path } = useRoute();

	const showMastHead = isHome && ! hasQueryArg( path, 'search' );

	const classes = classnames( {
		'site-header': true,
		home: showMastHead,
	} );

	return (
		<header id="masthead" className={ classes } role="banner">
			<div className="site-branding">
				{ showMastHead ? <MastHead /> : <PageMenu isLoggedIn={ loggedIn } /> }
			</div>
		</header>
	);
};

export default Header;
