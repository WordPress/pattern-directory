/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PatternSearch from '../pattern-search';

const MastHead = () => {
	return (
		<>
			<h1 className="site-title">
				<a href={ wporgSiteUrl } rel="home">
					{ __( 'WordPress Patterns', 'wporg-patterns' ) }
				</a>
			</h1>
			<p className="site-description">
				{ __(
					'Add a beautifully designed, ready to go layout to any WordPress site with a simple copy/paste.',
					'wporg-patterns'
				) }
			</p>
			<PatternSearch />
		</>
	);
};

export default MastHead;
