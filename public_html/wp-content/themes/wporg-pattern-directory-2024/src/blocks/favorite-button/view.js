/**
 * WordPress dependencies
 */
import { getContext, store } from '@wordpress/interactivity';

store( 'wporg/patterns/favorite-button', {
	state: {
		get labelScreenReader() {
			const { label, count } = getContext();
			return label.screenReader.replace( '%s', count );
		},
		get labelCount() {
			const { count } = getContext();
			return `(${ count })`;
		},
		get labelAction() {
			const { label, isFavorite } = getContext();
			return isFavorite ? label.remove : label.add;
		},
	},
	actions: {
		*triggerAction() {
			const context = getContext();
			if ( context.isFavorite ) {
				try {
					const newCount = yield wp.apiFetch( {
						path: '/wporg/v1/pattern-favorites',
						method: 'DELETE',
						data: { id: context.postId },
					} );
					context.isFavorite = false;
					context.count = newCount;
				} catch ( error ) {}
			} else {
				try {
					const newCount = yield wp.apiFetch( {
						path: '/wporg/v1/pattern-favorites',
						method: 'POST',
						data: { id: context.postId },
					} );
					context.isFavorite = true;
					context.count = newCount;
				} catch ( error ) {}
			}
		},
	},
} );
