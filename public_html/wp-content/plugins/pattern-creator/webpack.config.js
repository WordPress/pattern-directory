const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

const config = {
	...defaultConfig,
	output: {
		...defaultConfig.output,
		library: [ 'wp', 'patternCreator' ],
		libraryTarget: 'window',
	},

	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			/*
			 * Bundled @wordpress/* ESM (e.g. @wordpress/editor) imports CommonJS
			 * `diff` via extensionless subpaths webpack 5 rejects. Scoped to
			 * node_modules to keep strict resolution for project source.
			 */
			{
				test: /\.m?js$/,
				include: /node_modules/,
				resolve: { fullySpecified: false },
			},
		],
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
