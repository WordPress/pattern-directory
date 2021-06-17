/**
 * External dependencies
 */
import classnames from 'classnames';

const Header = ( { isHome, children } ) => {
	const classes = classnames( {
		'site-header': true,
		home: isHome,
	} );

	return (
		<header id="masthead" className={ classes } role="banner">
			<div className="site-branding">{ children }</div>
		</header>
	);
};

export default Header;
