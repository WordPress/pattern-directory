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

				// Externalize the JSX runtime to the `react-jsx-runtime`
				// script WordPress provides at runtime, instead of bundling
				// our own (React 18) copy. The bundled copy stamps elements
				// with the legacy `react.element` type, which React 19's
				// react-dom rejects (error #525). The pinned
				// dependency-extraction-webpack-plugin (5.2.0) predates this
				// mapping, so we add it here. This is version-agnostic: the
				// handle always matches the runtime's own React.
				//
				// TODO: Remove this (and the requestToHandle mapping below)
				// once @wordpress/scripts is bumped to a React-19-aware
				// version, which externalizes react/jsx-runtime by default.
				if (
					request === 'react/jsx-runtime' ||
					request === 'react/jsx-dev-runtime'
				) {
					return 'ReactJSXRuntime';
				}
			},
			requestToHandle( request ) {
				if (
					request === 'react/jsx-runtime' ||
					request === 'react/jsx-dev-runtime'
				) {
					return 'react-jsx-runtime';
				}
			},
		} ),
	],
};

module.exports = config;
