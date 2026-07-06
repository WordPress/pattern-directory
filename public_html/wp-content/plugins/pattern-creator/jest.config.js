/**
 * External dependencies
 */
const fs = require( 'fs' );
const path = require( 'path' );

/**
 * Internal dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

// Absolute path to the installed `@wordpress` package scope.
const wordpressScope = path.dirname(
	path.dirname( require.resolve( '@wordpress/data/package.json' ) )
);

/*
 * Resolve bare @wordpress/* imports to their built CommonJS entry.
 *
 * These packages ship a `react-native` field pointing at untranspiled `src`,
 * which jsdom's `browser` export condition falls back to, leaving Jest to
 * execute raw ESM/TypeScript source and pull in untransformed dependencies
 * (uuid, marked, …). Mapping each package to its `build/index.cjs` runs the
 * tests against the same output the site ships.
 */
const wordpressPackageMappers = {};
for ( const name of fs.readdirSync( wordpressScope ) ) {
	if (
		fs.existsSync(
			path.join( wordpressScope, name, 'build', 'index.cjs' )
		)
	) {
		wordpressPackageMappers[ `^@wordpress/${ name }$` ] = path.join(
			wordpressScope,
			name,
			'build',
			'index.cjs'
		);
	}
}

const config = {
	...defaultConfig,
	moduleNameMapper: {
		...wordpressPackageMappers,
		...defaultConfig.moduleNameMapper,
		// Force module uuid to resolve with the CJS entry point, because
		// Jest does not support package.json.exports.
		// See https://github.com/uuidjs/uuid/issues/451
		uuid: require.resolve( 'uuid' ),
	},
	// @wordpress/* packages are mapped to their built CommonJS above, but a few
	// of their third-party dependencies ship ESM-only builds that still need to
	// be transpiled for Jest.
	transformIgnorePatterns: [ 'node_modules/(?!(parsel-js|uuid|marked)/)' ],
	setupFiles: [
		...( defaultConfig.setupFiles || [] ),
		require.resolve( './jest.setup.js' ),
	],
};

module.exports = config;
