/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

export default apiFetch.createPreloadingMiddleware( {
	'/?_fields=description,gmt_offset,home,name,site_icon,site_icon_url,site_logo,timezone_string,url': {
		body: {
			// Set up default values for use in site-* blocks (site-title,
			// site-tagline, site-logo). These placeholders should be the same
			// as content returned in `includes/mock-blocks.php`.
			name: __( 'Site Title placeholder', 'wporg-patterns' ),
			description: __( 'Site Tagline placeholder', 'wporg-patterns' ),
			url: 'https://wordpress.org/patterns',
			home: 'https://wordpress.org/patterns',
			gmt_offset: '0',
			timezone_string: '',
			// Technically this should be an int (ID), but it's not validated
			// since the API reponse is preloaded below. Using "logo" to avoid
			// collision with a real media item.
			site_logo: 'logo',
			site_icon: 0,
			site_icon_url: 'https://s.w.org/images/wmark.png',
		},
	},
	// Replace the custom logo output with the WordPress W.
	'/wp/v2/media/logo?context=view': {
		body: {
			id: 'logo',
			alt_text: __( 'Site logo', 'wporg-patterns' ),
			source_url: 'https://s.w.org/images/wmark.png',
		},
	},
} );
