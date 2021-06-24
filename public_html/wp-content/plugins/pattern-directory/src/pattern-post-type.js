/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import PluginWrapper from './pattern-post-type/';

registerPlugin( 'pattern-post-type', {
	render: PluginWrapper,
} );
