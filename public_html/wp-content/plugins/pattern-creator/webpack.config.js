const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

const config = {
	...defaultConfig,
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin( {
			injectPolyfill: true,
			requestToExternal: function( request ) {
				// Skip anything with extra folders in path.
				if ( /@wordpress\/[a-z-]+\/[a-z-]+/.test( request ) ) {
					return null;
				}
			},
		} ),
	],
};

module.exports = config;
