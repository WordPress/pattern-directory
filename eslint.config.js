/**
 * External dependencies
 */
const wpScriptsConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...wpScriptsConfig,
	{
		rules: {
			'@wordpress/i18n-text-domain': [
				'error',
				{ allowedTextDomain: [ 'wporg-patterns' ] },
			],
		},
	},
];
