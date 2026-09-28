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
 * Map bare @wordpress/* imports to their built CommonJS entry. Their
 * `react-native` field points at untranspiled `src`, which jsdom resolves and
 * then fails to parse as ESM/TypeScript.
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
	// Transpile the ESM-only dependencies pulled in by the built @wordpress packages.
	transformIgnorePatterns: [ 'node_modules/(?!(parsel-js|uuid|marked)/)' ],
	setupFiles: [
		...( defaultConfig.setupFiles || [] ),
		require.resolve( './jest.setup.js' ),
	],
};

module.exports = config;
