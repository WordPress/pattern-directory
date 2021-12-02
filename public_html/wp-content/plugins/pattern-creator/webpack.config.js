const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const config = {
	...defaultConfig,
	output: {
		...defaultConfig.output,
		library: [ 'wp', 'patternCreator' ],
		libraryTarget: 'window',
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...defaultConfig.resolve.alias,
			'themes/pattern-directory': path.resolve( __dirname, '../../themes/pattern-directory/src/' ),
		},
	},
};

module.exports = config;
