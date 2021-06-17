/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';

const Breadcrumbs = ( { crumbs } ) => {
	return (
		<div>
			<a key="crumb-base" href={ wporgSiteUrl } rel="home">
				{ __( 'All Patterns', 'wporg-patterns' ) }
			</a>
			{ crumbs.map( ( i, idx ) => {
				let pathElement = <a href={ i.href }>{ i.label }</a>;

				if ( idx >= crumbs.length - 1 ) {
					pathElement = <span>{ i.label }</span>;
				}

				return (
					<Fragment key={ i.label }>
						<span className="sep">/</span>
						{ pathElement }
					</Fragment>
				);
			} ) }
		</div>
	);
};

export default Breadcrumbs;
