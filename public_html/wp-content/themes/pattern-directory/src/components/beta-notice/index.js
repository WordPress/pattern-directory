/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const BetaNotice = () => (
	<div className="notice notice-warning notice-alt" style={ { maxWidth: '960px', margin: '1rem auto' } }>
		<p style={ { fontSize: '0.9375rem', margin: '0.75rem 0' } }>
			{ __(
				'You’re a bit early to the party! This directory hasn’t yet fully launched. ',
				'wporg-patterns'
			) }
			<a href="https://github.com/WordPress/pattern-directory/">
				{ __( 'Follow along to see our progress.', 'wporg-patterns' ) }
			</a>
		</p>
	</div>
);

export default BetaNotice;
