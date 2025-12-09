const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

const config = {
	...defaultConfig,
	output: {
		...defaultConfig.output,
		library: [ 'wp', 'patternCreator' ],
		libraryTarget: 'window',
	},

	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin( {
			requestToExternal( request ) {
				if (
					request === '@wordpress/editor' ||
					request === '@wordpress/icons' ||
					request === '@wordpress/interface' ||
					request === '@wordpress/fields' ||
					request === '@wordpress/dataviews'
				) {
					return false;
				}
			},
		} ),
	],
};

module.exports = config;
