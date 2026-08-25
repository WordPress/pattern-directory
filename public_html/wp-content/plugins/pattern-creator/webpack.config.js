const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

/*
 * Packages webpack has to bundle rather than leave behind as a `wp-*` script handle.
 *
 * WordPress only registers a handle for the packages Gutenberg builds as standalone
 * scripts. Externalising anything else leaves the bundle declaring a dependency that
 * nothing registers, and `wp_enqueue_script()` then drops it — along with its inline
 * scripts — without raising an error. The creator simply never boots.
 *
 * Only packages the plugin would externalise by default need listing here; it already
 * bundles `@wordpress/icons`, `@wordpress/interface`, `@wordpress/fields`, and
 * `@wordpress/dataviews` on its own.
 */
const BUNDLED_PACKAGES = [
	// Editor-only package, not registered on the front end where the creator runs.
	'@wordpress/editor',
	/*
	 * In the Gutenberg monorepo, but never shipped as standalone scripts. Reached
	 * transitively through the bundled `@wordpress/editor`.
	 */
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
