/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default apiFetch.createPreloadingMiddleware( {
	'/?_fields=description,gmt_offset,home,name,site_icon,site_icon_url,site_logo,timezone_string,url': {
		body: {
			// Set up default values for use in site-* blocks (site-title, site-tagline).
			// These placeholders should be the same as returned in `includes/mock-blocks.php`.
			name: __( 'Site Title placeholder', 'wporg-patterns' ),
			description: __( 'Site Tagline placeholder', 'wporg-patterns' ),
			url: 'https://wordpress.org/patterns',
			home: 'https://wordpress.org/patterns',
			gmt_offset: '0',
			timezone_string: '',
			site_logo: false, // This needs to be a valid media ID, or false for the placeholder view.
			site_icon: 0,
			site_icon_url: 'https://s.w.org/images/wmark.png',
		},
	},
} );
