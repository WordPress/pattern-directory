/**
 * WordPress dependencies
 */
import { createReduxStore, registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import { STORE_NAME } from './constants';

export const storeConfig = {
	reducer: reducer,
	actions: actions,
	selectors: selectors,
	persist: [ 'preferences' ],
};

export { POST_TYPE, CATEGORY_SLUG, KEYWORD_SLUG } from './constants';

export const store = createReduxStore( STORE_NAME, storeConfig );

// Technically `registerStore` has been deprecated in favor of `register`, but
// `register` does not support the persistance layer. To switch, we'll need to
// update the `preferences` state to use the `@wordpress/preferences` package.
registerStore( STORE_NAME, storeConfig );
