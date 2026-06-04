/**
 * TextEncoder / TextDecoder are not provided by the jsdom test environment but
 * are required by some @wordpress dependencies at module load. Polyfill them
 * from Node's `util` module.
 */
const { TextEncoder, TextDecoder } = require( 'util' );

global.TextEncoder = global.TextEncoder || TextEncoder;
global.TextDecoder = global.TextDecoder || TextDecoder;
