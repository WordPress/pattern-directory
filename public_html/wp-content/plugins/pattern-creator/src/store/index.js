/**
 * External dependencies
 */
import { registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as selectors from './selectors';
import * as actions from './actions';
import { MODULE_KEY } from './utils';

const store = registerStore( MODULE_KEY, {
	reducer,
	selectors,
	actions,
} );

export default store;
