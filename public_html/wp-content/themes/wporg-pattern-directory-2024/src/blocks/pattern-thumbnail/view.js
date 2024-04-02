/* global FileReader */
/**
 * WordPress dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

/**
 * Module constants
 */
const MAX_ATTEMPTS = 10;
const RETRY_DELAY = 2000;

const { actions, state } = store( 'wporg/patterns/thumbnail', {
	state: {
		get attempts() {
			return getContext().attempts;
		},
		get shouldRetry() {
			return getContext().shouldRetry;
		},
		get hasError() {
			return getContext().hasError;
		},
		get base64Image() {
			return getContext().base64Image;
		},
		get hasLoaded() {
			return state.base64Image || state.hasError;
		},
	},
	actions: {
		setShouldRetry( value ) {
			const context = getContext();
			context.shouldRetry = value;
		},

		setHasError( value ) {
			const context = getContext();
			context.hasError = value;
		},

		setBase64Image( value ) {
			const context = getContext();
			context.base64Image = value;
		},

		*fetchImage( fullUrl ) {
			try {
				const context = getContext();
				const res = yield fetch( fullUrl );
				context.attempts++;

				if ( res.redirected ) {
					actions.setShouldRetry( true );
				} else if ( res.status === 200 && ! res.redirected ) {
					const blob = yield res.blob();

					const value = yield new Promise( ( resolve ) => {
						const reader = new FileReader();
						reader.onloadend = () => resolve( reader.result );
						reader.readAsDataURL( blob );
					} );

					actions.setBase64Image( value );
					actions.setShouldRetry( false );
				}
			} catch ( error ) {
				actions.setHasError( true );
				actions.setShouldRetry( false );
			}
		},
	},

	callbacks: {
		// Run on init, starts the image fetch process.
		*init() {
			const { src } = getContext();

			if ( ! state.base64Image ) {
				// Initial fetch.
				yield actions.fetchImage( src );

				while ( state.shouldRetry ) {
					yield new Promise( ( resolve ) => {
						setTimeout( () => resolve(), RETRY_DELAY );
					} );
					yield actions.fetchImage( src );

					if ( state.attempts >= MAX_ATTEMPTS ) {
						actions.setHasError( true );
						actions.setShouldRetry( false );
					}
				}
			}
		},
	},
} );
