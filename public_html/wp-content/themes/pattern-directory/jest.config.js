const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

const config = {
	...defaultConfig,
	transformIgnorePatterns: [ 'node_modules/(?!(is-plain-obj))' ],
};

module.exports = config;
