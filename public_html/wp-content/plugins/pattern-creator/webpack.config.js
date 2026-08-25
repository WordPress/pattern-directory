const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

/*
 * Bundle these instead of externalising them: no `wp-*` handle is registered for
 * them where the creator runs, and a script with an unregistered dependency is
 * silently dropped. The extraction plugin bundles other editor-only packages by default.
 */
const BUNDLED_PACKAGES = [
	'@wordpress/editor',
	'@wordpress/global-styles-engine',
	'@wordpress/media-editor',
	'@wordpress/media-fields',
];

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
				if ( BUNDLED_PACKAGES.includes( request ) ) {
					return false;
				}
			},
		} ),
	],
};

module.exports = config;
