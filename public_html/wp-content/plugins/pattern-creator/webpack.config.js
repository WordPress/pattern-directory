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
			// Bundled @wordpress/* ESM packages (e.g. @wordpress/editor) import CommonJS
			// dependencies such as `diff` via extensionless subpaths. Webpack 5 treats
			// `.mjs` sources as fully specified and would otherwise refuse to resolve them.
			// Scoped to node_modules so project source keeps strict ESM resolution.
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
